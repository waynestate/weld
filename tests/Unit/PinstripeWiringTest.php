<?php

declare(strict_types=1);

use Nette\Neon\Neon;

/*
|--------------------------------------------------------------------------
| Pinstripe is installed, not copied
|--------------------------------------------------------------------------
|
| WELD requires waynestate/pinstripe in require-dev (W32). PHPStan includes
| Pinstripe's Laravel preset, so the level, the ignore hygiene and the
| banned calls (eval, dd(), TLS checks turned off, SQL joined from strings)
| come from the package and move when it moves. Pint keeps a local
| pint.json only because Pint cannot extend another config file and two
| folders here are Squire's code, not ours: the file must stay Pinstripe's
| laravel.json plus those recorded excludes, and nothing else. A suit off
| the rack, taken in at exactly two seams.
*/

function pinstripeRoot(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @return array<string, mixed>
 */
function pinstripeJson(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    expect($decoded)->toBeArray();

    return $decoded;
}

/**
 * Pint excludes WELD adds on top of Pinstripe's laravel.json, each with its reason.
 *
 * @var array<string, string>
 */
const WELD_PINT_EXTRA_EXCLUDES = [
    // Squire's deploy kit, vendored in deploy/: restyling it turns every kit update into a conflict.
    'deploy' => 'Squire deploy kit',
    // Squire's shared docs builder, vendored as one file and updated by copying it again.
    'bin/docs-site.php' => 'Squire docs builder',
];

it('requires Pinstripe as a dev dependency', function () {
    $composer = pinstripeJson(pinstripeRoot().'/composer.json');

    expect($composer['require-dev'] ?? [])->toHaveKey('waynestate/pinstripe')
        ->and($composer['require'] ?? [])->not->toHaveKey('waynestate/pinstripe')
        ->and($composer['config']['allow-plugins']['phpstan/extension-installer'] ?? null)->toBeTrue();
});

it('includes the Laravel preset and leaves its settings to Pinstripe', function () {
    $neon = Neon::decodeFile(pinstripeRoot().'/phpstan.neon');
    expect($neon)->toBeArray();

    $parameters = $neon['parameters'] ?? [];
    expect($neon['includes'] ?? [])->toBe(['vendor/waynestate/pinstripe/phpstan/laravel.neon'])
        ->and($parameters)->not->toHaveKey('level')
        ->and($parameters)->not->toHaveKey('reportIgnoresWithoutComments')
        ->and($parameters)->not->toHaveKey('reportUnmatchedIgnoredErrors')
        ->and($parameters)->not->toHaveKey('disallowedFunctionCalls')
        ->and($parameters)->not->toHaveKey('disallowedMethodCalls');
});

it('keeps pint.json to Pinstripe\'s Laravel preset plus the recorded excludes', function () {
    $preset = pinstripeRoot().'/vendor/waynestate/pinstripe/pint/laravel.json';
    if (! is_file($preset)) {
        $this->markTestSkipped('Pinstripe is not installed: run composer install first.');
    }

    $pinstripe = pinstripeJson($preset);
    $weld = pinstripeJson(pinstripeRoot().'/pint.json');

    /** @var list<string> $theirs */
    $theirs = $pinstripe['exclude'] ?? [];
    /** @var list<string> $ours */
    $ours = $weld['exclude'] ?? [];

    $withoutExclude = static fn (array $config): array => array_diff_key($config, ['exclude' => true]);

    expect($withoutExclude($weld))->toBe($withoutExclude($pinstripe))
        ->and(array_values(array_diff($theirs, $ours)))->toBe([])
        ->and(array_values(array_diff($ours, $theirs)))->toEqualCanonicalizing(array_keys(WELD_PINT_EXTRA_EXCLUDES));
});

it('locks Pinstripe as a dev package', function () {
    if (! is_file(pinstripeRoot().'/composer.lock')) {
        $this->markTestSkipped('No composer.lock yet: run composer install first.');
    }

    $lock = pinstripeJson(pinstripeRoot().'/composer.lock');

    expect(array_column($lock['packages-dev'] ?? [], 'name'))->toContain('waynestate/pinstripe')
        ->and(array_column($lock['packages'] ?? [], 'name'))->not->toContain('waynestate/pinstripe');
});
