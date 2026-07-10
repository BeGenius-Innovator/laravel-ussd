<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Http\Middleware;

use BeGenius\Ussd\Facades\Ussd;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ThrottleUssdRequests
 *
 * Rate-limiting middleware specifically for USSD endpoints.
 *
 * Unlike web throttling (which limits by IP or user ID), USSD
 * requests should be throttled by phone number. A single user
 * should not be able to hammer the USSD endpoint faster than
 * the configured number of requests per minute.
 *
 * Usage in routes:
 *   Route::post('/callback', ...)->middleware('throttle:ussd');
 *
 * Or register the named limiter in AppServiceProvider:
 *   RateLimiter::for('ussd', fn ($job) => Limit::perMinute(10));
 */
class ThrottleUssdRequests
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next, int $maxAttempts = 10, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts)
        );
    }

    /**
     * Resolve the rate limit key from the phone number.
     */
    private function resolveRequestSignature(Request $request): string
    {
        $phone = $request->input('phoneNumber', $request->input('msisdn', $request->input('From', '')));
        $ip = $request->ip();

        return 'ussd:'.$phone.':'.$ip;
    }

    private function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return new Response(
            "END Too many requests. Please try again later.\n",
            429,
            [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
            ]
        );
    }

    private function addHeaders(Response $response, int $maxAttempts, int $remaining): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}
