<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Http\Requests;

use BeGenius\Ussd\Contracts\UssdDriver;
use Illuminate\Http\Request;

/**
 * UssdRequest
 *
 * Encapsulates an incoming USSD request from any gateway.
 *
 * Unlike a standard HTTP request, a USSD request comes from a telecom
 * gateway and contains specific fields like sessionId, phoneNumber,
 * network code, and the user's text input.
 *
 * Instead of working with raw HTTP request data scattered across the
 * codebase, this class provides a clean, typed abstraction.
 * The driver (e.g., OrangeDriver, MoovDriver) is responsible for
 * mapping the gateway's payload to this unified structure.
 *
 * This abstraction is critical for gateway-agnostic development:
 * your application code never touches gateway-specific fields.
 */
class UssdRequest
{
    /**
     * @param string      $sessionId   Unique session identifier from the gateway
     * @param string      $phoneNumber Subscriber's MSISDN (phone number)
     * @param string      $network     Network/operator code (e.g., "ORANGE", "MOOV")
     * @param string      $text        The user's input string, including previous menu digits
     * @param array       $raw         The original payload from the gateway (for debugging)
     * @param string|null $serviceCode The USSD service code (*123# etc.)
     */
    public function __construct(
        private readonly string  $sessionId,
        private readonly string  $phoneNumber,
        private readonly string  $network,
        private readonly string  $text,
        private readonly array   $raw = [],
        private readonly ?string $serviceCode = null,
    ) {}

    /**
     * Factory method: create a UssdRequest from an HTTP request via the configured driver.
     *
     * This is the preferred way to instantiate a UssdRequest in a controller.
     * The driver handles the gateway-specific parsing logic.
     */
    public static function fromHttpRequest(Request $request, UssdDriver $driver): self
    {
        return $driver->parseRequest($request);
    }

    /**
     * Get the last input from the user.
     *
     * The text field contains the full path of selections separated by '*',
     * e.g., "1*2*5000". This method extracts the most recent input.
     */
    public function input(): string
    {
        $parts = explode('*', $this->text);
        $last = end($parts);

        return $last !== false ? $last : '';
    }

    /**
     * Get the previous inputs as an array.
     *
     * Example: "1*2*5000" → ['1', '2', '5000']
     */
    public function inputs(): array
    {
        $text = $this->text;

        return $text !== '' ? explode('*', $text) : [];
    }

    /**
     * Get the full navigation path.
     */
    public function path(): string
    {
        return $this->text;
    }

    /**
     * Determine if this is the start of a new session.
     *
     * A new session is identified by an empty text field.
     */
    public function isNewSession(): bool
    {
        return $this->text === '';
    }

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

    public function raw(): array
    {
        return $this->raw;
    }

    public function serviceCode(): ?string
    {
        return $this->serviceCode;
    }
}
