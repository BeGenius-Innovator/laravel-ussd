<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Services;

use BeGenius\Ussd\Contracts\SessionDriver;
use BeGenius\Ussd\Core\UssdSession;

/**
 * SessionManager
 *
 * Manages the lifecycle of USSD sessions.
 *
 * USSD sessions are different from web sessions:
 * - They are short-lived (typically 60-120 seconds of inactivity)
 * - They survive across multiple HTTP requests from the gateway
 * - They store navigation state and user input
 *
 * The manager abstracts the storage backend (database, Redis, etc.)
 * behind the SessionDriver contract.
 */
class SessionManager
{
    public function __construct(
        private readonly SessionDriver $driver,
        private readonly int $lifetimeMinutes,
    ) {}

    /**
     * Load an existing session or create a new one.
     */
    public function loadOrCreate(string $sessionId, string $phoneNumber, string $network): UssdSession
    {
        $session = $this->driver->find($sessionId);

        if ($session === null) {
            $session = new UssdSession(
                sessionId:   $sessionId,
                phoneNumber: $phoneNumber,
                network:     $network,
            );

            $this->driver->save($session);
        }

        return $session;
    }

    /**
     * Save the session.
     */
    public function save(UssdSession $session): void
    {
        $this->driver->save($session);
    }

    /**
     * Destroy a session.
     */
    public function destroy(string $sessionId): void
    {
        $this->driver->delete($sessionId);
    }

    /**
     * Get the session lifetime in minutes.
     */
    public function lifetime(): int
    {
        return $this->lifetimeMinutes;
    }
}
