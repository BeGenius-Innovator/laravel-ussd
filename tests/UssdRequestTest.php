<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Drivers\DefaultUssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * UssdRequestTest
 *
 * Tests the USSD request parsing and input extraction.
 *
 * These tests verify:
 * - Request parsing from HTTP via driver
 * - Input extraction (last segment after '*')
 * - Multi-segment path handling
 * - New session detection
 */
class UssdRequestTest extends TestCase
{
    /** @test */
    public function it_parses_from_http_request_via_driver(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'network'     => 'ORANGE',
            'text'        => '1*2',
            'serviceCode' => '*123#',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertEquals('abc123', $ussdRequest->sessionId());
        $this->assertEquals('22670000000', $ussdRequest->phoneNumber());
        $this->assertEquals('ORANGE', $ussdRequest->network());
        $this->assertEquals('1*2', $ussdRequest->path());
        $this->assertEquals('*123#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function it_extracts_the_last_input(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'text'        => '1*2*5000',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertEquals('5000', $ussdRequest->input());
    }

    /** @test */
    public function it_returns_inputs_as_array(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'text'        => '1*2*5000',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertEquals(['1', '2', '5000'], $ussdRequest->inputs());
    }

    /** @test */
    public function it_detects_new_session(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'text'        => '',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertTrue($ussdRequest->isNewSession());
    }

    /** @test */
    public function it_detects_existing_session(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'text'        => '1',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertFalse($ussdRequest->isNewSession());
    }

    /** @test */
    public function it_returns_empty_input_when_no_text(): void
    {
        $httpRequest = new Request([
            'sessionId'   => 'abc123',
            'phoneNumber' => '22670000000',
            'text'        => '',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertEquals('', $ussdRequest->input());
        $this->assertEquals([], $ussdRequest->inputs());
    }

    /** @test */
    public function it_supports_alternative_field_names(): void
    {
        $httpRequest = new Request([
            'session_id'   => 'abc123',
            'msisdn'       => '22670000000',
            'operator'     => 'MOOV',
            'text'         => '1',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, new DefaultUssdDriver());

        $this->assertEquals('abc123', $ussdRequest->sessionId());
        $this->assertEquals('22670000000', $ussdRequest->phoneNumber());
        $this->assertEquals('MOOV', $ussdRequest->network());
    }
}
