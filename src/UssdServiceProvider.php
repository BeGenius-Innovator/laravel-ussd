<?php

declare(strict_types=1);

namespace BeGenius\Ussd;

use BeGenius\Ussd\Core\UssdEngine;
use BeGenius\Ussd\Services\MenuManager;
use BeGenius\Ussd\Services\SessionManager;
use BeGenius\Ussd\Contracts\SessionDriver as SessionDriverContract;
use BeGenius\Ussd\Drivers\DefaultUssdDriver;
use BeGenius\Ussd\Contracts\UssdDriver as UssdDriverContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

/**
 * UssdServiceProvider
 *
 * The entry point for Laravel package registration. This service provider
 * binds the USSD engine and all its dependencies into the Laravel service
 * container, publishes configuration and migrations, and registers routes.
 *
 * In Laravel, a Service Provider is the central place to configure your
 * package. It tells Laravel which classes to bind in the container,
 * what files to publish, and which routes to register.
 *
 * Laravel Auto-Discovery (in composer.json extra.laravel.providers)
 * allows Laravel to automatically discover and register this provider
 * without the user manually adding it to config/app.php.
 */
class UssdServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     *
     * This method is called on every request. It should only be used
     * for binding classes into the service container. Never register
     * event listeners, routes, or views here — use the boot() method.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ussd.php',
            'ussd'
        );

        $this->registerServices();
    }

    /**
     * Bootstrap package services.
     *
     * This method is called after all service providers have been
     * registered. It is used to publish configuration, migrations,
     * and register routes.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ussd');
        $this->loadMigrations();
        $this->loadRoutes();
        $this->registerCommands();
        $this->excludeFromCsrf();
    }

    /**
     * Register all core services in the container.
     *
     * We bind:
     * - The USSD driver (parses incoming gateway payloads)
     * - The session driver (stores session state)
     * - The session manager (handles session lifecycle)
     * - The menu manager (resolves menu definitions)
     * - The USSD engine (orchestrates request -> response)
     */
    protected function registerServices(): void
    {
        // Bind the default USSD driver.
        $this->app->bind(UssdDriverContract::class, function ($app) {
            $driver = $app['config']->get('ussd.default_driver', 'default');

            return match ($driver) {
                'default'        => new DefaultUssdDriver(),
                'africastalking' => new Drivers\Gateway\AfricasTalkingDriver(),
                'orange'         => new Drivers\Gateway\OrangeDriver(),
                'moov'           => new Drivers\Gateway\MoovDriver(),
                'infobip'        => new Drivers\Gateway\InfobipDriver(),
                'twilio'         => new Drivers\Gateway\TwilioDriver(),
                'beem'           => new Drivers\Gateway\BeemDriver(),
                'advanta'        => new Drivers\Gateway\AdvantaDriver(),
                'hubtel'         => new Drivers\Gateway\HubtelDriver(),
                'mtn'            => new Drivers\Gateway\MtnDriver(),
                'vodacom'        => new Drivers\Gateway\VodacomDriver(),
                'airtel'         => new Drivers\Gateway\AirtelDriver(),
                default          => new DefaultUssdDriver(),
            };
        });

        // Bind the session driver.
        $this->app->bind(SessionDriverContract::class, function ($app) {
            $driver = $app['config']->get('ussd.session_driver', 'database');

            return match ($driver) {
                'database' => new Drivers\Session\DatabaseSessionDriver(
                    $app['config']->get('ussd.session_table', 'ussd_sessions')
                ),
                'array'    => new Drivers\Session\ArraySessionDriver(),
                'redis'    => new Drivers\Session\RedisSessionDriver(
                    connection: $app['config']->get('ussd.redis_connection', 'default'),
                    ttlMinutes: $app['config']->get('ussd.session_lifetime', 2),
                ),
                default    => new Drivers\Session\DatabaseSessionDriver(
                    $app['config']->get('ussd.session_table', 'ussd_sessions')
                ),
            };
        });

        // Bind the session manager as a singleton so state is consistent.
        $this->app->singleton(SessionManager::class, function ($app) {
            return new SessionManager(
                $app->make(SessionDriverContract::class),
                $app['config']->get('ussd.session_lifetime', 2)
            );
        });

        // Bind the menu manager as a singleton.
        $this->app->singleton(MenuManager::class, function () {
            return new MenuManager();
        });

        // Bind the USSD engine as a singleton under the key 'ussd.engine'.
        $this->app->singleton('ussd.engine', function ($app) {
            return new UssdEngine(
                $app->make(UssdDriverContract::class),
                $app->make(SessionManager::class),
                $app->make(MenuManager::class),
                $app['config']->get('ussd.default_menu', 'welcome'),
                $app['log']
            );
        });
    }

    /**
     * Publish configuration and migration files.
     *
     * --tag=ussd-config   : php artisan vendor:publish --tag=ussd-config
     * --tag=ussd-migrations: php artisan vendor:publish --tag=ussd-migrations
     */
    protected function publishResources(): void
    {
        $this->publishes([
            __DIR__.'/../config/ussd.php' => config_path('ussd.php'),
        ], 'ussd-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'ussd-migrations');
    }

    /**
     * Load package migrations from the database/migrations directory.
     */
    protected function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Load package routes.
     *
     * This registers the USSD callback endpoint (POST /ussd/callback)
     * and optionally the simulator routes.
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/ussd.php');
    }

    /**
     * Register Artisan commands.
     *
     * php artisan ussd:list  — List all registered menus
     * php artisan ussd:clean — Purge expired sessions
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\UssdListCommand::class,
                Console\UssdCleanCommand::class,
            ]);
        }
    }

    /**
     * Exclude USSD callback routes from CSRF protection.
     *
     * USSD gateways send POST requests without CSRF tokens.
     * Without this exclusion, all USSD requests would be rejected.
     */
    protected function excludeFromCsrf(): void
    {
        $prefix = $this->app['config']->get('ussd.routes_prefix', 'ussd');

        $this->app->afterResolving(
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            function ($middleware) use ($prefix) {
                $middleware->except[] = $prefix.'/callback';
            }
        );
    }
}
