<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Exceptions;

/**
 * SessionExpiredException
 *
 * Thrown when the user's USSD session has timed out.
 * The engine will catch this and return an END response.
 */
class SessionExpiredException extends UssdException
{
    public function __construct()
    {
        parent::__construct('USSD session has expired.');
    }
}
