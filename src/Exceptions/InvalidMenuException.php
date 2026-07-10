<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Exceptions;

/**
 * InvalidMenuException
 *
 * Thrown when a requested menu does not exist in the registry.
 */
class InvalidMenuException extends UssdException
{
    public static function notFound(string $name): self
    {
        return new self("USSD menu '{$name}' not found. Make sure the menu is registered before use.");
    }
}
