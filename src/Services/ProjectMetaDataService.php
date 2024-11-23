<?php

namespace PartridgeRocks\LaravelArchitect\Services;

use Illuminate\Support\Facades\File;

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

    public function isLaravelProject(string $path): bool
    {
        $conditions = [
            'artisan_exists' => File::exists($path.'/artisan'),
            'composer_exists' => File::exists($path.'/composer.json'),
        ];

        return collect($conditions)->every(fn ($condition) => $condition);
    }
}
