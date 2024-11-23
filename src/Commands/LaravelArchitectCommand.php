<?php

namespace PartridgeRocks\LaravelArchitect\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Dotenv\Dotenv;
use PartridgeRocks\LaravelArchitect\LaravelArchitect;
use RuntimeException;
use SplFileObject;
use Symfony\Component\Finder\Finder;

class LaravelArchitectCommand extends Command
{
    public $signature = 'laravel:architect
        {path? : Path to the Laravel project (defaults to current directory)}
        {--deep : Perform a deeper analysis}
        {--skip-env : Skip environment file analysis}';

    public $description = 'Analyze and tell the story of your Laravel application';

    protected array $hiddenEnvKeys = [
        'APP_KEY',
        'DB_PASSWORD',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'MAIL_PASSWORD',
        'REDIS_PASSWORD',
        'GOOGLE_MAP_API_KEY',
        'SLACK_BOT_USER_OAUTH_TOKEN',
        'SLACK_VERIFICATION_TOKEN',
        'RESEND_KEY',
        'LOG_SLACK_WEBHOOK_URL',
        'SLACK_ALERT_WEBHOOK_URL',
        'CARD_API_KEY',
    ];

    public function __construct(private readonly LaravelArchitect $architect)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path') ?? getcwd();

        if (!$this->isLaravelProject($path)) {
            $this->error("❌ This doesn't seem to be a Laravel project!");
            return self::FAILURE;
        }

        $this->newLine();
        $this->title("📖 The Story of Your Laravel Application");
        $this->newLine();

        // Load environment variables safely
        $env = $this->option('skip-env') ? [] : $this->loadEnvironmentVariables($path);

