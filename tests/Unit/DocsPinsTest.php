<?php

declare(strict_types=1);

/*
 * The README, MAINTAINING, BEST_PRACTICES and the docs pages say which
 * Preflight release CI pins and which Squire kit the deploy layer carries.
 * Those sentences are typed by hand, and on 2026-11-11 five of them still said
 * Preflight 0.6.0 two pins after CI moved to 0.8.0. These tests read the pins
 * from the files that hold them (.gitlab-ci.yml, the GitHub workflow and
 * deploy/.squire.json) and fail on any "current pin" sentence that names
 * another release. History ("since Preflight 0.5.0", the changelog) is left
 * alone: only the sentences below claim to be the present tense. A label that
 * says 7018 rod on a box of 6013 is still a label, just not a helpful one.
 */

/**
 * Where the sentences live: every hand-written doc except the changelog, which is history by design.
 *
 * @return list<string>
 */
function docsPinsFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [$root.'/README.md', $root.'/MAINTAINING.md', $root.'/BEST_PRACTICES.md'];
    $docs = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/docs', FilesystemIterator::SKIP_DOTS));
    foreach ($docs as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (str_ends_with($path, '.md') && ! str_ends_with($path, '/docs/changelog.md')) {
            $files[] = $path;
        }
    }
    sort($files);

    return $files;
}

/**
 * Every match of the given present-tense patterns, as "file:line" => version.
 *
 * @param  list<string>  $patterns  each with one capture group holding a release
 * @return array<string, string>
 */
function docsPinsClaims(array $patterns): array
{
    $root = str_replace('\\', '/', dirname(__DIR__, 2)).'/';
    $claims = [];
    foreach (docsPinsFiles() as $file) {
        foreach (preg_split('/\R/', (string) file_get_contents($file)) ?: [] as $index => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $line, $found) > 0) {
                    foreach ($found[1] as $n => $version) {
                        $claims[str_replace($root, '', str_replace('\\', '/', $file)).':'.($index + 1).'#'.$n.' '.$pattern] = $version;
                    }
                }
            }
        }
    }

    return $claims;
}

/** The Preflight release every GitLab component include pins (they must agree). */
function docsPinsPreflight(): string
{
    preg_match_all('#/preflight/[a-z0-9-]+@([0-9]+\.[0-9]+\.[0-9]+)#', (string) file_get_contents(dirname(__DIR__, 2).'/.gitlab-ci.yml'), $pins);
    $tags = array_values(array_unique($pins[1]));

    expect($tags)->toHaveCount(1, 'Every Preflight include in .gitlab-ci.yml should pin the same release, found: '.implode(', ', $tags));

    return $tags[0];
}

const DOCS_PINS_PREFLIGHT = [
    "/CI pins Preflight(?:'s components at)? ([0-9]+\\.[0-9]+\\.[0-9]+)/",
    "/Preflight's components run CI \\(([0-9]+\\.[0-9]+\\.[0-9]+)\\)/",
    '/on GitLab, Preflight ([0-9]+\.[0-9]+\.[0-9]+) adds/',
    '/`\.gitlab-ci\.yml`, Preflight ([0-9]+\.[0-9]+\.[0-9]+)\)/',
    '/pins every Preflight component to the ([0-9]+\.[0-9]+\.[0-9]+) release tag/',
    "/Preflight's components are pinned at ([0-9]+\\.[0-9]+\\.[0-9]+) in CI/",
];

const DOCS_PINS_SQUIRE = [
    "/runs Squire's kit ([0-9]+\\.[0-9]+\\.[0-9]+)/",
    '/kit in `deploy\/`,? (?:is )?at ([0-9]+\.[0-9]+\.[0-9]+)/',
    "/Squire's kit in `deploy\\/` deploys it \\(([0-9]+\\.[0-9]+\\.[0-9]+)\\)/",
    "/Squire ([0-9]+\\.[0-9]+\\.[0-9]+)'s release script/",
];

const DOCS_PINS_FIX = 'Change the sentence to the release the repo pins, then run php bin/sync-practices.php (for '
    .'BEST_PRACTICES.md), composer docs and composer site:agent-ready so the guides and the site follow.';

it('names the Preflight release CI pins wherever a doc says what CI pins', function (): void {
    $pin = docsPinsPreflight();
    $claims = docsPinsClaims(DOCS_PINS_PREFLIGHT);

    expect(count($claims))->toBeGreaterThanOrEqual(8, 'The present-tense Preflight sentences moved; update DOCS_PINS_PREFLIGHT to find them again');
    foreach ($claims as $where => $version) {
        expect($version)->toBe($pin, $where.' says Preflight '.$version.', but .gitlab-ci.yml pins '.$pin.'. '.DOCS_PINS_FIX);
    }
});

it('names the Preflight release the GitHub workflow calls', function (): void {
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/ci.yml');

    expect(preg_match('#preflight/\.github/workflows/[a-z0-9-]+\.yml@[0-9a-f]{40} \# ([0-9]+\.[0-9]+\.[0-9]+)#', $workflow, $called))
        ->toBe(1, 'The GitHub workflow should call Preflight at a release commit with the tag in a comment');
    expect($called[1])->toBe(docsPinsPreflight(), 'GitLab and GitHub should run the same Preflight release');

    $claims = docsPinsClaims(["/calls Preflight ([0-9]+\\.[0-9]+\\.[0-9]+)'s reusable workflow/"]);
    expect($claims)->not->toBeEmpty();
    foreach ($claims as $where => $version) {
        expect($version)->toBe($called[1], $where.' says the GitHub workflow calls Preflight '.$version.'. '.DOCS_PINS_FIX);
    }
});

it('names the Squire kit deploy/.squire.json records wherever a doc says which kit the deploy layer runs', function (): void {
    $kit = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/deploy/.squire.json'), true);
    $version = (string) ($kit['version'] ?? '');
    expect($version)->toMatch('/^[0-9]+\.[0-9]+\.[0-9]+$/', 'deploy/.squire.json records no kit version');

    $claims = docsPinsClaims(DOCS_PINS_SQUIRE);

    expect(count($claims))->toBeGreaterThanOrEqual(8, 'The present-tense Squire kit sentences moved; update DOCS_PINS_SQUIRE to find them again');
    foreach ($claims as $where => $said) {
        expect($said)->toBe($version, $where.' says Squire '.$said.', but deploy/.squire.json records '.$version.'. '.DOCS_PINS_FIX);
    }
});
