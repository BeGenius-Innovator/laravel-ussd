<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Flows;

use BeGenius\Ussd\Core\UssdContext;
use BeGenius\Ussd\Responses\UssdResponse;

/**
 * Flow
 *
 * A flow represents a multi-step USSD workflow.
 *
 * Unlike a simple menu (which only displays options), a flow guides
 * the user through a sequence of steps — collecting data, validating
 * input, and executing business logic.
 *
 * This is a Finite State Machine (FSM):
 * - Each step is a state
 * - User input triggers transitions between states
 * - The flow completes when it reaches a terminal state
 *
 * Example: Money Transfer Flow
 *   Step 1: Enter recipient phone → Step 2: Enter amount → Step 3: Enter PIN
 *   → Step 4: Confirm → END: Transaction processed
 */
class Flow
{
    /**
     * @var array<string, Step> Steps indexed by their name
     */
    private array $steps = [];

    /**
     * @param string $name       Unique flow identifier
     * @param string $firstStep  Name of the initial step
     */
    public function __construct(
        private readonly string $name,
        private string $firstStep = '',
    ) {}

    /**
     * Add a step to the flow.
     */
    public function addStep(Step $step): self
    {
        $this->steps[$step->name()] = $step;

        return $this;
    }

    /**
     * Set the first step of the flow.
     */
    public function firstStep(string $stepName): self
    {
        $this->firstStep = $stepName;

        return $this;
    }

    /**
     * Process the flow for the given context.
     *
     * Determines the current step from the session, executes it,
     * and returns the result.
     */
    public function handle(UssdContext $context): StepResult
    {
        $currentStepName = $context->session()->currentState() ?: $this->firstStep;

        $step = $this->resolveStep($currentStepName);

        // Run validation first
        $error = $step->validate($context);
        if ($error !== null) {
            return StepResult::stay(
                UssdResponse::continue($error)
            );
        }

        // Execute the step
        return $step->handle($context);
    }

    /**
     * Resolve a step by name.
     */
    public function resolveStep(string $name): Step
    {
        if (!isset($this->steps[$name])) {
            throw new \RuntimeException("Flow step '{$name}' not found in flow '{$this->name}'.");
        }

        return $this->steps[$name];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getFirstStep(): string
    {
        return $this->firstStep;
    }
}
