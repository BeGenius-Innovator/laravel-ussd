<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * OrangeDriver
 *
 * Parses incoming USSD requests from the Orange telecom gateway.
 *
 * Orange (used in France, Côte d'Ivoire, Senegal, Mali, Burkina Faso,
 * Cameroon, etc.) sends requests with:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - ussdText     : User input text (alternative field name)
 *   - text         : User input text
 *   - serviceCode  : USSD service code dialed
 *   - network      : Network identifier
 *
 * @see https://developer.orange.com
 */
class OrangeDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('session_id', '')),
            phoneNumber: $request->input('msisdn', $request->input('phoneNumber', '')),
            network:     $request->input('network', $request->input('operator', 'ORANGE')),
            text:        $request->input('ussdText', $request->input('text', '')),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('ussdCode')),
        );
    }
}
