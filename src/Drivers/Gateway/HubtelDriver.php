<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * HubtelDriver
 *
 * Parses incoming USSD requests from the Hubtel gateway (Ghana).
 *
 * Hubtel is a leading digital communications platform in Ghana.
 * Their USSD gateway sends payloads with:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber MSISDN
 *   - text         : User input concatenated with '*'
 *   - network      : Mobile network code
 *   - code         : USSD short code dialed
 *
 * @see https://developers.hubtel.com
 */
class HubtelDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('SessionId', $request->input('session_id', ''))),
            phoneNumber: $request->input('msisdn', $request->input('MSISDN', $request->input('phoneNumber', ''))),
            network:     $request->input('network', $request->input('Network', 'HUBTEL')),
            text:        $request->input('text', $request->input('Text', $request->input('input', ''))),
            raw:         $request->all(),
            serviceCode: $request->input('code', $request->input('Code', $request->input('serviceCode'))),
        );
    }
}
