<?php

declare(strict_types=1);

/*
 * cast-versions.php: rewrite the cast's version labels from each project's latest tag.
 *
 * The front page (site/index.html) and the README name every part's latest
 * release by hand: once in the weld log (joint__rel), once on the cast card
 * (chip--version) and, for the parts the README lists, in its "Current
 * versions" list. They drift on every tag, so this tool rewrites them from
 * the tags. WELD's own row is not one of them: it carries data-docs-version,
 * which the docs builder keeps current.
 *
 * Usage:
 *   php bin/cast-versions.php [--tags <file>] [--site <file>] [--readme <file>] [--today <YYYY-MM-DD>] [--check]
 *
 * Tag source, in order: --tags <file>, a JSON object of part name to version
 * (for tests and offline runs), else `git ls-remote --tags --refs` against
 * git@git.wayne.edu:enterprise-apps/<repo>.git, one call per part, spaced
 * because git.wayne.edu throttles bursts. A part whose remote cannot be
 * reached is warned about and keeps its current label; the write never fails
 * on that.
 *
 * --check writes nothing and exits 1 listing each stale label (part, shown,
 * tag). It is deliberately not part of `composer ci`: it fails on every new
 * tag until someone runs the rewrite, so it is a release step, not a gate.
 *
 * --today <YYYY-MM-DD> is the date a rewrite stamps in the README's
 * "(checked ...)" line; the default is today in America/Detroit. A value
 * that is not a real calendar date in that exact shape is a usage error.
 * --check never reports or rewrites the date, and a run that changes no
 * label leaves the date alone too.
 *
 * The "pins them at" sentence on the Preflight card is never touched: it
 * names the release WELD pins, not the latest tag.
 *
 * Exit codes: 0 current or rewritten, 1 stale labels (--check), 2 usage
 * error. Plain PHP 8.3, no Composer packages, so it runs on Windows, macOS
 * and Linux the same way.
 */

/** Part name as written on the page, to the repository under enterprise-apps/. */
const CAST_PARTS = [
    'WayneUI' => 'wayne-ui',
    'CREED' => 'creed',
    'PROCTOR' => 'proctor',
    'Pinstripe' => 'pinstripe',
    'Preflight' => 'preflight',
    'HELMET' => 'helmet',
    'SESAME' => 'sesame',
    'Sourdough' => 'sourdough',
    'Squire' => 'squire',
    'Parity' => 'parity',
    'Booster' => 'booster',
    'Rollcall' => 'rollcall',
    'Drawbridge' => 'drawbridge',
    'ADVISOR' => 'advisor',
];

const CAST_REMOTE_BASE = 'git@git.wayne.edu:enterprise-apps/';

/** Space the polls: git.wayne.edu throttles bursts (WORKER_CONTEXT, 2026-11-19). */
const CAST_GIT_SPACING_SECONDS = 60;

const CAST_GIT_TIMEOUT_SECONDS = 30;

$usage = "Usage: php bin/cast-versions.php [--tags <file>] [--site <file>] [--readme <file>] [--today <YYYY-MM-DD>] [--check]\n"
    ."       php bin/cast-versions.php --help\n";

$tagsFile = null;
$siteArg = null;
$readmeArg = null;
$todayArg = null;
$check = false;

$i = 1;
while ($i < count($argv)) {
    $arg = $argv[$i];
    if ($arg === '--tags' || str_starts_with($arg, '--tags=')) {
        $value = str_starts_with($arg, '--tags=') ? substr($arg, 7) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '') {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $tagsFile = $value;
    } elseif ($arg === '--site' || str_starts_with($arg, '--site=')) {
        $value = str_starts_with($arg, '--site=') ? substr($arg, 7) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '') {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $siteArg = $value;
    } elseif ($arg === '--readme' || str_starts_with($arg, '--readme=')) {
        $value = str_starts_with($arg, '--readme=') ? substr($arg, 9) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '') {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $readmeArg = $value;
    } elseif ($arg === '--today' || str_starts_with($arg, '--today=')) {
        $value = str_starts_with($arg, '--today=') ? substr($arg, 8) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '' || ! castVersionsIsDate($value)) {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $todayArg = $value;
    } elseif ($arg === '--check') {
        $check = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        fwrite(STDOUT, $usage);
        exit(0);
    } else {
        fwrite(STDERR, $usage);
        exit(2);
    }
    $i++;
}

