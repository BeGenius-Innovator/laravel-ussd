<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * BeemDriver
 *
 * Parses incoming USSD requests from the Beem Africa gateway.
 *
 * Beem is a USSD gateway provider operating in Tanzania, Malawi,
 * Zambia, and other African countries. They send JSON payloads with:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : Concatenated user input
 *   - operator     : Network operator name
 *   - code         : USSD short code dialed
 *
 * @see https://docs.beem.africa/ussd
 */
class BeemDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();

        return new UssdRequest(
            sessionId:   $payload['sessionId'] ?? $payload['session_id'] ?? $request->input('sessionId', ''),
            phoneNumber: $payload['msisdn'] ?? $payload['phoneNumber'] ?? $request->input('msisdn', ''),
            network:     $payload['operator'] ?? $payload['network'] ?? $request->input('operator', 'BEEM'),
            text:        $payload['text'] ?? $payload['input'] ?? $request->input('text', ''),
            raw:         $payload ?: $request->all(),
            serviceCode: $payload['code'] ?? $payload['serviceCode'] ?? $request->input('serviceCode'),
        );
    }
}
