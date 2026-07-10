<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Responses\UssdResponse;

/**
 * UssdResponseTest
 *
 * Tests the USSD response creation and formatting.
 *
 * These tests verify:
 * - CON responses are correctly formatted
 * - END responses are correctly formatted
 * - HTTP response conversion works
 * - Type checks (isContinue, isEnd) are accurate
 */
class UssdResponseTest extends TestCase
{
    /** @test */
    public function it_creates_a_continue_response_with_correct_format(): void
    {
        $response = UssdResponse::continue("Welcome\n1. Balance\n2. Transfer");

        $this->assertTrue($response->isContinue());
        $this->assertFalse($response->isEnd());
        $this->assertEquals('CON', $response->type());
        $this->assertEquals("Welcome\n1. Balance\n2. Transfer", $response->message());
        $this->assertEquals("CON Welcome\n1. Balance\n2. Transfer\n", $response->toString());
    }

    /** @test */
    public function it_creates_an_end_response_with_correct_format(): void
    {
        $response = UssdResponse::end('Thank you for using our service.');

        $this->assertTrue($response->isEnd());
        $this->assertFalse($response->isContinue());
        $this->assertEquals('END', $response->type());
        $this->assertEquals('Thank you for using our service.', $response->message());
        $this->assertEquals("END Thank you for using our service.\n", $response->toString());
    }

    /** @test */
    public function it_converts_to_http_response_with_correct_headers(): void
    {
        $ussdResponse = UssdResponse::continue('Test message');
        $httpResponse = $ussdResponse->toHttpResponse();

        $this->assertEquals(200, $httpResponse->getStatusCode());
        $this->assertEquals('text/plain; charset=utf-8', $httpResponse->headers->get('Content-Type'));
        $this->assertEquals("CON Test message\n", $httpResponse->getContent());
    }

    /** @test */
    public function it_handles_empty_messages(): void
    {
        $response = UssdResponse::continue('');

        $this->assertEquals('CON', $response->type());
        $this->assertEquals("CON \n", $response->toString());
    }
}
