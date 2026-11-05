<?php

declare(strict_types=1);

/*
 * regen-check.php: is WELD still Sourdough's Inertia shape?
 *
 * Compares a freshly generated Sourdough Inertia app with WELD, file by file,
 * by SHA-256, against the recorded baseline of known drift, and fails only on
 * NEW drift (a ratchet). See design/weld-reproducible-from-sourdough.md and
 * research/weld-regen-diff-1103.md.
 *
 * Usage:
 *   php bin/regen-check.php --generated <dir> [--baseline .sourdough/regen-baseline.json] [--write-baseline]
 *
 * <dir> must contain .sourdough/manifest.json, the file list Sourdough writes
 * into every app it generates (files maps path to {class, sha256}). Paths
 * listed in .sourdough/product-files.json (exact paths or dir/ prefixes) and
 * every path with a vendor or node_modules segment at any depth are skipped.
 * Each remaining path is reported as IDENTICAL, DIFFERS (the WELD file exists
 * with another SHA-256) or MISSING_IN_WELD.
 *
 * Exit codes: 0 no new drift (or the baseline was written), 1 new drift,
 * listed on stderr, 2 usage error. Plain PHP 8.3, no Composer packages, so it
 * runs on Windows, macOS and Linux the same way.
 */

use App\Support\RegenCheck;
use App\Support\RegenCheckError;

require dirname(__DIR__).'/vendor/autoload.php';

$usage = "Usage: php bin/regen-check.php --generated <dir> [--baseline .sourdough/regen-baseline.json] [--write-baseline]\n"
    ."       php bin/regen-check.php --help\n";

$generated = null;
$baseline = '.sourdough/regen-baseline.json';
$writeBaseline = false;

$i = 1;
$argc = count($argv);
while ($i < $argc) {
    $arg = $argv[$i];
    if ($arg === '--generated' || str_starts_with($arg, '--generated=')) {
        $value = str_starts_with($arg, '--generated=') ? substr($arg, 12) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '') {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $generated = $value;
    } elseif ($arg === '--baseline' || str_starts_with($arg, '--baseline=')) {
        $value = str_starts_with($arg, '--baseline=') ? substr($arg, 11) : ($argv[++$i] ?? null);
        if (! is_string($value) || $value === '') {
            fwrite(STDERR, $usage);
            exit(2);
        }
        $baseline = $value;
    } elseif ($arg === '--write-baseline') {
        $writeBaseline = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        fwrite(STDOUT, $usage);
        exit(0);
    } else {
        fwrite(STDERR, $usage);
        exit(2);
    }
    $i++;
}

if ($generated === null) {
    fwrite(STDERR, $usage);
    exit(2);
}
if (! is_dir($generated)) {
    fwrite(STDERR, 'regen-check: the generated app folder '.$generated.' does not exist.'."\n");
    exit(2);
}

try {
    $check = new RegenCheck(rtrim(str_replace('\\', '/', $generated), '/'), getcwd());
    $results = $check->compare();
    if ($writeBaseline) {
        $check->writeBaseline($baseline, $results);
        fwrite(STDOUT, 'Wrote '.count(RegenCheck::driftPaths($results)).' known drift path(s) to '.$baseline."\n");
        exit(0);
    }
    $gate = $check->gate($baseline);
} catch (RegenCheckError $e) {
    fwrite(STDERR, 'regen-check: '.$e->getMessage()."\n");
    exit(2);
}

foreach ($gate['report'] as $line) {
    // RELOCATED is expected placement (WELD keeps the kit file under
    // deploy/), not drift: print it like IDENTICAL and say where it moved.
    if (str_starts_with($line, RegenCheck::STATUS_RELOCATED.' ')) {
        $line .= ' (relocated under deploy/)';
    }
    fwrite(STDOUT, $line."\n");
}
fflush(STDOUT);
if ($gate['code'] === 0) {
    fwrite(STDOUT, 'No new drift: every DIFFERS and MISSING_IN_WELD path is in the baseline.'."\n");
    exit(0);
}
fwrite(STDERR, 'NEW DRIFT (not in the baseline; fix it or refresh with --write-baseline):'."\n");
foreach ($gate['newDrift'] as $path) {
    fwrite(STDERR, '  '.$path."\n");
}
exit(1);
