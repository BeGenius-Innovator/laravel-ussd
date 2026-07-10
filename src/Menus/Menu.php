<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Menus;

use BeGenius\Ussd\Responses\UssdResponse;

/**
 * Menu
 *
 * Represents a USSD menu screen displayed to the user.
 *
 * A menu is a named screen with a title, options, and an optional
 * action class. When a user selects an option, the menu either
 * navigates to another menu or invokes an action (business logic).
 *
 * This class uses the Builder pattern: methods return $this so
 * definitions can be chained fluently.
 *
 * Example:
 *   Ussd::menu('main')
 *       ->title("Welcome")
 *       ->option("1", "Balance", BalanceAction::class)
 *       ->option("2", "Transfer", TransferFlow::class);
 */
class Menu
{
    /**
     * @var MenuOption[]
     */
    private array $options = [];

    private string $title = '';

    private ?string $action = null;

    public function __construct(
        private readonly string $name,
    ) {}

    /**
     * Set the menu title (displayed at the top of the screen).
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Add an option to this menu.
     *
     * @param string                $key      The digit key (e.g., "1")
     * @param string                $label    Display label (e.g., "Check Balance")
     * @param \Closure|string|null  $action   Action class name or callable
     * @param string|null           $nextMenu Next menu name to navigate to
     */
    public function option(
        string $key,
        string $label,
        \Closure|string|null $action = null,
        ?string $nextMenu = null,
    ): self {
        $this->options[] = new MenuOption($key, $label, $action, $nextMenu);

        return $this;
    }

    /**
     * Set the action class for this menu (for menus that are also actions).
     */
    public function action(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Render the menu as a USSD CON response.
     *
     * Format:
     *   CON <title>
     *   1. Option 1
     *   2. Option 2
     */
    public function render(): UssdResponse
    {
        $lines = [$this->title];

        foreach ($this->options as $option) {
            $lines[] = $option->key().'. '.$option->label();
        }

        $message = implode("\n", $lines);

        return UssdResponse::continue($message);
    }

    /**
     * Find an option by its key.
     */
    public function findOption(string $key): ?MenuOption
    {
        foreach ($this->options as $option) {
            if ($option->key() === $key) {
                return $option;
            }
        }

        return null;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return MenuOption[]
     */
    public function options(): array
    {
        return $this->options;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }
}
