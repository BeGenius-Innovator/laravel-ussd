<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Flows;

use BeGenius\Ussd\Core\UssdContext;

/**
 * Step
 *
 * Represents a single step within a USSD flow (workflow).
 *
 * A flow is a sequence of steps that collect information or guide
 * the user through a transaction. Each step:
 *
 * 1. Renders a prompt to the user
 * 2. Validates the user's input
 * 3. Stores the result in the session
 * 4. Returns the next step or signals completion
 *
 * This is the State pattern: each step is a state in a state machine.
 * The flow transitions from step to step based on user input.
 */
abstract class Step
{
    /**
     * Execute the step logic and return a response.
     *
     * @param UssdContext $context The current USSD context (session, request, etc.)
     *
     * @return StepResult The result indicating the next step or completion
     */
    abstract public function handle(UssdContext $context): StepResult;

    /**
     * Validate the user's input for this step.
     *
     * Override this in concrete steps to add validation.
     *
     * @return string|null Error message, or null if valid
     */
    public function validate(UssdContext $context): ?string
    {
        return null; // No validation by default
    }

    /**
     * Get the name/identifier of this step.
     * Used to track which step the user is currently on.
     */
    abstract public function name(): string;
}
