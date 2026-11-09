<?php

declare(strict_types=1);

/*
 * sync-practices.php: keep the docs guides in step with BEST_PRACTICES.md.
 *
 * BEST_PRACTICES.md is the truth for each practice's Practice, Why and Stack
 * lines; the guides in docs/guides repeat them, one section per practice.
 * This script rewrites the repeated lines from the source instead of
 * hand-copying them, and --check makes composer ci fail when a guide drifts.
 * Both modes also fail when the guides do not cover BEST_PRACTICES.md one
 * for one: a practice with no section, a section in two guides, a section
 * BEST_PRACTICES.md does not know, or a section without its Stack line.
 *
 * Usage:
 *   php bin/sync-practices.php          rewrite the guides that change
 *   php bin/sync-practices.php --check  write nothing, list the drifted guides
 *
 * Exit codes: 0 nothing to do (or the guides were rewritten), 1 a guide
 * drifts (check mode) or the coverage is off (both modes, listed on stderr),
 * 2 usage error. Plain PHP 8.3 and WELD's own autoloader, no other Composer
 * packages, so it runs on Windows, macOS and Linux the same way.
 */

use App\Support\PracticesSync;

require dirname(__DIR__).'/vendor/autoload.php';

$usage = "Usage: php bin/sync-practices.php [--check]\n"
    ."       php bin/sync-practices.php --help\n";

$root = dirname(__DIR__);
$check = false;

$i = 1;
$argc = count($argv);
while ($i < $argc) {
    $arg = $argv[$i];
    if ($arg === '--check') {
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

$sourcePath = $root.'/BEST_PRACTICES.md';
if (! is_file($sourcePath)) {
    fwrite(STDERR, 'sync-practices: BEST_PRACTICES.md is missing from the repo root.'."\n");
    exit(2);
}

$practices = PracticesSync::practices((string) file_get_contents($sourcePath));

$guides = glob($root.'/docs/guides/*.md') ?: [];
sort($guides);

$changed = [];
$texts = [];
foreach ($guides as $guide) {
    $relative = str_replace('\\', '/', substr($guide, strlen($root) + 1));
    $text = (string) file_get_contents($guide);
    $synced = PracticesSync::sync($text, $practices);
    $texts[$relative] = $synced;
    if ($synced === $text) {
        continue;
    }
    $changed[] = $relative;
    if (! $check && file_put_contents($guide, $synced) === false) {
        fwrite(STDERR, 'sync-practices: cannot write '.$guide."\n");
        exit(2);
    }
}

// A practice the guides never mention is a drift no rewrite can fix, so
// both modes report it and exit 1: write the section by hand, then sync.
$problems = PracticesSync::coverage($practices, $texts);
foreach ($problems as $problem) {
    fwrite(STDERR, 'sync-practices: '.$problem."\n");
}

if ($check) {
    foreach ($changed as $guide) {
        fwrite(STDOUT, $guide."\n");
    }
    if ($changed !== [] || $problems !== []) {
        exit(1);
    }
    fwrite(STDOUT, 'Practices in the guides match BEST_PRACTICES.md.'."\n");
    exit(0);
}

fwrite(STDOUT, 'Synced '.count($changed)." guide(s).\n");
exit($problems === [] ? 0 : 1);
