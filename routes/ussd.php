<?php

declare(strict_types=1);

use BeGenius\Ussd\Http\Controllers\UssdController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| USSD Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming HTTP requests from USSD gateways.
|
| The main callback endpoint:
|   POST /ussd/callback
|
| Gateways will be configured to send requests to this URL.
|
| The simulator (when enabled) provides a web UI for testing:
|   GET  /ussd/simulator
|   POST /ussd/simulator
|
*/

$prefix = config('ussd.routes_prefix', 'ussd');

Route::group(['prefix' => $prefix], function () {
    // Main USSD callback endpoint
    Route::post('/callback', UssdController::class)
        ->name('ussd.callback');

    // Simulator (only in non-production environments)
    if (config('ussd.simulator_enabled', false) && !app()->environment('production')) {
        Route::get('/simulator', [\BeGenius\Ussd\Http\Controllers\SimulatorController::class, 'index'])
            ->name('ussd.simulator');

        Route::post('/simulator', [\BeGenius\Ussd\Http\Controllers\SimulatorController::class, 'handle'])
            ->name('ussd.simulator.handle');
    }
});
