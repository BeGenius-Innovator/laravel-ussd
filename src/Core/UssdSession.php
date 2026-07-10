<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Core;

use Carbon\Carbon;

/**
 * UssdSession
 *
 * Represents a single USSD session from a user's interaction.
 *
 * A USSD session starts when a user dials a service code (*123#)
 * and ends when the user reaches an END response or the session
 * times out. During the session, the user navigates through menus,
 * enters data, and performs transactions.
 *
 * The session stores:
 * - The user's phone number and network
 * - The current state (menu or flow step)
 * - Arbitrary data (form inputs, transaction details)
 * - Timing information (creation, last activity)
 */
class UssdSession
{
    /**
     * @param string      $sessionId    Unique session ID from the gateway
     * @param string      $phoneNumber  Subscriber's phone number
     * @param string      $network      Network operator code
     * @param string      $currentState The current menu name or flow step ID
     * @param array       $data         Arbitrary session data (user inputs, state)
     * @param Carbon|null $createdAt    When the session started
     * @param Carbon|null $updatedAt    Last activity timestamp
     */
    public function __construct(
        private string  $sessionId,
        private string  $phoneNumber,
        private string  $network,
        private string  $currentState = '',
        private array   $data = [],
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null,
    ) {
        if ($this->createdAt === null) {
            $this->createdAt = Carbon::now();
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = Carbon::now();
        }
    }

    /**
     * Check if the session has expired based on the given lifetime.
     */
    public function isExpired(int $lifetimeMinutes): bool
    {
        if ($this->updatedAt === null) {
            return true;
        }

        return Carbon::now()->diffInMinutes($this->updatedAt, true) >= $lifetimeMinutes;
    }

    /**
     * Store a value in the session data.
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->touch();
    }

    /**
     * Retrieve a value from the session data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a key exists in session data.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove a key from session data.
     */
    public function forget(string $key): void
    {
        unset($this->data[$key]);
        $this->touch();
    }

    /**
     * Get all session data.
     */
    public function allData(): array
    {
        return $this->data;
    }

    /**
     * Mark the session as recently active.
     */
    public function touch(): void
    {
        $this->updatedAt = Carbon::now();
    }

    // --- Getters / Setters ---

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function network(): string
    {
        return $this->network;
    }

    public function currentState(): string
    {
        return $this->currentState;
    }

    public function setCurrentState(string $state): void
    {
        $this->currentState = $state;
        $this->touch();
    }

    public function createdAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }
}
