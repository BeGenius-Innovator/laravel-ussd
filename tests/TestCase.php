<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\UssdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * TestCase
 *
 * Base test case for the USSD package.
 *
 * Uses Orchestra Testbench, which provides a Laravel application
 * environment for testing packages without a full Laravel install.
 *
 * Orchestra Testbench creates a minimal Laravel application that
 * loads your service provider, allowing you to test your package
 * in isolation.
 */
class TestCase extends Orchestra
{
    /**
     * Load the package service provider.
     */
    protected function getPackageProviders($app): array
    {
        return [
            UssdServiceProvider::class,
        ];
    }

    /**
     * Configure the environment for testing.
     */
    protected function defineEnvironment($app): void
    {
        // Use array session driver for tests (no database needed)
        $app['config']->set('ussd.session_driver', 'array');
        $app['config']->set('ussd.session_lifetime', 2);
        $app['config']->set('ussd.default_menu', 'welcome');
    }
}
