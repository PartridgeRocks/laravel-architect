<?php

namespace PartridgeRocks\LaravelArchitect;

use PartridgeRocks\LaravelArchitect\Commands\LaravelArchitectCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
