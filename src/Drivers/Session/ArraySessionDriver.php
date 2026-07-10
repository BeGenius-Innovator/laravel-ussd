<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Session;

use BeGenius\Ussd\Contracts\SessionDriver;
use BeGenius\Ussd\Core\UssdSession;
use Carbon\Carbon;

/**
 * ArraySessionDriver
 *
 * Stores USSD sessions in memory (an array).
 *
 * This driver is only suitable for testing. Sessions are lost
 * between requests because PHP is stateless. However, it is
 * useful for unit tests where session persistence is not needed.
 */
class ArraySessionDriver implements SessionDriver
{
    /**
     * @var array<string, UssdSession>
     */
    private array $sessions = [];

    public function find(string $sessionId): ?UssdSession
    {
        return $this->sessions[$sessionId] ?? null;
    }

    public function save(UssdSession $session): void
    {
        $this->sessions[$session->sessionId()] = $session;
    }

    public function delete(string $sessionId): void
    {
        unset($this->sessions[$sessionId]);
    }

    public function purgeExpired(int $lifetimeMinutes): int
    {
        $count = 0;
        $now = Carbon::now();

        foreach ($this->sessions as $id => $session) {
            if ($session->isExpired($lifetimeMinutes)) {
                unset($this->sessions[$id]);
                $count++;
            }
        }

        return $count;
    }
}
