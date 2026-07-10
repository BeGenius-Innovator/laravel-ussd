<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Services;

use BeGenius\Ussd\Menus\Menu;
use BeGenius\Ussd\Exceptions\InvalidMenuException;

/**
 * MenuManager
 *
 * Central registry for all USSD menu definitions.
 *
 * Menus are registered once (typically in a Service Provider) and
 * then resolved by the engine at runtime based on the session state.
 *
 * This follows the Registry pattern, providing a single place to
 * look up any menu by name.
 */
class MenuManager
{
    /**
     * @var array<string, Menu> Registered menus indexed by name
     */
    private array $menus = [];

    /**
     * Register a menu.
     */
    public function register(Menu $menu): void
    {
        $this->menus[$menu->name()] = $menu;
    }

    /**
     * Resolve a menu by name.
     *
     * @throws InvalidMenuException if the menu does not exist
     */
    public function resolve(string $name): Menu
    {
        if (!isset($this->menus[$name])) {
            throw InvalidMenuException::notFound($name);
        }

        return $this->menus[$name];
    }

    /**
     * Check if a menu exists.
     */
    public function has(string $name): bool
    {
        return isset($this->menus[$name]);
    }

    /**
     * Alias for resolve().
     */
    public function menu(string $name): Menu
    {
        return $this->resolve($name);
    }

    /**
     * Get all registered menu names.
     *
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->menus);
    }
}
