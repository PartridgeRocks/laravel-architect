<?php

namespace PartridgeRocks\LaravelArchitect\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \PartridgeRocks\LaravelArchitect\LaravelArchitect
 */
class LaravelArchitect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \PartridgeRocks\LaravelArchitect\LaravelArchitect::class;
    }
}
