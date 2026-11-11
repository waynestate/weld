<?php

declare(strict_types=1);

/*
 * BEST_PRACTICES and the docs prove their `Checked` and `Enforced` tags by
 * citing files, often down to the line: `tests/Feature/CsrfTest.php:15-19`,
 * `deploy/scripts/release.sh:563`. A tag is only as good as its citation, and
 * a citation goes stale quietly when a file is renamed, deleted or shortened.
 * These tests read every backticked repo path in those docs and fail when the
 * file is gone or the cited line is past its end. They cannot tell that line
 * 40 still says what the doc says it does; they can tell when line 40 no longer
 * exists, which is the citation equivalent of a weld with no metal in it.
 */

/** The docs that cite: every hand-written one except the changelog, whose old lines cite old files on purpose. */
const DOCS_CITATIONS_FILES = ['README.md', 'MAINTAINING.md', 'BEST_PRACTICES.md', 'docs'];

/** Top-level folders whose paths are this repo's own (vendor and package paths are not checked). */
const DOCS_CITATIONS_ROOTS = ['.github', '.sourdough', 'app', 'bin', 'bootstrap', 'config', 'database', 'deploy',
    'docs', 'public', 'resources', 'routes', 'scripts', 'site', 'stubs', 'tests', 'tools'];

/**
 * Paths the docs name on purpose that a fresh checkout does not have, each with the reason. Add one here only
 * with its reason; a renamed file is a doc to fix, not a path to excuse.
 */
const DOCS_CITATIONS_ABSENT = [
    'app/Jobs' => 'the folder an app adds with its first job; JobLimitsTest passes while it is missing',
    '.sourdough/manifest.json' => 'what sourdough adopt writes in a derived app; WELD itself carries none',
    'bin/weld' => 'shorthand for the pair bin/weld.ps1 and bin/weld.sh',
    'bootstrap/cache/config.php' => 'the config cache, which the image must never bake in',
    'database/database.sqlite' => 'the local SQLite file, made on first run',
    'public/hot' => "Vite's dev server marker, written while it runs and gitignored",
    'resources/boost/guidelines/core.blade.php' => 'a path inside a stack package that ships Boost guidelines',
];

/**
 * Every citation of a repo file, as "doc:line `citation`" => [path, last cited line or null].
 *
 * @return array<string, array{0: string, 1: int|null}>
 */
function docsCitations(): array
{
    $root = dirname(__DIR__, 2);
    $docs = [];
    foreach (DOCS_CITATIONS_FILES as $entry) {
        if (is_file($root.'/'.$entry)) {
            $docs[] = $entry;

            continue;
        }
        $walk = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$entry, FilesystemIterator::SKIP_DOTS));
        foreach ($walk as $file) {
            $relative = ltrim(str_replace('\\', '/', substr((string) $file, strlen($root))), '/');
            if (str_ends_with($relative, '.md') && $relative !== 'docs/changelog.md') {
                $docs[] = $relative;
            }
        }
    }
    sort($docs);

    $citations = [];
    foreach ($docs as $doc) {
        foreach (preg_split('/\R/', (string) file_get_contents($root.'/'.$doc)) ?: [] as $index => $line) {
            preg_match_all('#`(\.?[A-Za-z0-9_][A-Za-z0-9_.-]*(?:/[A-Za-z0-9_.-]+)*)(?::([0-9]+)(?:-([0-9]+))?)?`#', $line, $found, PREG_SET_ORDER);
            foreach ($found as $match) {
                $path = $match[1];
                $top = explode('/', $path)[0];
                $lineCited = isset($match[2]) && $match[2] !== '';
                $ours = (str_contains($path, '/') && in_array($top, DOCS_CITATIONS_ROOTS, true))
                    // A bare name counts when it is a file at the root; `SecurityHeaders.php:57` is HELMET's.
                    || (! str_contains($path, '/') && is_file($root.'/'.$path));
                if (! $ours || array_key_exists($path, DOCS_CITATIONS_ABSENT) || str_contains($path, '*')) {
                    continue;
                }
                $last = $lineCited ? (int) (($match[3] ?? '') !== '' ? $match[3] : $match[2]) : null;
                $citations[$doc.':'.($index + 1).' `'.$match[0].'`'] = [$path, $last];
            }
        }
    }

    return $citations;
}

it('finds the citations it is meant to check', function (): void {
    $cited = docsCitations();

    expect(count($cited))->toBeGreaterThan(100, 'Fewer citations than expected; did the pattern stop matching?');
    expect(array_filter($cited, fn (array $c): bool => $c[1] !== null))->not->toBeEmpty();
});

it('cites only files the repo has', function (): void {
    $root = dirname(__DIR__, 2);
    $missing = [];
    foreach (docsCitations() as $where => [$path]) {
        // Folders count too: a citation of `deploy/.squire/portable` names the folder.
        if (! file_exists($root.'/'.$path)) {
            $missing[] = $where;
        }
    }

    expect($missing)->toBe([], "These citations name a file the repo does not have; fix the path in the doc (for BEST_PRACTICES.md, then run php bin/sync-practices.php):\n".implode("\n", $missing));
});

it('cites only lines the file has', function (): void {
    $root = dirname(__DIR__, 2);
    $past = [];
    foreach (docsCitations() as $where => [$path, $last]) {
        if ($last === null || ! is_file($root.'/'.$path)) {
            continue;
        }
        $lines = count(preg_split('/\R/', rtrim((string) file_get_contents($root.'/'.$path), "\r\n")) ?: []);
        if ($last > $lines) {
            $past[] = $where.' (the file has '.$lines.' lines)';
        }
    }

    expect($past)->toBe([], "These citations point past the end of the file; find the lines again and fix the doc:\n".implode("\n", $past));
});

it('excuses only paths that are still cited and still absent', function (): void {
    $root = dirname(__DIR__, 2);
    $cited = [];
    foreach (DOCS_CITATIONS_FILES as $entry) {
        $files = is_file($root.'/'.$entry) ? [$root.'/'.$entry] : glob($root.'/'.$entry.'/{,*/}*.md', GLOB_BRACE);
        foreach ($files ?: [] as $file) {
            $cited[] = (string) file_get_contents($file);
        }
    }
    $text = implode("\n", $cited);

    foreach (array_keys(DOCS_CITATIONS_ABSENT) as $path) {
        expect(str_contains($text, '`'.$path))->toBeTrue($path.' is excused but no doc cites it any more; drop it from DOCS_CITATIONS_ABSENT');
        expect(file_exists($root.'/'.$path))->toBeFalse($path.' is excused as absent but the repo has it now; drop it from DOCS_CITATIONS_ABSENT');
    }
});