$today = $todayArg ?? (new DateTimeImmutable('now', new DateTimeZone('America/Detroit')))->format('Y-m-d');

$root = dirname(__DIR__);
$sitePath = $siteArg !== null ? $siteArg : $root.'/site/index.html';
$readmePath = $readmeArg !== null ? $readmeArg : $root.'/README.md';

if (! is_file($sitePath)) {
    fwrite(STDERR, "cast-versions: site file $sitePath does not exist.\n");
    exit(2);
}
if (! is_file($readmePath)) {
    fwrite(STDERR, "cast-versions: README file $readmePath does not exist.\n");
    exit(2);
}

$siteHtml = (string) file_get_contents($sitePath);
$readmeText = (string) file_get_contents($readmePath);

$tags = [];
if ($tagsFile !== null) {
    if (! is_file($tagsFile)) {
        fwrite(STDERR, "cast-versions: tags file $tagsFile does not exist.\n");
        exit(2);
    }
    $decoded = json_decode((string) file_get_contents($tagsFile), true);
    if (! is_array($decoded) || $decoded === []) {
        fwrite(STDERR, "cast-versions: $tagsFile must be a JSON object of part name to version.\n");
        exit(2);
    }
    foreach ($decoded as $name => $version) {
        if (! is_string($version) || ! castVersionsIsVersion($version)) {
            fwrite(STDERR, "cast-versions: $name in $tagsFile is not a version: $version\n");
            exit(2);
        }
        $tags[(string) $name] = $version;
    }
} else {
    $spaced = false;
    foreach (CAST_PARTS as $name => $slug) {
        if ($spaced) {
            usleep(CAST_GIT_SPACING_SECONDS * 1_000_000);
        }
        $spaced = true;
        $url = CAST_REMOTE_BASE.$slug.'.git';
        $remoteTags = castVersionsLsRemote($url);
        if ($remoteTags === null) {
            fwrite(STDERR, "cast-versions: warning: could not reach $url; keeping the current label for $name.\n");

            continue;
        }
        $latest = castVersionsLatest($remoteTags);
        if ($latest === null) {
            fwrite(STDERR, "cast-versions: warning: no semver tag on $url; keeping the current label for $name.\n");

            continue;
        }
        $tags[$name] = $latest;
    }
}

if ($check) {
    $stale = castVersionsStale($siteHtml, $readmeText, $tags);
    if ($stale !== []) {
        fwrite(STDERR, 'cast-versions: stale labels (run without --check to rewrite them):'."\n");
        foreach ($stale as [$part, $shown, $tag, $where]) {
            fwrite(STDERR, "  $part: shown $shown, tag $tag ($where)\n");
        }
        exit(1);
    }
    fwrite(STDOUT, 'cast-versions: every label matches its latest tag.'."\n");
    exit(0);
}

[$newSite, $siteChanges] = castVersionsRewriteSite($siteHtml, $tags);
[$newReadme, $readmeChanges] = castVersionsRewriteReadme($readmeText, $tags, $today);

if ($newSite !== $siteHtml) {
    file_put_contents($sitePath, $newSite);
}
if ($newReadme !== $readmeText) {
    file_put_contents($readmePath, $newReadme);
}

$changes = array_merge($siteChanges, $readmeChanges);
if ($changes === []) {
    fwrite(STDOUT, 'cast-versions: nothing to update; every label already matches its latest tag.'."\n");
    exit(0);
}

$byPart = [];
foreach ($changes as $change) {
    $key = $change['part'];
    $byPart[$key]['from'] = $change['from'];
    $byPart[$key]['to'] = $change['to'];
    $byPart[$key]['where'][] = $change['where'];
}
foreach ($byPart as $part => $change) {
    fwrite(STDOUT, "$part: {$change['from']} -> {$change['to']} (".implode(', ', $change['where']).")\n");
}
fwrite(STDOUT, 'cast-versions: rewrote '.count($byPart).' part(s).'."\n");
exit(0);

