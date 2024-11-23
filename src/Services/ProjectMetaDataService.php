<?php

namespace PartridgeRocks\LaravelArchitect\Services;

class ProjectMetaDataService
{
    public function getProjectName(): string
    {
        return config('app.name');
    }

    public function getCurrentLaravelVersion(): string
    {
        return app()->version();
    }

}
