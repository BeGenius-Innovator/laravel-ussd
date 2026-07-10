<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Console;

use BeGenius\Ussd\Facades\Ussd;
use BeGenius\Ussd\Services\MenuManager;
use Illuminate\Console\Command;

class UssdListCommand extends Command
{
    protected $signature = 'ussd:list';

    protected $description = 'List all registered USSD menus and flows';

    public function handle(MenuManager $menuManager): int
    {
        $menus = $menuManager->names();

        if (empty($menus)) {
            $this->warn('No USSD menus registered.');

            return self::SUCCESS;
        }

        $this->info('Registered USSD Menus:');
        $this->newLine();

        $rows = [];

        foreach ($menus as $name) {
            $menu = $menuManager->resolve($name);
            $options = $menu->options();
            $optionCount = count($options);

            $firstOptions = '';
            foreach (array_slice($options, 0, 3) as $opt) {
                $firstOptions .= sprintf(
                    "  %s. %s%s\n",
                    $opt->key(),
                    $opt->label(),
                    $opt->hasAction() ? $opt->action() : ''
                );
            }
            if ($optionCount > 3) {
                $firstOptions .= "  ... +".($optionCount - 3)." more\n";
            }

            $rows[] = [
                $name,
                $optionCount,
                $firstOptions ?: '  (no options)',
            ];
        }

        $this->table(['Menu', 'Options', 'Preview'], $rows);

        return self::SUCCESS;
    }
}
