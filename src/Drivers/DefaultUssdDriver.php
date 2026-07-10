<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * DefaultUssdDriver
 *
 * The default driver for parsing incoming USSD requests.
 *
 * This driver expects the gateway to send the following fields
 * in the request body (or query string):
 *
 *   - sessionId     : Unique session identifier
 *   - phoneNumber   : Subscriber MSISDN
 *   - network       : Network operator code
 *   - text          : User input string
 *   - serviceCode   : USSD service code (optional)
 *
 * Gateway-specific drivers should extend this class or implement
 * the UssdDriver interface directly.
 */
class DefaultUssdDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('session_id', '')),
            phoneNumber: $request->input('phoneNumber', $request->input('phone_number', $request->input('msisdn', ''))),
            network:     $request->input('network', $request->input('operator', 'unknown')),
            text:        $request->input('text', ''),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('service_code')),
        );
    }
}
