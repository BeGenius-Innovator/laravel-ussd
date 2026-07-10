<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * USSD Facade
 *
 * Provides a static interface to the UssdEngine instance.
 *
 * @method static \BeGenius\Ussd\Responses\UssdResponse handle(\BeGenius\Ussd\Http\Requests\UssdRequest $request)
 * @method static \BeGenius\Ussd\Menus\Menu menu(string $name)
 * @method static \BeGenius\Ussd\Menus\Menu currentMenu(string $name)
 * @method static \BeGenius\Ussd\Flows\Flow flow(string $name)
 * @method static \BeGenius\Ussd\Services\SessionManager session()
 *
 * @see \BeGenius\Ussd\Core\UssdEngine
 */
class Ussd extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ussd.engine';
    }
}
