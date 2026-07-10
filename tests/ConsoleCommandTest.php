<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Services\SessionManager;

/**
 * ConsoleCommandTest
 *
 * Tests the Artisan commands provided by the package.
 */
class ConsoleCommandTest extends TestCase
{
    /** @test */
    public function ussd_list_shows_registered_menus()
    {
        Ussd::menu('test_list_menu')
            ->title('Test Menu')
            ->option('1', 'Option A')
            ->option('2', 'Option B');

        $this->artisan('ussd:list')
            ->expectsTable(['Menu', 'Options', 'Preview'], [
                ['test_list_menu', 2, "  1. Option A\n  2. Option B\n"],
            ])
            ->assertExitCode(0);
    }

    /** @test */
    public function ussd_list_shows_warning_when_no_menus()
    {
        $this->artisan('ussd:list')
            ->expectsOutputToContain('No USSD menus registered')
            ->assertExitCode(0);
    }

    /** @test */
    public function ussd_clean_purges_expired_sessions()
    {
        $this->artisan('ussd:clean')
            ->expectsOutputToContain('Purging')
            ->assertExitCode(0);
    }

    /** @test */
    public function ussd_clean_accepts_custom_minutes()
    {
        $this->artisan('ussd:clean', ['--minutes' => 5])
            ->expectsOutputToContain('5 minutes')
            ->assertExitCode(0);
    }
}
