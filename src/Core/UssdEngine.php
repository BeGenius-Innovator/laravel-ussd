<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Core;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Exceptions\InvalidMenuException;
use BeGenius\Ussd\Exceptions\SessionExpiredException;
use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Flows\Flow;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use BeGenius\Ussd\Menus\Menu;
use BeGenius\Ussd\Responses\UssdResponse;
use BeGenius\Ussd\Services\MenuManager;
use BeGenius\Ussd\Services\SessionManager;
use Psr\Log\LoggerInterface;

/**
 * UssdEngine
 *
 * The central orchestrator of the USSD package.
 *
 * Responsibilities:
 * 1. Receive a UssdRequest from the controller
 * 2. Load (or create) the user's session
 * 3. Determine the current state (menu or flow)
 * 4. Execute the appropriate handler
 * 5. Save the session and return a UssdResponse
 *
 * This follows the Orchestration pattern: the engine coordinates
 * multiple components (session, menu, flow) without containing
 * business logic itself.
 *
 * Pipeline:
 *   Request → Parse (Driver) → Load Session → Resolve State
 *   → Execute Action → Save Session → Return Response
 */
class UssdEngine
{
    /**
     * @var Menu[] Menus registered via closure (deferred)
     */
    private array $menuDefinitions = [];

    /**
     * @var Flow[] Flows registered via closure
     */
    private array $flowDefinitions = [];

    public function __construct(
        private readonly UssdDriver     $driver,
        private readonly SessionManager $sessionManager,
        private readonly MenuManager    $menuManager,
        private readonly string         $defaultMenu,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Main entry point: process an incoming USSD request.
     */
    public function handle(UssdRequest $request): UssdResponse
    {
        try {
            $this->logRequest($request);

            // 1. Build the context
            $context = $this->buildContext($request);

            // 2. Check session expiration
            if (!$request->isNewSession() && $context->session()->isExpired(
                $this->sessionManager->lifetime()
            )) {
                throw new SessionExpiredException();
            }

            // 3. Resolve and execute the current state
            $response = $this->processState($context);

            // 4. Save the session
            $this->sessionManager->save($context->session());

            $this->logResponse($response);

            return $response;

        } catch (SessionExpiredException $e) {
            return $this->handleExpiredSession($request);
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Build the context from the incoming request.
     */
    private function buildContext(UssdRequest $request): UssdContext
    {
        // Load or create session
        $session = $this->sessionManager->loadOrCreate(
            $request->sessionId(),
            $request->phoneNumber(),
            $request->network(),
        );

        return new UssdContext(
            $request,
            $session,
            $this->menuManager,
            $this->defaultMenu,
        );
    }

    /**
     * Process the current state based on the session.
     */
    private function processState(UssdContext $context): UssdResponse
    {
        $session = $context->session();

        if ($context->isNewSession()) {
            $menu = $this->menuManager->resolve($this->defaultMenu);
            $session->setCurrentState($this->defaultMenu);

            return $menu->render();
        }

        $currentState = $session->currentState();

        if (str_starts_with($currentState, 'flow:')) {
            return $this->processFlow($context, $currentState);
        }

        return $this->processMenuSelection($context, $currentState);
    }

    /**
     * Process a menu selection.
     */
    private function processMenuSelection(UssdContext $context, string $menuName): UssdResponse
    {
        $menu = $this->menuManager->resolve($menuName);
        $input = $context->input();

        $option = $menu->findOption($input);

        if ($option === null) {
            return $menu->render();
        }

        if ($option->hasAction()) {
            $action = $option->action();

            if (is_string($action) && is_subclass_of($action, Flow::class)) {
                $flow = new $action();
                $this->registerFlow($flow);
                $context->session()->setCurrentState('flow:'.$flow->name().':'.$flow->getFirstStep());

                return $this->processFlow($context, $context->session()->currentState());
            }

            if (is_string($action) && is_subclass_of($action, Menu::class)) {
                $menuInstance = new $action();
                $context->session()->setCurrentState($menuInstance->name());

                return $menuInstance->render();
            }

            if ($action instanceof \Closure) {
                return $action($context);
            }

            if (is_string($action) && class_exists($action)) {
                $instance = app($action);

                return $instance($context);
            }
        }

        if ($option->isNavigation()) {
            $nextMenu = $this->menuManager->resolve($option->nextMenu());
            $context->session()->setCurrentState($option->nextMenu());

            return $nextMenu->render();
        }

        return $menu->render();
    }

    /**
     * Process a flow step.
     */
    private function processFlow(UssdContext $context, string $currentState): UssdResponse
    {
        // Parse state: "flow:{flowName}:{stepName}"
        $parts = explode(':', $currentState);

        if (count($parts) < 3) {
            throw new \RuntimeException("Invalid flow state: {$currentState}");
        }

        $flowName = $parts[1];
        $stepName = $parts[2];

        // Find the flow definition
        if (!isset($this->flowDefinitions[$flowName])) {
            throw new \RuntimeException("Flow '{$flowName}' not found.");
        }

        $flow = $this->flowDefinitions[$flowName];
        $result = $flow->handle($context);

        if ($result->isComplete()) {
            // Flow completed — return to default menu
            $context->session()->setCurrentState($this->defaultMenu);
        } elseif ($result->nextStep() !== null) {
            // Move to next step
            $context->session()->setCurrentState('flow:'.$flowName.':'.$result->nextStep());
        }

        return $result->response();
    }

    /**
     * Handle an expired session gracefully.
     */
    private function handleExpiredSession(UssdRequest $request): UssdResponse
    {
        $this->sessionManager->destroy($request->sessionId());

        return UssdResponse::end(__('ussd::ussd.session_expired'));
    }

    /**
     * Handle any uncaught error gracefully.
     */
    private function handleError(\Throwable $e): UssdResponse
    {
        $this->logger->error('USSD Engine Error: '.$e->getMessage(), [
            'exception' => $e,
        ]);

        return UssdResponse::end(__('ussd::ussd.system_error'));
    }

    /**
     * Register a menu. Supports fluent API.
     */
    public function menu(string $name): Menu
    {
        $menu = new Menu($name);
        $this->menuManager->register($menu);

        return $menu;
    }

    /**
     * Register a flow.
     */
    public function flow(string $name): Flow
    {
        $flow = new Flow($name);
        $this->flowDefinitions[$name] = $flow;

        return $flow;
    }

    /**
     * Register a pre-built flow instance.
     */
    public function registerFlow(Flow $flow): void
    {
        $this->flowDefinitions[$flow->name()] = $flow;
    }

    /**
     * Get the session manager (for external use).
     */
    public function session(): SessionManager
    {
        return $this->sessionManager;
    }

    private function logRequest(UssdRequest $request): void
    {
        $this->logger->info('USSD Request', [
            'sessionId'   => $request->sessionId(),
            'phoneNumber' => $request->phoneNumber(),
            'network'     => $request->network(),
            'text'        => $request->path(),
        ]);
    }

    private function logResponse(UssdResponse $response): void
    {
        $this->logger->info('USSD Response', [
            'type'    => $response->type(),
            'message' => $response->message(),
        ]);
    }
}
