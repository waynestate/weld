<?php

declare(strict_types=1);

/*
 * The front page names every part's release twice: once in the weld log and
 * once on its cast card. Both are typed by hand, so they drift, and on
 * 2026-11-10 the page still said PROCTOR 0.12.0 while WELD's lock held 0.15.0.
 * These tests keep the two lists in agreement, and keep every part WELD
 * carries at or ahead of the release WELD actually runs (the page shows each
 * project's latest tag, which can only be newer than WELD's pin). An
 * inspector who signs off on a joint should at least look at the bead.
 */

const FRONT_PAGE = __DIR__.'/../../site/index.html';

/** How to fix a failure, in one commit. */
const FRONT_PAGE_FIX = 'Fix it in site/index.html: give the part its latest tag in both the weld log (joint__rel) '
    .'and its cast card (chip--version), then run php bin/agent-ready.php so index.md and llms-full.txt follow.';

/**
 * The parts on the page, by name, with the release each list shows.
 *
 * @return array{log: array<string, string>, cast: array<string, string>}
 */
function frontPageReleases(): array
{
    $html = (string) file_get_contents(FRONT_PAGE);

    $log = [];
    preg_match_all('#<li class="joint[^"]*"[^>]*>(.*?)</li>#s', $html, $joints);
    foreach ($joints[1] as $joint) {
        if (preg_match('#<h3 class="joint__name">([^<]+)</h3>#', $joint, $name) !== 1
            || preg_match('#<p class="joint__rel">(.*?)</p>#s', $joint, $rel) !== 1) {
            continue;
        }
        $release = trim(strip_tags(str_replace('<span class="wsu-sr-only">Release </span>', '', $rel[1])));
        $log[trim($name[1])] = $release;
    }

    $cast = [];
    preg_match_all('#<article class="cast-card[^"]*"[^>]*>(.*?)</article>#s', $html, $cards);
    foreach ($cards[1] as $card) {
        if (preg_match('#<a class="cast-card__link"[^>]*>([^<]+)</a>#', $card, $name) !== 1
            || preg_match('#<span class="chip chip--version">([^<]+)</span>#', $card, $chip) !== 1) {
            continue;
        }
        $cast[trim($name[1])] = trim($chip[1]);
    }

    return ['log' => $log, 'cast' => $cast];
}

/**
 * The release WELD runs of each part it carries, read from the files that pin it.
 *
 * @return array<string, string>
 */
function weldRunsReleases(): array
{
    $root = __DIR__.'/../..';
    $lock = json_decode((string) file_get_contents($root.'/composer.lock'), true);
    $locked = [];
    foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        $locked[$package['name']] = ltrim((string) $package['version'], 'v');
    }

    $runs = [];
    foreach (['CREED' => 'creed', 'PROCTOR' => 'proctor', 'Pinstripe' => 'pinstripe', 'HELMET' => 'helmet',
        'SESAME' => 'sesame', 'ADVISOR' => 'advisor'] as $part => $package) {
        $runs[$part] = $locked['waynestate/'.$package] ?? 'not in composer.lock';
    }

    preg_match_all('#/preflight/[a-z0-9-]+@([0-9]+\.[0-9]+\.[0-9]+)#', (string) file_get_contents($root.'/.gitlab-ci.yml'), $pins);
    $tags = array_values(array_unique($pins[1]));
    usort($tags, 'version_compare');
    $runs['Preflight'] = $tags[0] ?? 'no Preflight component in .gitlab-ci.yml';

    $kit = json_decode((string) file_get_contents($root.'/deploy/.squire.json'), true);
    $runs['Squire'] = (string) ($kit['version'] ?? 'no version in deploy/.squire.json');

    $npm = json_decode((string) file_get_contents($root.'/package.json'), true);
    $runs['WayneUI'] = (string) ($npm['dependencies']['@waynestate/wayne-ui-css'] ?? $npm['devDependencies']['@waynestate/wayne-ui-css'] ?? 'not in package.json');

    return $runs;
}

it('lists every part of the stack in the weld log, WayneUI and WELD included', function (): void {
    $log = frontPageReleases()['log'];

    expect(array_keys($log))->toBe([
        'WayneUI', 'CREED', 'PROCTOR', 'Pinstripe', 'Preflight', 'HELMET', 'SESAME', 'Sourdough', 'Squire',
        'Parity', 'Booster', 'Rollcall', 'Drawbridge', 'ADVISOR', 'WELD',
    ]);
    foreach ($log as $part => $release) {
        expect($release)->toMatch('/^[0-9]+\.[0-9]+\.[0-9]+$/', $part.' has no release in the weld log. '.FRONT_PAGE_FIX);
    }
});

it('shows the same release on each cast card as in the weld log', function (): void {
    ['log' => $log, 'cast' => $cast] = frontPageReleases();

    expect(count($cast))->toBeGreaterThanOrEqual(13);
    foreach ($cast as $part => $chip) {
        expect($log)->toHaveKey($part, message: $part.' has a cast card but no row in the weld log');
        expect($chip)->toBe($log[$part], $part.': the cast card says '.$chip.', the weld log '.$log[$part].'. '.FRONT_PAGE_FIX);
    }
});

it('never shows a part older than the release WELD runs', function (): void {
    $log = frontPageReleases()['log'];

    foreach (weldRunsReleases() as $part => $runs) {
        expect($runs)->toMatch('/^[0-9]+\.[0-9]+\.[0-9]+$/', 'Cannot tell which '.$part.' WELD runs: '.$runs);
        expect(version_compare($log[$part], $runs, '>='))->toBeTrue(
            'The front page says '.$part.' '.$log[$part].', but WELD runs '.$runs.'. '.FRONT_PAGE_FIX
        );
    }
});

it('names the Preflight release WELD pins in the Preflight cast line', function (): void {
    $html = (string) file_get_contents(FRONT_PAGE);
    $pin = weldRunsReleases()['Preflight'];

    expect($html)->toContain("WELD's pipeline pins them at ".$pin.' ');
});
