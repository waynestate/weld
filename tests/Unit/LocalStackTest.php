<?php

// The local stack that `bin/weld.sh test` and `bin/weld.sh doctor` still run,
// plus the Parity pointers (D-087): `up`, `down` and `logs` print the parity
// commands that replace them and exit 2. The last test guards the compose Vite
// bug found by the 2026-11-06 proof run (docs/reference/proof.md), where a
// fresh clone loaded every page blank. A weld that holds once is a tack weld.

$root = dirname(__DIR__, 2);

it('points up, down and logs at Parity in both scripts', function () use ($root) {
    foreach (['bin/weld.sh', 'bin/weld.ps1'] as $path) {
        $script = (string) file_get_contents($root.'/'.$path);

        expect($script)->toContain('parity up')
            ->and($script)->toContain('parity down')
            ->and($script)->toContain('parity logs -f')
            ->and($script)->toContain('D-087');
    }
});

it('carries none of the old up and down implementation', function () use ($root) {
    foreach (['bin/weld.sh', 'bin/weld.ps1'] as $path) {
        $script = (string) file_get_contents($root.'/'.$path);

        foreach (['database.sqlite', 'key:generate', 'public/hot', 'advisor', 'npm ci', 'compose up', 'compose down'] as $gone) {
            expect($script)->not->toContain($gone, $path.' still carries '.$gone);
        }
    }
});

it('still runs test and doctor in the app container', function () use ($root) {
    $sh = (string) file_get_contents($root.'/bin/weld.sh');
    $ps = (string) file_get_contents($root.'/bin/weld.ps1');

    expect($sh)->toContain('docker compose run --rm app composer ci')
        ->and($sh)->toContain('docker compose run --rm app php artisan weld:doctor')
        ->and($ps)->toContain("'composer', 'ci'")
        ->and($ps)->toContain("'weld:doctor'");
});

it('prints the Parity commands for up, down and logs, and exits 2', function () use ($root) {
    if (PHP_OS_FAMILY === 'Windows') {
        markTestSkipped('needs sh, which this Windows checkout does not have');
    }

    foreach ([['up', 'parity up'], ['down', 'parity down'], ['logs', 'parity logs -f']] as [$command, $parity]) {
        [$code, $out] = runWeldScript($root, $command);

        expect($code)->toBe(2, 'bin/weld.sh '.$command.' exited with '.$code.': '.$out)
            ->and($out)->toContain($parity);
    }
});

it('exits 2 on an unknown command', function () use ($root) {
    if (PHP_OS_FAMILY === 'Windows') {
        markTestSkipped('needs sh, which this Windows checkout does not have');
    }

    [$code, $out] = runWeldScript($root, 'bogus');

    expect($code)->toBe(2, 'bin/weld.sh bogus exited with '.$code.': '.$out);
});

/** Run `sh bin/weld.sh <command>` from the repository root; return [exit code, output]. */
function runWeldScript(string $root, string $command): array
{
    $process = proc_open(['sh', 'bin/weld.sh', $command], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (! is_resource($process)) {
        throw new RuntimeException('could not start sh');
    }

    $out = (string) stream_get_contents($pipes[1]).(string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $out];
}

it('points the browser at a Vite address it can reach', function () use ($root) {
    $compose = (string) file_get_contents($root.'/compose.yaml');

    expect(preg_match('/^  vite:\n(?<body>(?:    .*\n|\n)+)/m', $compose, $m))->toBe(1);
    // Judge the settings, not the comments that explain them.
    $vite = implode("\n", array_filter(
        explode("\n", $m['body']),
        fn (string $line): bool => ! str_starts_with(ltrim($line), '#'),
    ));

    // `--host` (or `--host 0.0.0.0`) makes laravel-vite-plugin write the
    // listen address, http://[::]:5173 or http://0.0.0.0:5173, to public/hot.
    // LARAVEL_SAIL makes it listen on 0.0.0.0 and write http://localhost:5173.
    expect($vite)->toMatch('/^    command: npm run dev$/m')
        ->and($vite)->not->toContain('--host')
        ->and($vite)->toMatch("/^      LARAVEL_SAIL: '1'$/m")
        ->and($vite)->toContain("'127.0.0.1:5173:5173'");
});
