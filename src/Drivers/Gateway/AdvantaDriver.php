<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * AdvantaDriver
 *
 * Parses incoming USSD requests from the Advanta Africa gateway.
 *
 * Advanta sends query string parameters via HTTP POST/GET:
 *   - SESSIONID : Unique session identifier
 *   - MSISDN    : Subscriber phone number
 *   - INPUT     : User's input text
 *   - USSDCODE  : The USSD service code dialed
 *
 * @see https://www.advanta.africa/ussd-api-guide
 */
class AdvantaDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('SESSIONID', $request->input('sessionId', '')),
            phoneNumber: $request->input('MSISDN', $request->input('msisdn', $request->input('phoneNumber', ''))),
            network:     $request->input('network', $request->input('operator', 'ADVANTA')),
            text:        $request->input('INPUT', $request->input('text', $request->input('input', ''))),
            raw:         $request->all(),
            serviceCode: $request->input('USSDCODE', $request->input('serviceCode')),
        );
    }
}
