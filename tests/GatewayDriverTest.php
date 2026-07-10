<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Drivers\Gateway\AdvantaDriver;
use BeGenius\Ussd\Drivers\Gateway\AfricasTalkingDriver;
use BeGenius\Ussd\Drivers\Gateway\AirtelDriver;
use BeGenius\Ussd\Drivers\Gateway\BeemDriver;
use BeGenius\Ussd\Drivers\Gateway\HubtelDriver;
use BeGenius\Ussd\Drivers\Gateway\InfobipDriver;
use BeGenius\Ussd\Drivers\Gateway\MoovDriver;
use BeGenius\Ussd\Drivers\Gateway\MtnDriver;
use BeGenius\Ussd\Drivers\Gateway\OrangeDriver;
use BeGenius\Ussd\Drivers\Gateway\TwilioDriver;
use BeGenius\Ussd\Drivers\Gateway\VodacomDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

/**
 * GatewayDriverTest
 *
 * Tests that each gateway driver correctly parses its specific
 * payload format into a unified UssdRequest object.
 */
class GatewayDriverTest extends TestCase
{
    /** @test */
    public function africastalking_driver_parses_form_data(): void
    {
        $request = new Request([
            'sessionId'   => 'ATUid_abc123',
            'serviceCode' => '*384*1234#',
            'phoneNumber' => '+254712345678',
            'text'        => '1*2',
        ]);

        $ussdRequest = (new AfricasTalkingDriver())->parseRequest($request);

        $this->assertInstanceOf(UssdRequest::class, $ussdRequest);
        $this->assertEquals('ATUid_abc123', $ussdRequest->sessionId());
        $this->assertEquals('+254712345678', $ussdRequest->phoneNumber());
        $this->assertEquals('1*2', $ussdRequest->path());
        $this->assertEquals('2', $ussdRequest->input());
        $this->assertEquals('*384*1234#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function orange_driver_parses_msisdn_and_ussdText(): void
    {
        $request = new Request([
            'sessionId'  => 'ORANGE_789',
            'msisdn'     => '22507000101',
            'ussdText'   => '1',
            'serviceCode' => '*123#',
        ]);

        $ussdRequest = (new OrangeDriver())->parseRequest($request);

        $this->assertEquals('ORANGE_789', $ussdRequest->sessionId());
        $this->assertEquals('22507000101', $ussdRequest->phoneNumber());
        $this->assertEquals('1', $ussdRequest->input());
        $this->assertEquals('*123#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function moov_driver_uses_operator_field(): void
    {
        $request = new Request([
            'sessionId' => 'MOOV_456',
            'msisdn'    => '22670000000',
            'text'      => '2*5000',
            'operator'  => 'MOOV',
        ]);

        $ussdRequest = (new MoovDriver())->parseRequest($request);

        $this->assertEquals('MOOV_456', $ussdRequest->sessionId());
        $this->assertEquals('22670000000', $ussdRequest->phoneNumber());
        $this->assertEquals('5000', $ussdRequest->input());
        $this->assertEquals('MOOV', $ussdRequest->network());
    }

    /** @test */
    public function infobip_driver_parses_json_payload(): void
    {
        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'sessionId' => 'INFOBIP_111',
            'msisdn'    => '22177000101',
            'text'      => '3',
            'shortCode' => '*555#',
        ]));

        // Manually set the JSON content
        $request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag([
            'sessionId' => 'INFOBIP_111',
            'msisdn'    => '22177000101',
            'text'      => '3',
            'shortCode' => '*555#',
        ]));

        $ussdRequest = (new InfobipDriver())->parseRequest($request);

        $this->assertEquals('INFOBIP_111', $ussdRequest->sessionId());
        $this->assertEquals('22177000101', $ussdRequest->phoneNumber());
        $this->assertEquals('3', $ussdRequest->input());
        $this->assertEquals('*555#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function twilio_driver_parses_session_sid(): void
    {
        $request = new Request([
            'SessionSid' => 'TWILIO_222',
            'From'       => '+233501234567',
            'Body'       => '1',
            'To'         => '*789#',
        ]);

        $ussdRequest = (new TwilioDriver())->parseRequest($request);

        $this->assertEquals('TWILIO_222', $ussdRequest->sessionId());
        $this->assertEquals('+233501234567', $ussdRequest->phoneNumber());
        $this->assertEquals('1', $ussdRequest->input());
        $this->assertEquals('*789#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function beem_driver_parses_operator_field(): void
    {
        $request = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag([
            'sessionId' => 'BEEM_333',
            'msisdn'    => '255712000000',
            'text'      => '1*2*3',
            'operator'  => 'VODACOM_TZ',
            'code'      => '*150#',
        ]));

        $ussdRequest = (new BeemDriver())->parseRequest($request);

        $this->assertEquals('BEEM_333', $ussdRequest->sessionId());
        $this->assertEquals('255712000000', $ussdRequest->phoneNumber());
        $this->assertEquals('3', $ussdRequest->input());
        $this->assertEquals('VODACOM_TZ', $ussdRequest->network());
    }

    /** @test */
    public function advanta_driver_parses_uppercase_fields(): void
    {
        $request = new Request([
            'SESSIONID' => 'ADVANTA_444',
            'MSISDN'    => '254712000000',
            'INPUT'     => '4',
            'USSDCODE'  => '*123*456#',
        ]);

        $ussdRequest = (new AdvantaDriver())->parseRequest($request);

        $this->assertEquals('ADVANTA_444', $ussdRequest->sessionId());
        $this->assertEquals('254712000000', $ussdRequest->phoneNumber());
        $this->assertEquals('4', $ussdRequest->input());
        $this->assertEquals('*123*456#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function hubtel_driver_parses_ghana_payload(): void
    {
        $request = new Request([
            'sessionId' => 'HUBTEL_555',
            'msisdn'    => '23354000000',
            'text'      => '5',
            'network'   => 'MTN_GH',
            'code'      => '*789#',
        ]);

        $ussdRequest = (new HubtelDriver())->parseRequest($request);

        $this->assertEquals('HUBTEL_555', $ussdRequest->sessionId());
        $this->assertEquals('23354000000', $ussdRequest->phoneNumber());
        $this->assertEquals('5', $ussdRequest->input());
        $this->assertEquals('MTN_GH', $ussdRequest->network());
    }

    /** @test */
    public function mtn_driver_parses_standard_payload(): void
    {
        $request = new Request([
            'sessionId'   => 'MTN_666',
            'msisdn'      => '25670000000',
            'text'        => '6',
            'serviceCode' => '*165#',
        ]);

        $ussdRequest = (new MtnDriver())->parseRequest($request);

        $this->assertEquals('MTN_666', $ussdRequest->sessionId());
        $this->assertEquals('25670000000', $ussdRequest->phoneNumber());
        $this->assertEquals('6', $ussdRequest->input());
        $this->assertEquals('*165#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function vodacom_driver_parses_payload(): void
    {
        $request = new Request([
            'sessionId'   => 'VODA_777',
            'msisdn'      => '25576000000',
            'text'        => '7',
            'serviceCode' => '*155#',
        ]);

        $ussdRequest = (new VodacomDriver())->parseRequest($request);

        $this->assertEquals('VODA_777', $ussdRequest->sessionId());
        $this->assertEquals('25576000000', $ussdRequest->phoneNumber());
        $this->assertEquals('7', $ussdRequest->input());
    }

    /** @test */
    public function airtel_driver_parses_payload(): void
    {
        $request = new Request([
            'sessionId'   => 'AIRTEL_888',
            'msisdn'      => '26097000000',
            'text'        => '8',
            'serviceCode' => '*333#',
        ]);

        $ussdRequest = (new AirtelDriver())->parseRequest($request);

        $this->assertEquals('AIRTEL_888', $ussdRequest->sessionId());
        $this->assertEquals('26097000000', $ussdRequest->phoneNumber());
        $this->assertEquals('8', $ussdRequest->input());
        $this->assertEquals('*333#', $ussdRequest->serviceCode());
    }

    /** @test */
    public function default_driver_can_be_switched_in_config(): void
    {
        $this->app['config']->set('ussd.default_driver', 'africastalking');

        $driver = $this->app->make(\BeGenius\Ussd\Contracts\UssdDriver::class);

        $this->assertInstanceOf(AfricasTalkingDriver::class, $driver);
    }
}