        // Begin the analysis
        try {
            $this->chapterOne($path, $env);
            $this->chapterTwo($path);
            $this->chapterThree($path);
            $this->chapterFour($path);
            $this->chapterFive($path, $env);

            $this->newLine();
            $this->info('✨ Analysis complete!');
        } catch (Exception $e) {
            $this->error("An error occurred during analysis: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function loadEnvironmentVariables(string $path): array
    {
        try {
            if (File::exists($path . '/.env')) {
                return Dotenv::createImmutable($path)->load();
            }
        } catch (Exception $e) {
            $this->warn("Warning: Could not parse .env file: " . $e->getMessage());
        }
        return [];
    }

    private function title(string $title): void
    {
        $length = strlen($title);
        $this->newLine();
        $this->line(str_repeat('=', $length));
        $this->info($title);
        $this->line(str_repeat('=', $length));
        $this->newLine();
    }

    private function chapterOne(string $path, array $env): void
    {
        $this->components->info('Chapter 1: Project Overview 📋');

        $composer = json_decode(File::get($path . '/composer.json'), true);

        $details = [
            'Project Name'     => $env['APP_NAME'] ?? $composer['name'] ?? 'Unknown',
            'Description'      => $composer['description'] ?? 'No description provided',
            'Environment'      => $env['APP_ENV'] ?? 'Not specified',
            'Debug Mode'       => ($env['APP_DEBUG'] ?? 'false') === 'true' ? 'Enabled ⚠️' : 'Disabled ✅',
            'Maintenance Mode' => $this->isInMaintenanceMode($path) ? 'Enabled ⚠️' : 'Disabled',
            'Project Size'     => $this->getFormattedSize($path),
            'Lines of Code'    => number_format($this->getKloc($path)) . ' KLOC',
            'Activity Level'   => $this->getActivityScore($path),
        ];

        foreach ($details as $key => $value) {
            $this->components->twoColumnDetail($key, $value);
        }

        // Git information
        if (File::exists($path . '/.git')) {
            $this->newLine();
            $this->components->info('Git Information:');
            $this->printGitInfo($path);
        }
    }

    private function chapterTwo(string $path): void
    {
        $this->newLine();
        $this->components->info('Chapter 2: Application Structure 🏗️');

        $stats = [
            'Controllers'  => $this->getFileCount($path . '/app/Http/Controllers'),
            'Models'       => $this->getFileCount($path . '/app/Models'),
            'Migrations'   => $this->getFileCount($path . '/database/migrations'),
            'Routes Files' => count(File::files($path . '/routes')),
            'Views'        => $this->getFileCount($path . '/resources/views'),
            'Tests'        => $this->getFileCount($path . '/tests'),
            'Config Files' => $this->getFileCount($path . '/config'),
            'Commands'     => $this->getFileCount($path . '/app/Console/Commands'),
        ];

        foreach ($stats as $type => $count) {
            $this->components->twoColumnDetail($type, (string)$count);
        }

        // Identify patterns
        $this->newLine();
        $this->identifyPatterns($path);

        // Recent activity
        $this->newLine();
        $this->components->info('Recent File Changes:');
        foreach ($this->getRecentlyModifiedFiles($path) as $file) {
            $this->components->bulletList([$file]);
        }
    }

    private function chapterThree(string $path): void
    {
        $this->newLine();
        $this->components->info('Chapter 3: Dependencies and Tech Stack 🛠️');

        $composer = json_decode(File::get($path . '/composer.json'), true);

        $this->components->twoColumnDetail(
            'Laravel Version',
            $this->getLaravelVersion($composer)
        );

        $this->components->twoColumnDetail(
            'PHP Version',
            $composer['require']['php'] ?? 'Unknown'
        );

        // Notable packages
        $this->newLine();
        $this->components->info('Key Packages:');
        collect($composer['require'] ?? [])
            ->filter(fn($version, $package) => !str_starts_with($package, 'php') &&
                !str_starts_with($package, 'laravel/framework')
            )
            ->take(10)
            ->each(fn($version, $package) => $this->components->bulletList(["$package: $version"])
            );
    }

    private function chapterFour(string $path): void
    {
        $this->newLine();
        $this->components->info('Chapter 4: Testing and Quality 🎯');

        $testStats = [
            'Total Tests'     => $this->countTests($path),
            'Feature Tests'   => $this->architect->countTestsInDirectory($path . '/tests/Feature'),
            'Unit Tests'      => $this->architect->countTestsInDirectory($path . '/tests/Unit'),
            'Test Suite'      => $this->architect->identifyTestFramework($path),
            'Code Style'      => $this->identifyCodeStyle($path),
            'Static Analysis' => $this->hasStaticAnalysis($path) ? 'Configured ✅' : 'Not configured',
        ];

        foreach ($testStats as $key => $value) {
            $this->components->twoColumnDetail($key, $value);
        }
    }

    private function chapterFive(string $path, array $env): void
    {
        $this->newLine();
        $this->components->info('Chapter 5: Infrastructure Setup 🌐');

        $infrastructure = [
            'Queue System'   => $env['QUEUE_CONNECTION'] ?? 'sync',
            'Cache Driver'   => $env['CACHE_STORE'] ?? 'file',
            'Session Driver' => $env['SESSION_DRIVER'] ?? 'file',
            'Database'       => $env['DB_CONNECTION'] ?? 'mysql',
            'Mail Driver'    => $env['MAIL_MAILER'] ?? 'smtp',
            'Filesystem'     => $env['FILESYSTEM_DISK'] ?? 'local',
            'Broadcasting'   => $env['BROADCAST_DRIVER'] ?? 'log',
        ];

        foreach ($infrastructure as $key => $value) {
            $this->components->twoColumnDetail($key, $value);
        }

        // Third-party services detection
        $this->newLine();
        $this->components->info('Detected Services:');
        $this->detectThirdPartyServices($env);
    }

    private function identifyPatterns(string $path): void
    {
        $patterns = [];

        $directories = [
            'Services'            => 'Service Layer',
            'Repositories'        => 'Repository Pattern',
            'Actions'             => 'Action Pattern',
            'DataTransferObjects' => 'DTO Pattern',
            'Presenters'          => 'Presenter Pattern',
            'Policies'            => 'Policy Pattern',
            'Events'              => 'Event-Driven Architecture',
            'Jobs'                => 'Queue-based Processing',
            'ViewModels'          => 'View Model Pattern',
        ];

        foreach ($directories as $dir => $pattern) {
            if (File::exists($path . '/app/' . $dir)) {
                $patterns[] = "$pattern detected";
            }
        }

        if (!empty($patterns)) {
            $this->components->bulletList($patterns);
        } else {
            $this->line('No specific patterns detected');
        }
    }

    private function detectThirdPartyServices(array $env): void
    {
        $services = [];

        // AWS
        if (!empty($env['AWS_ACCESS_KEY_ID'])) {
            $services[] = 'AWS Integration';
        }

        // Redis
        if (!empty($env['REDIS_HOST'])) {
            $services[] = 'Redis';
        }

        // Slack
        if (!empty($env['SLACK_BOT_USER_OAUTH_TOKEN'])) {
            $services[] = 'Slack Integration';
        }

        // Other services you detected
        if (!empty($env['GOOGLE_MAP_API_KEY'])) {
            $services[] = 'Google Maps';
        }

        if (!empty($services)) {
            $this->components->bulletList($services);
        } else {
            $this->line('No third-party services detected');
        }
    }

    private function getFileCount(string $directory): int
    {
        if (!File::exists($directory)) {
            return 0;
        }
        return count(File::allFiles($directory));
    }

    private function getRecentlyModifiedFiles(string $path, int $limit = 5): array
    {
        try {
            return collect(File::allFiles($path))
                ->sortByDesc(fn($file) => $file->getMTime())
                ->take($limit)
                ->map(fn($file) => $file->getRelativePathname() .
                    ' (modified ' . date('Y-m-d', $file->getMTime()) . ')')
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    private function isLaravelProject(string $path): bool
    {
        return File::exists($path . '/artisan') &&
            File::exists($path . '/composer.json');
    }

    private function getFormattedSize(string $path): string
    {
        $bytes = $this->getDirSize($path);
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getDirSize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function getKloc(string $path): float
    {
        $finder = new Finder();
        $kloc   = 0;

        try {
            $finder->files()
                ->in($path)
                ->name('*.php')
                ->notPath('vendor')    // Exclude vendor directory
                ->notPath('node_modules');  // Exclude node_modules if present

            foreach ($finder as $file) {
                $fileObj = new SplFileObject($file->getRealPath());
                $fileObj->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY);

                $lineCount = 0;
                while (!$fileObj->eof()) {
                    $line = $fileObj->fgets();
                    // Skip comment-only lines
                    if (!preg_match('/^\s*(\/\/|\/\*|\*|\*\/|#)\s*$/', $line)) {
                        $lineCount++;
                    }
                }
                $kloc += $lineCount;
            }

            return round($kloc / 1000, 2);
        } catch (Exception $e) {
            throw new RuntimeException("Error analyzing directory: " . $e->getMessage());
        }
    }

    private function getActivityScore(string $path): string
    {
        $recentlyModified = count(array_filter(
            File::allFiles($path),
            fn($file) => $file->getMTime() > strtotime('-7 days')
        ));

        if ($recentlyModified > 100) return 'Very Active 🔥';
        if ($recentlyModified > 50) return 'Active 👍';
        if ($recentlyModified > 10) return 'Moderately Active 👌';
        return 'Low Activity 😴';
    }

    private function getLaravelVersion(array $composer): string
    {
        return $composer['require']['laravel/framework']
            ?? $composer['require']['illuminate/support']
            ?? 'Unknown';
    }

    private function isInMaintenanceMode(string $path): bool
    {
        return File::exists($path . '/storage/framework/down');
    }

    private function countTests(string $path): int
    {
        return $this->architect->countTestsInDirectory($path . '/tests');
    }




    private function identifyCodeStyle(string $path): string
    {
        $composer = json_decode(File::get($path . '/composer.json'), true);

        $styles = [];
        if (isset($composer['require-dev']['laravel/pint'])) {
            $styles[] = 'Pint';
        }
        if (File::exists($path . '/.php-cs-fixer.php')) {
            $styles[] = 'PHP CS Fixer';
        }
        if (File::exists($path . '/phpcs.xml')) {
            $styles[] = 'PHP_CodeSniffer';
        }

        return !empty($styles) ? implode(', ', $styles) : 'Not configured';
    }


    private function hasStaticAnalysis(string $path): bool
    {
        $composer = json_decode(File::get($path . '/composer.json'), true);

        return isset($composer['require-dev']['phpstan/phpstan']) ||
            isset($composer['require-dev']['larastan/larastan']) ||
            File::exists($path . '/phpstan.neon') ||
            File::exists($path . '/phpstan.neon.dist');
    }

    private function printGitInfo(string $path): void
    {
        try {
            $path = escapeshellarg($path);
            // Get current branch
            $branch = trim(shell_exec('cd ' . $path . ' && git branch --show-current'));
            $this->components->twoColumnDetail('Current Branch', $branch);

            // Get last commit
            $lastCommit = trim(shell_exec('cd ' . $path . ' && git log -1 --pretty=%B'));
            $this->components->twoColumnDetail('Last Commit', Str::limit($lastCommit, 50));

            // Get number of contributors
            $contributors = count(array_filter(array_unique(explode("\n",
                shell_exec('cd ' . $path . ' && git log --format="%aE" | sort -u')
            ))));
            $this->components->twoColumnDetail('Contributors', (string)$contributors);

            // Get total commits
            $totalCommits = trim(shell_exec('cd ' . $path . ' && git rev-list --count HEAD'));
            $this->components->twoColumnDetail('Total Commits', $totalCommits);

            // Get repository status
            $status = trim(shell_exec('cd ' . $path . ' && git status --porcelain'));
            $this->components->twoColumnDetail(
                'Working Directory',
                empty($status) ? 'Clean ✨' : 'Has Changes ⚠️'
            );

        } catch (Exception $e) {
            $this->warn('Could not fetch complete Git information');
        }
    }

    private function generateSummary(array $env = []): void
    {
        $this->newLine(2);
        $this->components->info('Executive Summary 📊');

        $strengths   = [];
        $suggestions = [];

        // Add strengths based on findings
        if ($this->hasStaticAnalysis(getcwd())) {
            $strengths[] = 'Static analysis is configured, showing commitment to code quality';
        }

        if ($this->architect->identifyTestFramework(getcwd()) !== 'Not identified') {
            $strengths[] = 'Testing framework is in place';
        }

        if (isset($env['QUEUE_CONNECTION']) && $env['QUEUE_CONNECTION'] !== 'sync') {
            $strengths[] = 'Queue system is properly configured for background processing';
        }

        // Add suggestions based on findings
        if (!$this->hasStaticAnalysis(getcwd())) {
            $suggestions[] = 'Consider adding static analysis with PHPStan/Larastan';
        }

        if ($this->countTests(getcwd()) < 10) {
            $suggestions[] = 'Test coverage appears low, consider adding more tests';
        }

        if (isset($env['APP_DEBUG']) && $env['APP_DEBUG'] === 'true') {
            $suggestions[] = 'Debug mode is enabled, remember to disable in production';
        }

        if (!empty($strengths)) {
            $this->newLine();
            $this->components->info('Project Strengths:');
            $this->components->bulletList($strengths);
        }

        if (!empty($suggestions)) {
            $this->newLine();
            $this->components->info('Suggestions for Improvement:');
            $this->components->bulletList($suggestions);
        }
    }

    private function scanForSecurityIssues(string $path, array $env): array
    {
        $issues = [];

        // Check for debug mode in non-local environments
        if (($env['APP_ENV'] ?? 'local') !== 'local' && ($env['APP_DEBUG'] ?? 'false') === 'true') {
            $issues[] = 'Debug mode is enabled in non-local environment';
        }

        // Check for common security files exposure
        $sensitiveFiles = ['.env.backup', '.env.old', 'storage/logs/laravel.log'];
        foreach ($sensitiveFiles as $file) {
            if (File::exists($path . '/' . $file)) {
                $issues[] = "Sensitive file exposed: {$file}";
            }
        }

        // Check composer.json for security-related packages
        $composer = json_decode(File::get($path . '/composer.json'), true);
        if (!isset($composer['require']['illuminate/encryption'])) {
            $issues[] = 'Encryption package not found in dependencies';
        }

        return $issues;
    }
}
