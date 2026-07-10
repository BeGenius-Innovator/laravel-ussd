<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Gateway;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * TwilioDriver
 *
 * Parses incoming USSD requests from the Twilio gateway.
 *
 * Twilio sends form-encoded POST requests with:
 *   - sessionId   : Unique session identifier
 *   - phoneNumber : Subscriber phone number (From)
 *   - text        : User input
 *   - serviceCode : The short code dialed (To)
 *
 * Twilio USSD responses use TwiML XML format (<Response>
 * with <Message>), so this driver only handles the incoming
 * request parsing. The response conversion to TwiML should
 * be handled at the controller level or via a dedicated
 * Twilio response formatter.
 *
 * @see https://www.twilio.com/docs/ussd
 */
class TwilioDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId', $request->input('SessionSid', '')),
            phoneNumber: $request->input('phoneNumber', $request->input('From', '')),
            network:     $request->input('network', $request->input('NetworkCode', 'TWILIO')),
            text:        $request->input('text', $request->input('Body', '')),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode', $request->input('To')),
        );
    }
}
