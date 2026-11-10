<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Migrations run once per release (W24)
|--------------------------------------------------------------------------
|
| Two replicas that both run migrate at start race on the schema, and a
| failed migration crash-loops every one of them. WELD's deploy kit runs
| migrations in exactly one place per path: on Citadel, the release script
| (the first-release hook and the Quick Update post-script both call it);
| off Citadel, the portable image's migrate role, a one-shot service the
| web, worker and scheduler roles wait for. This file reads WELD's copy of
| Squire's kit and fails when an update moves migrate anywhere else.
| Measure twice, migrate once.
*/

function weldDeployFile(string $relative): string
{
    $path = dirname(__DIR__, 2).'/deploy/'.$relative;
    expect(is_file($path))->toBeTrue("deploy/{$relative} is missing");

    return (string) file_get_contents($path);
}

it('runs migrate once on Citadel, from the release script and nowhere else', function () {
    $release = weldDeployFile('scripts/release.sh');
    expect(substr_count($release, 'php artisan migrate --force'))->toBe(1);
    expect($release)->toContain('if [ "$SQUIRE_MIGRATE" = "1" ]; then');

    // The first-release hook hands over to the release script rather than migrating itself.
    expect(weldDeployFile('deployments/001-first-release.sh'))
        ->toContain('scripts/release.sh')
        ->not->toContain('artisan migrate');

    // Citadel's container entrypoint and the image build run no artisan command at all.
    expect(weldDeployFile('.squire/fallback/citadel-apache-entrypoint'))->not->toContain('artisan');
    expect(weldDeployFile('build.sh'))->not->toContain('artisan migrate');
});

it('runs migrate in the portable image only for the migrate role', function () {
    $entrypoint = weldDeployFile('.squire/portable/squire-entrypoint.sh');

    // Find the body of run_migrate(): from its opening line to the first closing brace at column 0.
    expect(preg_match('/^run_migrate\(\) \{\n(.*?)^\}$/ms', $entrypoint, $body))->toBe(1);
    expect($body[1])->toContain('exec php artisan migrate --force "$@"');

    // Every other line of the entrypoint leaves migrate alone.
    $outside = str_replace($body[1], '', $entrypoint);
    foreach (explode("\n", $outside) as $number => $line) {
        if (str_starts_with(ltrim($line), '#')) {
            continue;
        }
        expect($line)->not->toMatch('/artisan\s+migrate/', 'squire-entrypoint.sh line '.($number + 1).' runs migrate outside the migrate role');
    }

    // run_migrate is reached only through the migrate role.
    preg_match_all('/^.*\brun_migrate\b.*$/m', $entrypoint, $lines);
    expect(array_map('trim', $lines[0]))->toBe(['run_migrate() {', 'migrate) run_migrate "$@" ;;']);
});

it('starts web, worker and scheduler only after the one-shot migrate service succeeds', function () {
    $compose = weldDeployFile('docker-compose.squire.yml');

    expect(preg_match('/^  migrate:\n(.*?)(?=^  [a-z]+:\n)/ms', $compose, $migrate))->toBe(1);
    expect($migrate[1])
        ->toContain('SQUIRE_ROLE: migrate')
        ->toContain('restart: "no"');

    foreach (['web', 'worker', 'scheduler'] as $role) {
        expect(preg_match('/^  '.$role.':\n(.*?)(?=^  [a-z]+:\n|^[a-z]+:\n|\z)/ms', $compose, $service))->toBe(1, "no {$role} service");
        expect($service[1])
            ->toContain("SQUIRE_ROLE: {$role}")
            ->toMatch('/depends_on:\n\s+migrate:\n\s+condition: service_completed_successfully/');
    }
});
