<?php

namespace Tests\Feature;

use App\Console\Commands\OracleCheck;
use ReflectionMethod;

/*
 * Where oracle:check looks for Squire's check file: the app root in a plain
 * Squire layout, deploy/ in WELD, and the first existing copy wins. The app
 * is pointed at a temp directory with setBasePath(); nothing real moves.
 */

function locationTempDir(): string
{
    $dir = sys_get_temp_dir().'/weld-oracle-location-'.bin2hex(random_bytes(8));
    mkdir($dir, 0755, true);

    return $dir;
}

function removeLocationTempDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir.'/'.$entry;
        if (is_dir($path)) {
            removeLocationTempDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function resolvedCheckScriptPath(): string
{
    $method = new ReflectionMethod(OracleCheck::class, 'checkScriptPath');

    return $method->invoke(new OracleCheck);
}

it('finds the check file under deploy/ when only that copy exists', function () {
    $dir = locationTempDir();

    try {
        $this->app->setBasePath($dir);
        mkdir($dir.'/deploy', 0755, true);
        file_put_contents($dir.'/deploy/oracle-check.php', '<?php // stub');

        $this->assertSame(base_path('deploy/oracle-check.php'), resolvedCheckScriptPath());
    } finally {
        removeLocationTempDir($dir);
    }
});

it('finds the check file at the app root when only that copy exists', function () {
    $dir = locationTempDir();

    try {
        $this->app->setBasePath($dir);
        file_put_contents($dir.'/oracle-check.php', '<?php // stub');

        $this->assertSame(base_path('oracle-check.php'), resolvedCheckScriptPath());
    } finally {
        removeLocationTempDir($dir);
    }
});

it('prefers the app-root copy when both exist', function () {
    $dir = locationTempDir();

    try {
        $this->app->setBasePath($dir);
        file_put_contents($dir.'/oracle-check.php', '<?php // root stub');
        mkdir($dir.'/deploy', 0755, true);
        file_put_contents($dir.'/deploy/oracle-check.php', '<?php // deploy stub');

        $this->assertSame(base_path('oracle-check.php'), resolvedCheckScriptPath());
    } finally {
        removeLocationTempDir($dir);
    }
});

it('points at deploy/ when neither copy exists yet', function () {
    $dir = locationTempDir();

    try {
        $this->app->setBasePath($dir);

        $this->assertSame(base_path('deploy/oracle-check.php'), resolvedCheckScriptPath());
    } finally {
        removeLocationTempDir($dir);
    }
});
