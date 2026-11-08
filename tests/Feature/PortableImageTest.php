<?php

/*
|--------------------------------------------------------------------------
| The portable image: one image, several process types, any UID in group 0
|--------------------------------------------------------------------------
|
| Off Citadel (ADR-0010) WELD runs from Squire's portable image,
| deploy/.squire/portable.Dockerfile: the code baked in, run as web,
| worker, scheduler or migrate by SQUIRE_ROLE (W8), as USER 10001:0 with
 | group-0 writable folders, Apache on 8080 (W13), a graceful stop on
 | SIGTERM (W16), and a vendor/ with no dev packages (W29). The kit is
 | Squire's byte for byte (squire init --check), and Squire's
 | PortableImageTest runs the entrypoint itself; this file reads WELD's
 | copy and fails when a kit update drops a role, the user or the port.
 | The Citadel mirror next to it must not change.
| One image, four hats: the welder, the night shift, the foreman's
| clipboard and the inspector who signs off once.
*/

function weldKitFile(string $relative): string
{
    $path = dirname(__DIR__, 2).'/deploy/'.$relative;

    expect(is_file($path))->toBeTrue("deploy/{$relative} is missing; the deploy kit lost part of the portable image");

    return str_replace("\r\n", "\n", (string) file_get_contents($path));
}

it('runs one image as web, worker, scheduler and migrate (W8)', function () {
    $compose = weldKitFile('docker-compose.squire.yml');
    $entrypoint = weldKitFile('.squire/portable/squire-entrypoint.sh');

    expect($compose)
        ->toContain('image: squire-local/portable:${SQUIRE_PROJECT:-squire-app}')
        ->toContain('profiles: [portable]');
    foreach (['migrate', 'web', 'worker', 'scheduler'] as $role) {
        expect($compose)->toContain("SQUIRE_ROLE: {$role}");
    }

    expect($entrypoint)
        ->toContain('exec php artisan queue:work --tries=3 --max-time=3600 --sleep=3 "$@"')
        ->toContain('exec php artisan schedule:work "$@"')
        ->toContain('exec php artisan migrate --force "$@"');
});

it('leaves the queue to the worker service, not the scheduler', function () {
    // W19's short scheduled worker is for Citadel; beside a real worker it would run the queue twice over.
    expect(weldKitFile('docker-compose.squire.yml'))->toContain('QUEUE_WORKER_MODE: none');
    expect((string) file_get_contents(dirname(__DIR__, 2).'/config/worker.php'))->toContain('QUEUE_WORKER_MODE');
});

it('runs as a numeric non-root user in group 0 on port 8080 (W13)', function () {
    $dockerfile = weldKitFile('.squire/portable.Dockerfile');

    expect($dockerfile)
        ->toContain("USER 10001:0\n")
        ->toContain("EXPOSE 8080\n")
        ->toContain('chgrp -R 0 "$dir";')
        ->toContain('chmod -R g=u,ug+rwX "$dir";')
        ->toContain('dirs="storage bootstrap/cache"')
        ->toContain("STOPSIGNAL SIGTERM\n")
        ->not->toContain('www-data');

    expect(weldKitFile('.squire/portable/ports.conf'))->toContain('Listen ${SQUIRE_PORT}');
    expect(weldKitFile('.squire/portable/vhost.conf'))->toContain('<VirtualHost *:${SQUIRE_PORT}>');
    expect(weldKitFile('.squire/portable/squire-entrypoint.sh'))->toContain('local port="${SQUIRE_PORT:-${PORT:-8080}}"');
});

it('caches config when a container starts, never at image build (W10)', function () {
    $dockerfile = weldKitFile('.squire/portable.Dockerfile');

    expect($dockerfile)->toContain('SQUIRE_ARTISAN_OPTIMIZE=0')->toContain('SQUIRE_MIGRATE=0');
    expect(weldKitFile('.squire/portable/squire-entrypoint.sh'))->toContain('php artisan optimize >&2');
});

it('keeps the Citadel mirror a mirror', function () {
    $citadel = weldKitFile('.squire/Dockerfile');

    expect($citadel)
        ->toContain('CMD ["apache2-foreground"]')
        ->not->toContain('portable')
        ->not->toMatch('/^USER /m');
    expect(weldKitFile('.squire/vhost.conf'))->toContain('<VirtualHost *:80>');
});

it('builds vendor/ without dev packages, so Boost never reaches the image (W29)', function () {
    $dockerfile = weldKitFile('.squire/portable.Dockerfile');
    $dockerignore = weldKitFile('.squire/portable.Dockerfile.dockerignore');
    $releaseScript = weldKitFile('scripts/release.sh');

    // The build context leaves vendor/ out, so the host's dev vendor/ never reaches a layer.
    expect(str_contains($dockerignore, "# Rebuilt by the image's release step.\nvendor\n"))
        ->toBeTrue('portable.Dockerfile.dockerignore no longer keeps vendor/ out of the build context; the host dev vendor/ could reach a layer');

    // The image builds vendor/ through scripts/release.sh, and no other line runs Composer.
    expect(str_contains($dockerfile, 'RUN --mount=type=cache,target=/tmp/squire-cache \\'))
        ->toBeTrue('portable.Dockerfile lost its cache-mounted release step; the image would no longer build vendor/ through release.sh');
    expect(str_contains($dockerfile, 'bash "/citadel/src/${SQUIRE_KIT_DIR}/scripts/release.sh"'))
        ->toBeTrue('portable.Dockerfile no longer builds vendor/ through scripts/release.sh; the image would bake in whatever the host had');
    $directComposer = [];
    foreach (explode("\n", $dockerfile) as $line) {
        if (! str_starts_with(ltrim($line), '#') && preg_match('/\bcomposer\s+(install|require)\b/', $line) === 1) {
            $directComposer[] = $line;
        }
    }
    expect($directComposer)->toBe([], 'portable.Dockerfile runs composer install or require outside release.sh; that could pull dev packages into the image');

    // Every composer install in release.sh carries --no-dev.
    $installs = [];
    foreach (explode("\n", $releaseScript) as $line) {
        if (! str_starts_with(ltrim($line), '#') && preg_match('/^\s*(?:[A-Za-z_][A-Za-z0-9_]*=\S+\s+)*composer\s+install\b/', $line) === 1) {
            $installs[] = $line;
        }
    }
    expect(count($installs))->toBeGreaterThan(0, 'release.sh no longer runs composer install; the portable image would have no vendor/ at all');
    foreach ($installs as $line) {
        expect(str_contains($line, '--no-dev'))
            ->toBeTrue("release.sh runs composer install without --no-dev ({$line}); dev packages like Boost would reach the image");
    }
});
