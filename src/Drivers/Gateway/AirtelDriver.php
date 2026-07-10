<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * AirtelDriver
 *
 * Parses incoming USSD requests from the Airtel Africa gateway.
 *
 * Airtel operates in 14 African countries including Nigeria, Kenya,
 * Uganda, Tanzania, Zambia, Malawi, Chad, Congo, and others.
 *
 * Typical payload fields:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : User input concatenated with '*'
 *   - serviceCode  : USSD short code dialed
 *   - network      : Network code
 */
class AirtelDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('session_id', '')),
            phoneNumber: $request->input('msisdn', $request->input('phoneNumber', $request->input('subscriber', ''))),
            network:     $request->input('network', $request->input('operator', 'AIRTEL')),
            text:        $request->input('text', $request->input('input', $request->input('message', ''))),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('shortCode')),
        );
    }
}
