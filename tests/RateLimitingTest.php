<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Http\Middleware\ThrottleUssdRequests;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * RateLimitingTest
 *
 * Tests the USSD rate limiting middleware.
 */
class RateLimitingTest extends TestCase
{
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = $this->app->make(RateLimiter::class);
    }

    /** @test */
    public function it_allows_request_under_limit()
    {
        $middleware = new ThrottleUssdRequests($this->limiter);

        $request = new Request([
            'phoneNumber' => '22670000001',
        ]);

        $response = $middleware->handle($request, function () {
            return new Response('CON Welcome');
        }, 5, 1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(5, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(4, $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function it_blocks_requests_over_limit()
    {
        $middleware = new ThrottleUssdRequests($this->limiter);

        $request = new Request([
            'phoneNumber' => '22670000002',
        ]);

        // Burn through all attempts
        for ($i = 0; $i < 3; $i++) {
            $middleware->handle($request, function () {
                return new Response('CON Test');
            }, 3, 1);
        }

        // This one should be blocked
        $response = $middleware->handle($request, function () {
            return new Response('CON Test');
        }, 3, 1);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('too many requests', strtolower($response->getContent()));
        $this->assertStringStartsWith('END ', $response->getContent());
    }

    /** @test */
    public function it_uses_msisdn_as_alternative_phone_field()
    {
        $middleware = new ThrottleUssdRequests($this->limiter);

        $request = new Request([
            'msisdn' => '22670000003',
        ]);

        $response = $middleware->handle($request, function () {
            return new Response('CON OK');
        }, 10, 1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_resolves_empty_phone_to_ip()
    {
        $middleware = new ThrottleUssdRequests($this->limiter);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $middleware->handle($request, function () {
            return new Response('CON OK');
        }, 10, 1);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
