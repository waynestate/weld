<?php

declare(strict_types=1);

/*
 * cast-versions.php: the front page and the README name every part's latest
 * release by hand, so they drift on every tag (11-19: CREED, SESAME,
 * Preflight). These tests run the tool against copies of site/index.html and
 * README.md with the fixture tags file: the rewrite, an idempotent second
 * run, the --check exit codes and the pins-them-at sentence it must never
 * touch. The committed page is left alone; the lead runs the real regen.
 */

function castVerRoot(): string
{
    return dirname(__DIR__, 2);
}

function castVerTempDir(): string
{
    $dir = sys_get_temp_dir().'/cast-versions-'.bin2hex(random_bytes(8));
    mkdir($dir, 0755, true);

    return $dir;
}

function castVerRemoveDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

/** Copy the front page and the README into a temp dir the tool may rewrite. */
function castVerStage(): string
{
    $dir = castVerTempDir();
    copy(castVerRoot().'/site/index.html', $dir.'/index.html');
    copy(castVerRoot().'/README.md', $dir.'/README.md');

    return $dir;
}

function castVerTags(): array
{
    $tags = json_decode((string) file_get_contents(castVerRoot().'/tests/fixtures/cast-tags.json'), true);
    expect(is_array($tags))->toBeTrue('the fixture tags file must decode to a JSON object');

    return $tags;
}

function castVerRun(string $dir, array $extra = []): array
{
    $args = array_merge(
        ['--tags', castVerRoot().'/tests/fixtures/cast-tags.json', '--site', $dir.'/index.html', '--readme', $dir.'/README.md'],
        $extra,
    );
    $command = PHP_BINARY.' '.escapeshellarg(castVerRoot().'/bin/cast-versions.php').' '.implode(' ', array_map('escapeshellarg', $args)).' 2>&1';
    exec($command, $output, $code);

    return [$code, implode("\n", $output)];
}

function castVerLogRows(string $html): array
{
    $rows = [];
    preg_match_all('#<li class="joint[^"]*"[^>]*>(.*?)</li>#s', $html, $matches);
    foreach ($matches[1] as $row) {
        if (preg_match('#<h3 class="joint__name">\s*([^<]+?)\s*</h3>#', $row, $name) === 1
            && preg_match('#<p class="joint__rel"><span class="wsu-sr-only">Release </span>([^<]+)</p>#', $row, $version) === 1) {
            $rows[trim($name[1])] = $version[1];
        }
    }

    return $rows;
}

function castVerCastCards(string $html): array
{
    $cards = [];
    preg_match_all('#<article class="cast-card[^"]*"[^>]*>(.*?)</article>#s', $html, $matches);
    foreach ($matches[1] as $card) {
        if (preg_match('#<a class="cast-card__link"[^>]*>\s*([^<]+?)\s*</a>#', $card, $name) === 1
            && preg_match('#<span class="chip chip--version">([^<]+)</span>#', $card, $version) === 1) {
            $cards[trim($name[1])] = $version[1];
        }
    }

    return $cards;
}

it('rewrites the weld log, the cast cards and the README list from the tags', function (): void {
    $dir = castVerStage();
    $tags = castVerTags();

    try {
        [$code, $output] = castVerRun($dir);
        expect($code)->toBe(0, $output);

        $html = (string) file_get_contents($dir.'/index.html');
        foreach ($tags as $name => $version) {
            expect(castVerLogRows($html)[$name] ?? null)->toBe($version, $name.': the weld log still does not show '.$version);
        }
        foreach ($tags as $name => $version) {
            expect(castVerCastCards($html)[$name] ?? null)->toBe($version, $name.': the cast card still does not show '.$version);
        }

        $readme = (string) file_get_contents($dir.'/README.md');
        expect(preg_match('#\*\*Current versions\*\* \(checked [0-9]{4}-[0-9]{2}-[0-9]{2}\): (.+?)\. These are the latest releases#', $readme, $list))
            ->toBe(1, 'the README "Current versions" list is gone');
        $shown = [];
        foreach (explode(', ', $list[1]) as $pair) {
            [$name, $version] = array_pad(explode(' ', $pair, 2), 2, '');
            $shown[$name] = $version;
        }
        // Every part the list names must now show its fixture tag; parts the
        // list does not name (Sourdough, Booster, Rollcall, Drawbridge) stay out.
        expect(count($shown))->toBeGreaterThan(0);
        foreach ($shown as $name => $version) {
            expect($version)->toBe($tags[$name], $name.': the README list still does not show '.$tags[$name]);
        }

        // WELD's own row is the docs builder's (data-docs-version), not this
        // tool's: whatever release the page shows, the run leaves it as it was.
        expect(preg_match('#<span data-docs-version>(\d+\.\d+\.\d+)</span>#', (string) file_get_contents(castVerRoot().'/site/index.html'), $own))
            ->toBe(1, 'the committed front page has no data-docs-version label');
        expect($html)->toContain('<span data-docs-version>'.$own[1].'</span>');
    } finally {
        castVerRemoveDir($dir);
    }
});

it('is idempotent: a second run changes nothing', function (): void {
    $dir = castVerStage();

    try {
        [$firstCode, $firstOutput] = castVerRun($dir);
        expect($firstCode)->toBe(0, $firstOutput);
        $afterFirst = [md5_file($dir.'/index.html'), md5_file($dir.'/README.md')];

        [$secondCode, $secondOutput] = castVerRun($dir);
        expect($secondCode)->toBe(0, $secondOutput);
        expect([md5_file($dir.'/index.html'), md5_file($dir.'/README.md')])->toBe($afterFirst, 'the second run rewrote the files');
        expect($secondOutput)->toContain('nothing to update');
    } finally {
        castVerRemoveDir($dir);
    }
});

it('exits 1 from --check while the labels are stale, listing each stale label', function (): void {
    $dir = castVerStage();
    $tags = castVerTags();

    try {
        [$code, $output] = castVerRun($dir, ['--check']);
        expect($code)->toBe(1, $output);
        foreach (['CREED', 'PROCTOR', 'Preflight', 'SESAME'] as $name) {
            expect($output)->toContain($name.': shown ');
            expect($output)->toContain($tags[$name]);
        }
        // --check writes nothing.
        expect(md5_file($dir.'/index.html'))->toBe(md5_file(castVerRoot().'/site/index.html'));
        expect(md5_file($dir.'/README.md'))->toBe(md5_file(castVerRoot().'/README.md'));
    } finally {
        castVerRemoveDir($dir);
    }
});

it('exits 0 from --check once the rewrite has caught the labels up', function (): void {
    $dir = castVerStage();

    try {
        [$firstCode] = castVerRun($dir);
        expect($firstCode)->toBe(0);

        [$code, $output] = castVerRun($dir, ['--check']);
        expect($code)->toBe(0, $output);
    } finally {
        castVerRemoveDir($dir);
    }
});

it('never touches the pins-them-at sentence or the README pinning note', function (): void {
    $dir = castVerStage();

    try {
        [$code, $output] = castVerRun($dir);
        expect($code)->toBe(0, $output);

        $html = (string) file_get_contents($dir.'/index.html');
        $readme = (string) file_get_contents($dir.'/README.md');

        expect($html)->toContain("WELD's pipeline pins them at 0.12.0 for the tests on PHP 8.3, 8.4 and 8.5");
        expect($readme)->toContain('These are the latest releases, not what this repo pins: CI and `deploy/.squire.json` hold those.');
    } finally {
        castVerRemoveDir($dir);
    }
});
