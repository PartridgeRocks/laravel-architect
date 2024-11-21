<?php

namespace PartridgeRocks\LaravelArchitect\Commands;

use Illuminate\Console\Command;

class LaravelArchitectCommand extends Command
{
    public $signature = 'laravel-architect';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
