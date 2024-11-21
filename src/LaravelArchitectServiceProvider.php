<?php

namespace PartridgeRocks\LaravelArchitect;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use PartridgeRocks\LaravelArchitect\Commands\LaravelArchitectCommand;

class LaravelArchitectServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-architect')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_architect_table')
            ->hasCommand(LaravelArchitectCommand::class);
    }
}