function castVersionsIsVersion(string $value): bool
{
    return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.-]+)?$/', $value) === 1;
}

/** True when the value is a real calendar date written exactly YYYY-MM-DD. */
function castVersionsIsDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/Detroit'));

    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

/**
 * The highest semver tag on the remote, a leading v stripped. Null when the
 * remote cannot be reached or times out.
 */
function castVersionsLsRemote(string $url): ?array
{
    $process = proc_open(['git', 'ls-remote', '--tags', '--refs', $url], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        return null;
    }
    fclose($pipes[0]);

    $stdout = '';
    $stderr = '';
    $timedOut = false;
    $deadline = microtime(true) + CAST_GIT_TIMEOUT_SECONDS;

    while (true) {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            $timedOut = true;
            break;
        }
        $read = [$pipes[1], $pipes[2]];
        $write = null;
        $except = null;
        $selected = @stream_select($read, $write, $except, (int) $remaining, (int) (($remaining - (int) $remaining) * 1_000_000));
        if ($selected === false) {
            // stream_select does not watch process pipes on Windows; read to EOF instead.
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            break;
        }
        if ($selected === 0) {
            $timedOut = true;
            break;
        }
        foreach ($read as $stream) {
            $chunk = (string) fread($stream, 8192);
            if ($chunk === '') {
                continue;
            }
            if ($stream === $pipes[1]) {
                $stdout .= $chunk;
            } else {
                $stderr .= $chunk;
            }
        }
        if (feof($pipes[1]) && feof($pipes[2])) {
            break;
        }
    }

    if ($timedOut) {
        proc_terminate($process, 9);
        proc_close($process);

        return null;
    }

    $code = proc_close($process);
    if ($code !== 0) {
        return null;
    }

    $tags = [];
    foreach (explode("\n", $stdout) as $line) {
        $parts = explode("\t", trim($line), 2);
        if (count($parts) !== 2 || ! str_starts_with($parts[1], 'refs/tags/')) {
            continue;
        }
        $tags[] = substr($parts[1], strlen('refs/tags/'));
    }

    return $tags;
}

function castVersionsLatest(array $tags): ?string
{
    $latest = null;
    foreach ($tags as $tag) {
        $version = ltrim($tag, 'v');
        if (! castVersionsIsVersion($version)) {
            continue;
        }
        if ($latest === null || version_compare($version, $latest, '>')) {
            $latest = $version;
        }
    }

    return $latest;
}

/**
 * Rewrite the joint__rel value of each part's weld log row and the chip--version
 * value of its cast card, matched by the name in joint__name / cast-card__link.
 * Returns [new html, changes]; a change is ['part', 'from', 'to', 'where'].
 */
function castVersionsRewriteSite(string $html, array $tags): array
{
    $changes = [];

    $html = preg_replace_callback('#<li class="joint[^"]*"[^>]*>.*?</li>#s', static function (array $m) use ($tags, &$changes): string {
        if (preg_match('#<h3 class="joint__name">\s*([^<]+?)\s*</h3>#', $m[0], $name) !== 1 || ! isset($tags[trim($name[1])])) {
            return $m[0];
        }
        $part = trim($name[1]);
        if (preg_match('#<p class="joint__rel"><span class="wsu-sr-only">Release </span>([^<]+)</p>#', $m[0], $old) !== 1 || $old[1] === $tags[$part]) {
            return $m[0];
        }
        $changes[] = ['part' => $part, 'from' => $old[1], 'to' => $tags[$part], 'where' => 'weld log'];

        return (string) preg_replace_callback(
            '#(<p class="joint__rel"><span class="wsu-sr-only">Release </span>)[^<]+(</p>)#',
            static fn (array $mm): string => $mm[1].$tags[$part].$mm[2],
            $m[0],
            1
        );
    }, $html);

    $html = preg_replace_callback('#<article class="cast-card[^"]*"[^>]*>.*?</article>#s', static function (array $m) use ($tags, &$changes): string {
        if (preg_match('#<a class="cast-card__link"[^>]*>\s*([^<]+?)\s*</a>#', $m[0], $name) !== 1 || ! isset($tags[trim($name[1])])) {
            return $m[0];
        }
        $part = trim($name[1]);
        if (preg_match('#<span class="chip chip--version">([^<]+)</span>#', $m[0], $old) !== 1 || $old[1] === $tags[$part]) {
            return $m[0];
        }
        $changes[] = ['part' => $part, 'from' => $old[1], 'to' => $tags[$part], 'where' => 'cast card'];

        return (string) preg_replace_callback(
            '#(<span class="chip chip--version">)[^<]+(</span>)#',
            static fn (array $mm): string => $mm[1].$tags[$part].$mm[2],
            $m[0],
            1
        );
    }, $html);

    return [$html, $changes];
}

