<?php

// The local stack that `bin/weld.ps1 up` and `bin/weld.sh up` bring up. Both
// bugs below were found by the 2026-11-06 proof run (docs/reference/proof.md),
// where a fresh clone on Windows loaded every page blank and a second `up`
// emptied the local database. A weld that holds once is a tack weld.

$root = dirname(__DIR__, 2);

it('creates the SQLite file only when it is missing on Windows', function () use ($root) {
    $script = (string) file_get_contents($root.'/bin/weld.ps1');

    // New-Item -Force on an existing file truncates it to zero bytes.
    foreach (preg_split('/\R/', $script) ?: [] as $line) {
        if (str_contains($line, 'database.sqlite') && str_contains($line, 'New-Item')) {
            expect($line)->not->toContain('-Force');
        }
    }

    expect($script)->toContain("if (-not (Test-Path -LiteralPath 'database/database.sqlite'))");
});

it('creates the SQLite file without truncating it on macOS and Linux', function () use ($root) {
    $script = (string) file_get_contents($root.'/bin/weld.sh');

    expect($script)->toContain('touch database/database.sqlite')
        ->and($script)->not->toMatch('/>\s*database\/database\.sqlite/');
});

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
