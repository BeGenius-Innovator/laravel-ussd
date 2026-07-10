<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * VodacomDriver
 *
 * Parses incoming USSD requests from Vodacom/Vodafone gateways.
 *
 * Vodacom operates in South Africa, Tanzania, Mozambique, Lesotho,
 * and the DRC. Vodafone operates in Ghana, Kenya, Egypt, and others.
 *
 * Typical payload fields:
 *   - sessionId    : Unique session identifier
 *   - msisdn       : Subscriber phone number
 *   - text         : User input concatenated with '*'
 *   - serviceCode  : USSD short code dialed
 *   - network      : Network code
 */
class VodacomDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('SessionId', $request->input('session_id', ''))),
            phoneNumber: $request->input('msisdn', $request->input('MSISDN', $request->input('phoneNumber', ''))),
            network:     $request->input('network', $request->input('Network', $request->input('operator', 'VODACOM'))),
            text:        $request->input('text', $request->input('Text', $request->input('input', ''))),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('ServiceCode', $request->input('shortCode'))),
        );
    }
}
