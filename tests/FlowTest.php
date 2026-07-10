<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Core\UssdContext;
use BeGenius\Ussd\Core\UssdSession;
use BeGenius\Ussd\Flows\Flow;
use BeGenius\Ussd\Flows\Step;
use BeGenius\Ussd\Flows\StepResult;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use BeGenius\Ussd\Responses\UssdResponse;
use BeGenius\Ussd\Services\MenuManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * FlowTest
 *
 * Tests the flow (workflow) system.
 *
 * These tests verify:
 * - Flow step execution
 * - Step-to-step transitions
 * - Flow completion
 * - Step validation errors
 */
class FlowTest extends TestCase
{
    /** @test */
    public function it_executes_a_flow_with_multiple_steps(): void
    {
        $flow = new Flow('transfer', 'ask_recipient');

        $flow->addStep(new class extends Step {
            public function name(): string { return 'ask_recipient'; }
            public function handle(UssdContext $context): StepResult
            {
                return StepResult::next('ask_amount', UssdResponse::continue('Enter recipient number:'));
            }
        });

        $flow->addStep(new class extends Step {
            public function name(): string { return 'ask_amount'; }
            public function handle(UssdContext $context): StepResult
            {
                return StepResult::next('confirm', UssdResponse::continue('Enter amount:'));
            }
        });

        $flow->addStep(new class extends Step {
            public function name(): string { return 'confirm'; }
            public function handle(UssdContext $context): StepResult
            {
                return StepResult::complete(UssdResponse::end('Transfer complete!'));
            }
        });

        // First step
        $context = $this->createContext('ask_recipient');
        $result = $flow->handle($context);
        $this->assertFalse($result->isComplete());
        $this->assertEquals('ask_amount', $result->nextStep());

        // Second step
        $context2 = $this->createContext('ask_amount');
        $result2 = $flow->handle($context2);
        $this->assertFalse($result2->isComplete());
        $this->assertEquals('confirm', $result2->nextStep());

        // Third (final) step
        $context3 = $this->createContext('confirm');
        $result3 = $flow->handle($context3);
        $this->assertTrue($result3->isComplete());
        $this->assertEquals('END', $result3->response()->type());
        $this->assertStringContainsString('Transfer complete!', $result3->response()->message());
    }

    /** @test */
    public function it_validates_step_input(): void
    {
        $flow = new Flow('pin_flow', 'enter_pin');

        $flow->addStep(new class extends Step {
            public function name(): string { return 'enter_pin'; }
            public function validate(UssdContext $context): ?string
            {
                $input = $context->input();
                if ($input !== '1234') {
                    return 'Invalid PIN. Please try again.';
                }
                return null;
            }
            public function handle(UssdContext $context): StepResult
            {
                return StepResult::complete(UssdResponse::end('PIN verified.'));
            }
        });

        // Wrong PIN
        $wrongContext = $this->createContextWithInput('enter_pin', '0000');
        $result = $flow->handle($wrongContext);
        $this->assertNull($result->nextStep()); // Stay on same step
        $this->assertStringContainsString('Invalid PIN', $result->response()->message());

        // Correct PIN
        $correctContext = $this->createContextWithInput('enter_pin', '1234');
        $result2 = $flow->handle($correctContext);
        $this->assertTrue($result2->isComplete());
        $this->assertStringContainsString('PIN verified.', $result2->response()->message());
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_step(): void
    {
        $this->expectException(\RuntimeException::class);

        $flow = new Flow('test', 'nonexistent');
        $flow->addStep(new class extends Step {
            public function name(): string { return 'exist'; }
            public function handle(UssdContext $context): StepResult
            {
                return StepResult::complete(UssdResponse::end('Done'));
            }
        });

        $context = $this->createContext('nonexistent');
        $flow->handle($context);
    }

    private function createContext(string $currentState): UssdContext
    {
        $httpRequest = new Request([
            'sessionId'   => 'flow_test',
            'phoneNumber' => '22670000000',
            'text'        => '1',
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, app(\BeGenius\Ussd\Contracts\UssdDriver::class));

        $session = new UssdSession('flow_test', '22670000000', 'ORANGE', $currentState, [], Carbon::now(), Carbon::now());

        return new UssdContext($ussdRequest, $session, new MenuManager(), 'welcome');
    }

    private function createContextWithInput(string $currentState, string $input): UssdContext
    {
        $httpRequest = new Request([
            'sessionId'   => 'flow_test',
            'phoneNumber' => '22670000000',
            'text'        => $input,
        ]);

        $ussdRequest = UssdRequest::fromHttpRequest($httpRequest, app(\BeGenius\Ussd\Contracts\UssdDriver::class));

        $session = new UssdSession('flow_test', '22670000000', 'ORANGE', $currentState, [], Carbon::now(), Carbon::now());

        return new UssdContext($ussdRequest, $session, new MenuManager(), 'welcome');
    }
}