/**
 * Rewrite the versions in the README's "Current versions" list, keeping the
 * names, the order and the pinning note after the list. When a label
 * changes, the "(checked ...)" date becomes $today; when none changes, the
 * README comes back exactly as it was, old date and all.
 */
function castVersionsRewriteReadme(string $readme, array $tags, string $today): array
{
    $changes = [];
    $count = 0;
    $new = preg_replace_callback(
        '#(\*\*Current versions\*\* \(checked [0-9]{4}-[0-9]{2}-[0-9]{2}\): )(.+?)(\. These are the latest releases)#',
        static function (array $m) use ($tags, $today, &$changes): string {
            $pairs = explode(', ', $m[2]);
            $changed = false;
            foreach ($pairs as $i => $pair) {
                [$name, $shown] = array_pad(explode(' ', $pair, 2), 2, '');
                if (! isset($tags[$name]) || $shown === $tags[$name]) {
                    continue;
                }
                $changes[] = ['part' => $name, 'from' => $shown, 'to' => $tags[$name], 'where' => 'README'];
                $pairs[$i] = $name.' '.$tags[$name];
                $changed = true;
            }

            return $changed
                ? '**Current versions** (checked '.$today.'): '.implode(', ', $pairs).$m[3]
                : $m[0];
        },
        $readme,
        1,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "cast-versions: warning: could not find the README \"Current versions\" list; leaving it alone.\n");
    }

    return [(string) $new, $changes];
}

/**
 * Each label that disagrees with the tags: [part, shown, tag, where].
 */
function castVersionsStale(string $html, string $readme, array $tags): array
{
    $stale = [];

    preg_match_all('#<li class="joint[^"]*"[^>]*>(.*?)</li>#s', $html, $joints);
    foreach ($joints[1] as $joint) {
        if (preg_match('#<h3 class="joint__name">\s*([^<]+?)\s*</h3>#', $joint, $name) !== 1
            || preg_match('#<p class="joint__rel"><span class="wsu-sr-only">Release </span>([^<]+)</p>#', $joint, $shown) !== 1) {
            continue;
        }
        $part = trim($name[1]);
        if (isset($tags[$part]) && $shown[1] !== $tags[$part]) {
            $stale[] = [$part, $shown[1], $tags[$part], 'weld log'];
        }
    }

    preg_match_all('#<article class="cast-card[^"]*"[^>]*>(.*?)</article>#s', $html, $cards);
    foreach ($cards[1] as $card) {
        if (preg_match('#<a class="cast-card__link"[^>]*>\s*([^<]+?)\s*</a>#', $card, $name) !== 1
            || preg_match('#<span class="chip chip--version">([^<]+)</span>#', $card, $shown) !== 1) {
            continue;
        }
        $part = trim($name[1]);
        if (isset($tags[$part]) && $shown[1] !== $tags[$part]) {
            $stale[] = [$part, $shown[1], $tags[$part], 'cast card'];
        }
    }

    if (preg_match('#\*\*Current versions\*\* \(checked [0-9]{4}-[0-9]{2}-[0-9]{2}\): (.+?)\. These are the latest releases#', $readme, $list) === 1) {
        foreach (explode(', ', $list[1]) as $pair) {
            [$name, $shown] = array_pad(explode(' ', $pair, 2), 2, '');
            if (isset($tags[$name]) && $shown !== $tags[$name]) {
                $stale[] = [$name, $shown, $tags[$name], 'README'];
            }
        }
    }

    return $stale;
}
