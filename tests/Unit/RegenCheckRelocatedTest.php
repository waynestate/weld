<?php

namespace Tests\Unit;

use App\Support\RegenCheck;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/*
 * The regen gate's fourth status: a manifest path whose root file WELD keeps
 * under deploy/ (Squire's kit placement) is RELOCATED when the deploy/ copy
 * is byte-identical to the manifest entry, and still MISSING_IN_WELD when it
 * changed. Temp-dir fixtures stand in for the generated app and WELD, the
 * same way RegenCheckTest builds its trees.
 */

function relocatedTempDir(): string
{
    $dir = sys_get_temp_dir().'/regen-check-relocated-'.bin2hex(random_bytes(8));
    mkdir($dir, 0755, true);

    return $dir;
}

function relocatedPut(string $root, string $relative, string $content): void
{
    $path = $root.'/'.$relative;
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
}

function relocatedRemoveDir(string $dir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($dir);
}

function relocatedWeld(array $files): string
{
    $root = relocatedTempDir();
    foreach ($files as $relative => $content) {
        relocatedPut($root, $relative, $content);
    }

    return $root;
}

function relocatedGenerated(array $files): string
{
    $root = relocatedTempDir();
    relocatedPut($root, '.sourdough/manifest.json', json_encode(['files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).'
');

    return $root;
}

function relocatedBaseline(array $paths): string
{
    $path = relocatedTempDir().'/regen-baseline.json';
    file_put_contents($path, json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).'
');

    return $path;
}

it('reports a kit file kept under deploy/ with identical bytes as relocated', function () {
    $build = "#!/bin/sh\necho weld build";
    $weld = relocatedWeld(['deploy/build.sh' => $build]);
    $generated = relocatedGenerated([
        'build.sh' => ['class' => 'managed', 'sha256' => hash('sha256', $build)],
    ]);

    try {
        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();

        $this->assertSame([['path' => 'build.sh', 'status' => RegenCheck::STATUS_RELOCATED, 'class' => 'managed']], $results);
        $this->assertSame([], RegenCheck::driftPaths($results));
    } finally {
        relocatedRemoveDir($weld);
        relocatedRemoveDir($generated);
    }
});

it('keeps a changed deploy/ copy as missing-in-weld drift', function () {
    $build = "#!/bin/sh\necho weld build";
    $weld = relocatedWeld(['deploy/build.sh' => "#!/bin/sh\necho something else"]);
    $generated = relocatedGenerated([
        'build.sh' => ['class' => 'managed', 'sha256' => hash('sha256', $build)],
    ]);

    try {
        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();

        $this->assertSame([['path' => 'build.sh', 'status' => RegenCheck::STATUS_MISSING_IN_WELD, 'class' => 'managed']], $results);
        $this->assertSame(['build.sh'], RegenCheck::driftPaths($results));
    } finally {
        relocatedRemoveDir($weld);
        relocatedRemoveDir($generated);
    }
});

it('passes the gate with an empty baseline when the only change is relocation', function () {
    $build = "#!/bin/sh\necho weld build";
    $weld = relocatedWeld(['deploy/build.sh' => $build]);
    $generated = relocatedGenerated([
        'build.sh' => ['class' => 'managed', 'sha256' => hash('sha256', $build)],
    ]);
    $baseline = relocatedBaseline([]);

    try {
        $gate = (new RegenCheck($generated, $weld))->gate($baseline);

        $this->assertSame(0, $gate['code']);
        $this->assertSame([], $gate['newDrift']);
    } finally {
        relocatedRemoveDir($weld);
        relocatedRemoveDir($generated);
        relocatedRemoveDir(dirname($baseline));
    }
});

it('reports a kit file kept under deploy/ with CRLF line endings as relocated', function () {
    $parity = "#!/usr/bin/env pwsh\nWrite-Host weld parity\n";
    $weld = relocatedWeld(['deploy/scripts/parity.ps1' => str_replace("\n", "\r\n", $parity)]);
    $generated = relocatedGenerated([
        'scripts/parity.ps1' => ['class' => 'managed', 'sha256' => hash('sha256', $parity)],
    ]);

    try {
        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();

        $this->assertSame([['path' => 'scripts/parity.ps1', 'status' => RegenCheck::STATUS_RELOCATED, 'class' => 'managed']], $results);
        $this->assertSame([], RegenCheck::driftPaths($results));
    } finally {
        relocatedRemoveDir($weld);
        relocatedRemoveDir($generated);
    }
});

it('keeps a CRLF deploy/ copy with a changed line as missing-in-weld drift', function () {
    $parity = "#!/usr/bin/env pwsh\nWrite-Host weld parity\n";
    $changed = "#!/usr/bin/env pwsh\nWrite-Host something else\n";
    $weld = relocatedWeld(['deploy/scripts/parity.ps1' => str_replace("\n", "\r\n", $changed)]);
    $generated = relocatedGenerated([
        'scripts/parity.ps1' => ['class' => 'managed', 'sha256' => hash('sha256', $parity)],
    ]);

    try {
        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();

        $this->assertSame([['path' => 'scripts/parity.ps1', 'status' => RegenCheck::STATUS_MISSING_IN_WELD, 'class' => 'managed']], $results);
        $this->assertSame(['scripts/parity.ps1'], RegenCheck::driftPaths($results));
    } finally {
        relocatedRemoveDir($weld);
        relocatedRemoveDir($generated);
    }
});
