<?php

/*
|--------------------------------------------------------------------------
| The app's own pages in the accessibility gate
|--------------------------------------------------------------------------
|
| The a11y-pages job saves each path in A11Y_PATHS, signed in by SESAME's fake
| driver, and the a11y-app include checks the saved pages it is told about.
| Two lists, one comment asking that they change together, and a page nobody
| added to either is a page the gate never sees: until 2026-11-11 both
| settings pages were exactly that. These tests hold the two lists equal, and
| walk every path the way the job does (one session, redirects followed) to
| prove every Inertia page under resources/js/pages is drawn by one of them.
| Build a page, and the suite asks you to put it under the hood.
*/

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Yaml\Yaml;

uses(RefreshDatabase::class);

/**
 * The a11y-pages job's A11Y_PATHS and the a11y-app include's pages, from .gitlab-ci.yml.
 *
 * @return array{paths: list<string>, pages: list<string>}
 */
function weldA11yLists(): array
{
    $ci = Yaml::parseFile(dirname(__DIR__, 2).'/.gitlab-ci.yml');

    $paths = preg_split('/\s+/', trim((string) ($ci['a11y-pages']['variables']['A11Y_PATHS'] ?? ''))) ?: [];

    $pages = null;
    foreach ($ci['include'] ?? [] as $include) {
        if (is_array($include) && str_contains((string) ($include['component'] ?? ''), '/preflight/a11y@')
            && ($include['inputs']['job_name'] ?? null) === 'a11y-app') {
            $pages = $include['inputs']['pages'] ?? [];
        }
    }
    expect($pages)->toBeArray('.gitlab-ci.yml has no a11y include with job_name a11y-app');

    return ['paths' => array_values(array_filter($paths)), 'pages' => array_values($pages)];
}

/**
 * Every Inertia page the app ships, as Inertia names it (resources/js/pages/settings/Profile.vue is settings/Profile).
 *
 * @return list<string>
 */
function weldInertiaPages(): array
{
    $base = dirname(__DIR__, 2).'/resources/js/pages';
    $pages = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (str_ends_with($path, '.vue')) {
            $pages[] = substr($path, strlen(str_replace('\\', '/', $base)) + 1, -4);
        }
    }
    sort($pages);

    return $pages;
}

it('saves the same pages the a11y-app job checks', function (): void {
    ['paths' => $paths, 'pages' => $pages] = weldA11yLists();

    expect($paths)->not->toBeEmpty();
    expect($pages)->toBe($paths, 'A11Y_PATHS in the a11y-pages job and the a11y-app include\'s pages must list the same paths, in the same order');
});

it('puts every Inertia page under the gate', function (): void {
    $paths = weldA11yLists()['paths'];
    $drawn = [];

    // The job's walk: one cookie jar, redirects followed, so the first signed-in
    // path goes through SESAME's fake /login and every later one is signed in.
    foreach ($paths as $path) {
        $response = $this->followingRedirects()->get($path);
        $response->assertOk();

        $page = $response->viewData('page');
        expect($page)->toBeArray($path.' did not answer with an Inertia page');
        $drawn[] = (string) $page['component'];
    }

    $missed = array_values(array_diff(weldInertiaPages(), $drawn));
    expect($missed)->toBe([], 'No path in A11Y_PATHS draws these pages, so the accessibility gate never checks them. '
        .'Add the path that shows each one to A11Y_PATHS (the a11y-pages job) and to the a11y-app include\'s pages: '
        .implode(', ', $missed));
});
