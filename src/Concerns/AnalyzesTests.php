<?php

namespace PartridgeRocks\LaravelArchitect\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait AnalyzesTests
{
    public function countTestsInDirectory(string $directory): int
    {
        if (! File::exists($directory)) {
            return 0;
        }

        $count = 0;
        foreach (File::allFiles($directory) as $file) {
            if (Str::endsWith($file->getFilename(), ['Test.php', '.spec.php'])) {
                $count++;
            }
        }

        return $count;
    }

    public function identifyTestFramework(string $path): string
    {
        $composer = json_decode(File::get($path . '/composer.json'), true);

        if (isset($composer['require-dev']['pestphp/pest'])) {
            return 'Pest 🐝';
        }

        if (isset($composer['require-dev']['phpunit/phpunit'])) {
            return 'PHPUnit 🎯';
        }

        return 'Not identified';
    }
}
