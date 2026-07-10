<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Http\Controllers;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;

/**
 * SimulatorController
 *
 * Provides a web-based USSD simulator for testing menus and flows
 * without needing an actual telecom gateway.
 *
 * The simulator renders a fake phone interface where you can:
 * - See the USSD screen output
 * - Type responses in an input field
 * - Navigate through menus
 *
 * It works by calling the same UssdEngine as the real gateway,
 * using a simulated request.
 *
 * NOTE: This is a development tool. Disable it in production
 * by setting USSD_SIMULATOR_ENABLED=false in your .env.
 */
class SimulatorController extends Controller
{
    public function __construct(
        private readonly UssdDriver $driver,
    ) {}

    /**
     * Display the simulator interface.
     */
    public function index()
    {
        $response = null;
        $sessionId = null;

        return view('ussd::simulator', [
            'response'  => $response,
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Handle a simulated USSD request.
     */
    public function handle(Request $request)
    {
        $sessionId = $request->input('session_id', 'sim_'.Carbon::now()->timestamp);
        $text = $request->input('text', '');

        // Build a simulated request
        $simulated = Request::create('/ussd/callback', 'POST', [
            'sessionId'   => $sessionId,
            'phoneNumber' => $request->input('phone', '22670000000'),
            'network'     => $request->input('network', 'SIMULATOR'),
            'text'        => $text,
            'serviceCode' => $request->input('service_code', '*123#'),
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($simulated, $this->driver);
        $ussdResponse = Ussd::handle($ussdRequest);

        return view('ussd::simulator', [
            'response'  => $ussdResponse->toString(),
            'sessionId' => $sessionId,
            'text'      => $text,
            'phone'     => $request->input('phone', '22670000000'),
            'network'   => $request->input('network', 'SIMULATOR'),
        ]);
    }
}
