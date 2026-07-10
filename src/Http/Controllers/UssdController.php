<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Http\Controllers;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * UssdController
 *
 * The HTTP controller that receives incoming requests from USSD gateways.
 *
 * This controller:
 * 1. Receives the raw HTTP request from the gateway
 * 2. Parses it into a UssdRequest via the configured driver
 * 3. Passes it to the UssdEngine for processing
 * 4. Returns the UssdResponse as an HTTP response
 *
 * The controller is intentionally thin — all logic lives in the engine.
 */
class UssdController extends Controller
{
    public function __construct(
        private readonly UssdDriver $driver,
    ) {}

    /**
     * Handle an incoming USSD callback from the gateway.
     */
    public function __invoke(Request $request)
    {
        $ussdRequest = UssdRequest::fromHttpRequest($request, $this->driver);

        $ussdResponse = Ussd::handle($ussdRequest);

        return $ussdResponse->toHttpResponse();
    }
}
