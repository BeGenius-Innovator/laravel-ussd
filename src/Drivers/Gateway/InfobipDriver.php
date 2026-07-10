<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * InfobipDriver
 *
 * Parses incoming USSD requests from the Infobip gateway.
 *
 * Infobip is a global communication API provider used by many
 * telecom operators worldwide. Their USSD gateway sends JSON
 * payloads with:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : User's input text
 *   - shortCode    : The short code dialed
 *   - sessionActive: Whether the session is active (boolean)
 *   - language     : Language code (optional)
 *
 * @see https://www.infobip.com/docs/ussd
 */
class InfobipDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();

        return new UssdRequest(
            sessionId:   $payload['sessionId'] ?? $payload['session_id'] ?? '',
            phoneNumber: $payload['msisdn'] ?? $payload['phoneNumber'] ?? '',
            network:     $payload['network'] ?? $payload['operator'] ?? $payload['networkCode'] ?? 'INFOBIP',
            text:        $payload['text'] ?? $payload['message'] ?? '',
            raw:         $payload,
            serviceCode: $payload['shortCode'] ?? $payload['serviceCode'] ?? null,
        );
    }
}
