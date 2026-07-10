<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Menus;

/**
 * MenuOption
 *
 * Represents a single selectable option in a USSD menu.
 *
 * Each option has:
 * - A key (the digit the user presses, e.g., "1")
 * - A label (displayed text, e.g., "Check Balance")
 * - An optional action (class name or callable)
 * - An optional next menu (for navigation without a class)
 *
 * Examples:
 *   1. Check Balance    → BalanceAction::class
 *   2. Transfer Money   → TransferFlow::class
 *   3. Help             → "help_menu"
 */
class MenuOption
{
    /**
     * @param \Closure|string|null $action A callable or class name
     */
    public function __construct(
        private readonly string          $key,
        private readonly string          $label,
        private readonly \Closure|string|null $action = null,
        private readonly ?string         $nextMenu = null,
    ) {}

    /**
     * Check if this option navigates to another menu.
     */
    public function isNavigation(): bool
    {
        return $this->nextMenu !== null && $this->action === null;
    }

    /**
     * Check if this option triggers an action class/flow.
     */
    public function hasAction(): bool
    {
        return $this->action !== null;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    /**
     * @return \Closure|string|null
     */
    public function action(): \Closure|string|null
    {
        return $this->action;
    }

    public function nextMenu(): ?string
    {
        return $this->nextMenu;
    }
}
