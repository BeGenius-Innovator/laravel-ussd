<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Contracts;

use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * UssdDriver Contract
 *
 * Defines the interface that all USSD gateway drivers must implement.
 *
 * Each telecom gateway (Orange, Moov, Africa's Talking, etc.) sends
 * different payload formats. A driver's job is to translate the
 * gateway-specific payload into a unified UssdRequest object.
 *
 * To add support for a new gateway, implement this interface and
 * register your driver in the service provider.
 */
interface UssdDriver
{
    /**
     * Parse an incoming HTTP request from the gateway into a UssdRequest.
     */
    public function parseRequest(Request $request): UssdRequest;
}
