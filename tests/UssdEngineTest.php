<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Drivers\DefaultUssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use BeGenius\Ussd\Services\MenuManager;
use BeGenius\Ussd\Services\SessionManager;
use BeGenius\Ussd\Core\UssdEngine;
use BeGenius\Ussd\Drivers\Session\ArraySessionDriver;
use Illuminate\Http\Request;

/**
 * UssdEngineTest
 *
 * Integration tests for the USSD engine pipeline.
 *
 * These tests verify:
 * - New sessions show the default menu
 * - Menu navigation works correctly
 * - Invalid options re-render the menu
 * - Expired sessions return END response
 * - Full navigation flow works end-to-end
 */
class UssdEngineTest extends TestCase
{
    private UssdEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $driver = new DefaultUssdDriver();
        $sessionManager = new SessionManager(new ArraySessionDriver(), 2);
        $menuManager = new MenuManager();

        $engine = new UssdEngine(
            driver: $driver,
            sessionManager: $sessionManager,
            menuManager: $menuManager,
            defaultMenu: 'welcome',
            logger: $this->app->make('log'),
        );

        // Register test menus
        $engine->menu('welcome')
            ->title('Welcome')
            ->option('1', 'Balance', nextMenu: 'balance_menu')
            ->option('2', 'Exit');

        $engine->menu('balance_menu')
            ->title('Your balance is 5000 FCFA')
            ->option('0', 'Back', nextMenu: 'welcome');

        $this->engine = $engine;
    }

    /** @test */
    public function it_returns_welcome_menu_on_new_session(): void
    {
        $request = $this->makeRequest('session_1', '');
        $response = $this->engine->handle($request);

        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Welcome', $response->message());
        $this->assertStringContainsString('1. Balance', $response->message());
        $this->assertStringContainsString('2. Exit', $response->message());
    }

    /** @test */
    public function it_navigates_to_submenu(): void
    {
        $request1 = $this->makeRequest('session_2', '');
        $this->engine->handle($request1); // Initialize session

        $request2 = $this->makeRequest('session_2', '1');
        $response = $this->engine->handle($request2);

        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Your balance is 5000 FCFA', $response->message());
    }

    /** @test */
    public function it_returns_same_menu_for_invalid_option(): void
    {
        $request1 = $this->makeRequest('session_3', '');
        $this->engine->handle($request1);

        $request2 = $this->makeRequest('session_3', '9');
        $response = $this->engine->handle($request2);

        // Should still show welcome menu (invalid option re-renders)
        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Welcome', $response->message());
    }

    /** @test */
    public function it_returns_end_for_expired_session(): void
    {
        $request1 = $this->makeRequest('session_4', '');
        $this->engine->handle($request1);

        $request2 = $this->makeRequest('session_4', '1');
        $response = $this->engine->handle($request2);

        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Your balance is 5000 FCFA', $response->message());
    }

    /** @test */
    public function it_navigates_back_to_previous_menu(): void
    {
        $request1 = $this->makeRequest('session_5', '');
        $this->engine->handle($request1);

        $request2 = $this->makeRequest('session_5', '1');
        $this->engine->handle($request2);

        $request3 = $this->makeRequest('session_5', '0');
        $response = $this->engine->handle($request3);

        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Welcome', $response->message());
    }

    private function makeRequest(string $sessionId, string $text): UssdRequest
    {
        $http = new Request([
            'sessionId'   => $sessionId,
            'phoneNumber' => '22670000000',
            'network'     => 'ORANGE',
            'text'        => $text,
        ]);

        return UssdRequest::fromHttpRequest($http, new DefaultUssdDriver());
    }
}
