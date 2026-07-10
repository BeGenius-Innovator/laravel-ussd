<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Menus\Menu;

/**
 * MenuTest
 *
 * Tests the USSD menu system.
 *
 * These tests verify:
 * - Menu creation with fluent API
 * - Option registration and lookup
 * - Menu rendering as CON response
 * - Menu navigation via nextMenu
 * - Invalid option handling
 */
class MenuTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Register menus for testing
        $app->resolving('ussd.engine', function ($engine) {
            $engine->menu('welcome')
                ->title('Welcome to USSD')
                ->option('1', 'Balance', 'balance_menu')
                ->option('2', 'Exit');

            $engine->menu('balance_menu')
                ->title('Your balance is: 5000 FCFA')
                ->option('0', 'Back', 'welcome');
        });
    }

    /** @test */
    public function it_creates_a_menu_with_fluent_api(): void
    {
        $menu = Ussd::menu('test_menu')
            ->title('Test Menu')
            ->option('1', 'Option One')
            ->option('2', 'Option Two');

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals('test_menu', $menu->name());
    }

    /** @test */
    public function it_renders_a_menu_as_continue_response(): void
    {
        $menu = new Menu('main');
        $menu->title('Main Menu')
            ->option('1', 'Balance')
            ->option('2', 'Transfer');

        $response = $menu->render();

        $this->assertTrue($response->isContinue());
        $this->assertStringContainsString('Main Menu', $response->message());
        $this->assertStringContainsString('1. Balance', $response->message());
        $this->assertStringContainsString('2. Transfer', $response->message());
    }

    /** @test */
    public function it_finds_an_option_by_key(): void
    {
        $menu = new Menu('main');
        $menu->title('Menu')
            ->option('1', 'Option A')
            ->option('2', 'Option B');

        $option = $menu->findOption('2');
        $this->assertNotNull($option);
        $this->assertEquals('2', $option->key());
        $this->assertEquals('Option B', $option->label());
    }

    /** @test */
    public function it_returns_null_for_invalid_option(): void
    {
        $menu = new Menu('main');
        $menu->title('Menu')
            ->option('1', 'Option A');

        $this->assertNull($menu->findOption('9'));
    }

    /** @test */
    public function it_detects_navigation_options(): void
    {
        $menu = new Menu('main');
        $menu->option('1', 'Go to Help', nextMenu: 'help');

        $option = $menu->findOption('1');
        $this->assertNotNull($option);
        $this->assertTrue($option->isNavigation());
        $this->assertFalse($option->hasAction());
    }

    /** @test */
    public function it_detects_action_options(): void
    {
        $menu = new Menu('main');
        $menu->option('1', 'Balance', 'App\\Actions\\BalanceAction');

        $option = $menu->findOption('1');
        $this->assertNotNull($option);
        $this->assertTrue($option->hasAction());
        $this->assertFalse($option->isNavigation());
        $this->assertEquals('App\\Actions\\BalanceAction', $option->action());
    }
}
