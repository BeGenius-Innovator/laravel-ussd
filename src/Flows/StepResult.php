<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Flows;

use BeGenius\Ussd\Responses\UssdResponse;

/**
 * StepResult
 *
 * Represents the result of executing a flow step.
 *
 * A step can produce:
 * - A next step (continue the flow)
 * - A completion (flow is done, return to the menu or end)
 * - An error (with a message to display)
 */
class StepResult
{
    private function __construct(
        private readonly UssdResponse $response,
        private readonly ?string $nextStep = null,
        private readonly bool $complete = false,
    ) {}

    /**
     * Move to the next step in the flow.
     */
    public static function next(string $nextStep, UssdResponse $response): self
    {
        return new self($response, $nextStep, false);
    }

    /**
     * Complete the flow successfully.
     */
    public static function complete(UssdResponse $response): self
    {
        return new self($response, null, true);
    }

    /**
     * Stay on the same step (e.g., after validation error).
     */
    public static function stay(UssdResponse $response): self
    {
        return new self($response, null, false);
    }

    public function response(): UssdResponse
    {
        return $this->response;
    }

    public function nextStep(): ?string
    {
        return $this->nextStep;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }
}
