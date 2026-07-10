<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Core;

use BeGenius\Ussd\Http\Requests\UssdRequest;
use BeGenius\Ussd\Services\MenuManager;

/**
 * UssdContext
 *
 * Context object that carries the state of the current USSD interaction
 * throughout the engine's processing pipeline.
 *
 * This follows the Context pattern (also known as "context object"
 * from Core J2EE Patterns). Instead of passing multiple parameters
 * between methods, we bundle everything into a single context object.
 *
 * The context is immutable for the duration of a single request
 * (the session may be updated by the engine).
 */
class UssdContext
{
    public function __construct(
        private readonly UssdRequest  $request,
        private UssdSession           $session,
        private readonly MenuManager  $menuManager,
        private readonly string       $defaultMenu,
    ) {}

    public function request(): UssdRequest
    {
        return $this->request;
    }

    public function session(): UssdSession
    {
        return $this->session;
    }

    public function setSession(UssdSession $session): void
    {
        $this->session = $session;
    }

    public function menuManager(): MenuManager
    {
        return $this->menuManager;
    }

    public function defaultMenu(): string
    {
        return $this->defaultMenu;
    }

    /**
     * Get the user's current input (last segment after '*').
     */
    public function input(): string
    {
        return $this->request->input();
    }

    /**
     * Determine if this is the start of a session.
     */
    public function isNewSession(): bool
    {
        return $this->request->isNewSession();
    }
}
