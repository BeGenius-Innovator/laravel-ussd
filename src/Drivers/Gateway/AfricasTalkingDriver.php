<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * AfricasTalkingDriver
 *
 * Parses incoming USSD requests from the Africa's Talking gateway.
 *
 * Africa's Talking sends form-encoded POST requests with these fields:
 *   - sessionId    : Unique session identifier (e.g., "ATUid_abc123")
 *   - serviceCode  : The USSD code dialed (e.g., "*384*1234#")
 *   - phoneNumber  : Subscriber MSISDN (e.g., "+254712345678")
 *   - text         : Concatenated user input (empty on first request)
 *
 * @see https://developers.africastalking.com/docs/ussd/overview
 */
class AfricasTalkingDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', ''),
            phoneNumber: $request->input('phoneNumber', ''),
            network:     $request->input('networkCode', $request->input('network', 'AT')),
            text:        $request->input('text', ''),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode'),
        );
    }
}
