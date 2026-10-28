<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

/**
 * weld:doctor strikes an arc across the setup and reports every joint that
 * is not holding: the PHP version, the app key, debug in production, secure
 * session cookies, writable storage, the database and pending migrations.
 *
 * One line per check: PASS, WARN or FAIL. The exit code is 1 when any check
 * FAILs and 0 otherwise. --json hands the same report to a machine instead
 * of a human. A good bead is a quiet one: all PASS and nothing to say.
 */
class DoctorCommand extends Command
{
    protected $signature = 'weld:doctor {--json : Print the report as JSON instead of lines}';

    protected $description = 'Check that this WELD app is set up safely';

    public function handle(): int
    {
        $checks = [
            'php-version' => $this->checkPhpVersion(),
            'app-key' => $this->checkAppKey(),
            'debug-in-production' => $this->checkDebugInProduction(),
            'session-secure' => $this->checkSessionSecure(),
            'storage-writable' => $this->checkStorageWritable(),
            'database' => $this->checkDatabase(),
            'migrations' => $this->checkMigrations(),
        ];

        $results = [];

        foreach ($checks as $id => [$status, $message]) {
            $results[] = ['id' => $id, 'status' => $status, 'message' => $message];

            if (! $this->option('json')) {
                $this->line(sprintf('%-4s  %s  %s', $status, $id, $message));
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return collect($results)->contains('status', 'FAIL') ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkPhpVersion(): array
    {
        $version = PHP_VERSION;

        // @phpstan-ignore smaller.alwaysFalse (composer.json pins PHP ^8.3, but doctor reports honestly on an older runtime)
        if (PHP_VERSION_ID < 80300) {
            return ['FAIL', 'PHP '.$version.' is below the 8.3 floor'];
        }

        return ['PASS', 'PHP '.$version.' meets the 8.3 floor'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkAppKey(): array
    {
        if (filled(config('app.key'))) {
            return ['PASS', 'APP_KEY is set'];
        }

        return ['FAIL', 'APP_KEY is empty; run php artisan key:generate'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkDebugInProduction(): array
    {
        if (! app()->environment('production')) {
            return ['PASS', 'environment is '.app()->environment().', not production'];
        }

        if (config('app.debug')) {
            return ['FAIL', 'APP_DEBUG is true in production'];
        }

        return ['PASS', 'APP_DEBUG is off in production'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkSessionSecure(): array
    {
        if (! app()->environment('production')) {
            return ['PASS', 'environment is '.app()->environment().', not production'];
        }

        if (config('session.secure') === true) {
            return ['PASS', 'session cookies are flagged secure'];
        }

        return ['WARN', 'SESSION_SECURE_COOKIE is not true in production'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkStorageWritable(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($paths as $label => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return ['FAIL', $label.' is not writable: '.$path];
            }
        }

        return ['PASS', 'storage and bootstrap/cache are writable'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            // The class name only: never the DSN, host or password.
            return ['FAIL', get_class($exception)];
        }

        return ['PASS', 'database connection is up'];
    }

    /**
     * @return array{0: string, 1: string} [status, message]
     */
    private function checkMigrations(): array
    {
        try {
            /** @var Migrator $migrator */
            $migrator = app()->make(Migrator::class);
            $files = $migrator->getMigrationFiles(app()->databasePath('migrations'));
            $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];

            $pending = array_values(array_diff(
                array_map(static fn (string $file): string => substr(basename($file), 0, -4), $files),
                $ran,
            ));
        } catch (\Throwable $exception) {
            return ['WARN', 'could not inspect migrations: '.get_class($exception)];
        }

        if ($pending === []) {
            return ['PASS', 'no pending migrations'];
        }

        return ['WARN', count($pending).' pending migration(s): '.implode(', ', $pending)];
    }
}
