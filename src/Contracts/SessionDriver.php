<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Contracts;

use BeGenius\Ussd\Core\UssdSession;

/**
 * SessionDriver Contract
 *
 * Defines the interface for USSD session storage backends.
 *
 * USSD sessions need to persist across the multiple HTTP requests
 * that make up a single USSD interaction. The session stores:
 *
 * - The current menu/state the user is in
 * - User data (temporary input, partial transactions)
 * - Session metadata (creation time, last activity)
 *
 * Implementations can use:
 * - Database (recommended for production)
 * - Redis/Memcached (for high-throughput)
 * - Array (for testing)
 */
interface SessionDriver
{
    /**
     * Find a session by its unique identifier.
     */
    public function find(string $sessionId): ?UssdSession;

    /**
     * Save or update a session.
     */
    public function save(UssdSession $session): void;

    /**
     * Delete a session (e.g., on completion or timeout).
     */
    public function delete(string $sessionId): void;

    /**
     * Delete all expired sessions (garbage collection).
     */
    public function purgeExpired(int $lifetimeMinutes): int;
}
