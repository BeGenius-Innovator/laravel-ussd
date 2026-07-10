<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * MoovDriver
 *
 * Parses incoming USSD requests from the Moov/Africell gateway.
 *
 * Moov operates in Benin, Burkina Faso, Côte d'Ivoire, Congo,
 * Gabon, Mali, Niger, Senegal, Togo, and other African countries.
 *
 * Typical payload fields:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : User input concatenated with '*'
 *   - operator     : Network operator code
 *   - serviceCode  : USSD service code dialed
 *
 * Field names may vary by subsidiary.
 */
class MoovDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('session_id', '')),
            phoneNumber: $request->input('msisdn', $request->input('phoneNumber', $request->input('number', ''))),
            network:     $request->input('operator', $request->input('network', 'MOOV')),
            text:        $request->input('text', $request->input('message', '')),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('ussdCode')),
        );
    }
}
