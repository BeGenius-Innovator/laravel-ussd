<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Console;

use BeGenius\Ussd\Services\SessionManager;
use Illuminate\Console\Command;

class UssdCleanCommand extends Command
{
    protected $signature = 'ussd:clean {--minutes= : Override the configured session lifetime}';

    protected $description = 'Purge expired USSD sessions';

    public function handle(SessionManager $sessionManager): int
    {
        $minutes = (int) ($this->option('minutes') ?? $sessionManager->lifetime());

        $this->info("Purging USSD sessions older than {$minutes} minutes...");

        $deleted = $sessionManager->getDriver()->purgeExpired($minutes);

        $this->info("Done. {$deleted} expired session(s) purged.");

        return self::SUCCESS;
    }
}
