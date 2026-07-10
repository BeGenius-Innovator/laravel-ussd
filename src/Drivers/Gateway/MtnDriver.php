<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * MtnDriver
 *
 * Parses incoming USSD requests from MTN's gateway.
 *
 * MTN operates in 24 countries across Africa and the Middle East
 * (South Africa, Nigeria, Ghana, Uganda, Rwanda, Zambia, Cameroon,
 * Côte d'Ivoire, etc.). Their USSD callback payload typically uses:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : User input (concatenated with '*')
 *   - serviceCode  : USSD short code
 *   - network      : Network identifier
 *
 * Field names may vary by operating company.
 */
class MtnDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('session_id', '')),
            phoneNumber: $request->input('msisdn', $request->input('phoneNumber', $request->input('subscriber', ''))),
            network:     $request->input('network', $request->input('operator', 'MTN')),
            text:        $request->input('text', $request->input('input', $request->input('message', ''))),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('shortCode')),
        );
    }
}
