<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\RegenCheck;
use App\Support\RegenCheckError;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * RegenCheck against temp-dir fixtures: a "weld" tree standing in for WELD and
 * a "generated" tree whose .sourdough/manifest.json Sourdough would have
 * written. Extends PHPUnit's own TestCase, not the framework one, because the
 * class under test is plain PHP.
 */
class RegenCheckTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }
        $this->tempDirs = [];
    }

    public function test_identical_tree_passes(): void
    {
        $weld = $this->makeWeld(['config/app.php' => 'weld content']);
        $generated = $this->makeGenerated([
            'config/app.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'weld content')],
        ]);
        $baseline = $this->writeBaselineFile([]);

        $gate = (new RegenCheck($generated, $weld))->gate($baseline);

        $this->assertSame(0, $gate['code']);
        $this->assertSame([], $gate['newDrift']);
        $this->assertSame(0, $this->binExitCode($generated, $baseline, $weld));
    }

    public function test_a_crlf_checkout_of_the_lf_bytes_is_identical(): void
    {
        $lf = "#!/usr/bin/env pwsh\nWrite-Host weld parity\n";
        $weld = $this->makeWeld(['scripts/parity.ps1' => str_replace("\n", "\r\n", $lf)]);
        $generated = $this->makeGenerated([
            'scripts/parity.ps1' => ['class' => 'managed', 'sha256' => hash('sha256', $lf)],
        ]);
        $baseline = $this->writeBaselineFile([]);

        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();
        $gate = $check->gate($baseline);

        $this->assertSame([['path' => 'scripts/parity.ps1', 'status' => RegenCheck::STATUS_IDENTICAL, 'class' => 'managed']], $results);
        $this->assertSame(0, $gate['code']);
        $this->assertSame([], $gate['newDrift']);
    }

    public function test_tmp_reverse_direction_and_edges(): void
    {
        $crlf = "#!/usr/bin/env pwsh\r\nWrite-Host weld parity\r\n";
        $lf = str_replace("\r\n", "\n", $crlf);
        $mixed = "#!/usr/bin/env pwsh\r\nWrite-Host weld parity\n";

        $weld = $this->makeWeld(['scripts/parity.ps1' => $lf]);
        $generated = $this->makeGenerated([
            'scripts/parity.ps1' => ['class' => 'managed', 'sha256' => hash('sha256', $crlf)],
        ]);
        $results = (new RegenCheck($generated, $weld))->compare();
        $this->assertSame(RegenCheck::STATUS_IDENTICAL, $results[0]['status']);

        $weld2 = $this->makeWeld(['deploy/scripts/parity.sh' => $lf]);
        $generated2 = $this->makeGenerated([
            'scripts/parity.sh' => ['class' => 'managed', 'sha256' => hash('sha256', $crlf)],
        ]);
        $results2 = (new RegenCheck($generated2, $weld2))->compare();
        $this->assertSame(RegenCheck::STATUS_RELOCATED, $results2[0]['status']);

        $weld3 = $this->makeWeld(['scripts/mixed.txt' => $mixed]);
        $generated3 = $this->makeGenerated([
            'scripts/mixed.txt' => ['class' => 'managed', 'sha256' => hash('sha256', $mixed)],
        ]);
        $results3 = (new RegenCheck($generated3, $weld3))->compare();
        $this->assertSame(RegenCheck::STATUS_IDENTICAL, $results3[0]['status']);

        $weld4 = $this->makeWeld(['scripts/lone.txt' => "a\rb"]);
        $generated4 = $this->makeGenerated([
            'scripts/lone.txt' => ['class' => 'managed', 'sha256' => hash('sha256', 'ab')],
        ]);
        $results4 = (new RegenCheck($generated4, $weld4))->compare();
        $this->assertSame(RegenCheck::STATUS_DIFFERS, $results4[0]['status']);
    }

    public function test_changed_file_fails(): void
    {
        $weld = $this->makeWeld(['config/app.php' => 'weld content']);
        // Sourdough now ships different bytes for the same path.
        $generated = $this->makeGenerated([
            'config/app.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'other content')],
        ]);
        $baseline = $this->writeBaselineFile([]);

        $gate = (new RegenCheck($generated, $weld))->gate($baseline);

        $this->assertSame(1, $gate['code']);
        $this->assertSame(['config/app.php'], $gate['newDrift']);
        $this->assertSame(1, $this->binExitCode($generated, $baseline, $weld));
    }

    public function test_the_same_change_in_the_baseline_passes(): void
    {
        $weld = $this->makeWeld(['config/app.php' => 'weld content']);
        $generated = $this->makeGenerated([
            'config/app.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'other content')],
        ]);
        $baseline = $this->writeBaselineFile(['config/app.php']);

        $gate = (new RegenCheck($generated, $weld))->gate($baseline);

        $this->assertSame(0, $gate['code']);
        $this->assertSame([], $gate['newDrift']);
    }

    public function test_a_product_files_prefix_is_skipped(): void
    {
        $weld = $this->makeWeld([
            '.sourdough/product-files.json' => json_encode(['site/'])."\n",
            'site/index.html' => 'weld front page',
        ]);
        $generated = $this->makeGenerated([
            'site/index.html' => ['class' => 'seeded', 'sha256' => hash('sha256', 'a different front page')],
        ]);
        $baseline = $this->writeBaselineFile([]);

        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();
        $gate = $check->gate($baseline);

        $this->assertSame([], $results, 'the whole site/ prefix must be skipped');
        $this->assertSame(0, $gate['code']);
    }

    public function test_a_nested_vendor_path_is_ignored(): void
    {
        $weld = $this->makeWeld([]);
        $generated = $this->makeGenerated([
            'vendor/some/package/src/File.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'vendor bytes')],
            'resources/node_modules/tool/index.js' => ['class' => 'managed', 'sha256' => hash('sha256', 'node bytes')],
        ]);
        $baseline = $this->writeBaselineFile([]);

        $check = new RegenCheck($generated, $weld);
        $results = $check->compare();
        $gate = $check->gate($baseline);

        $this->assertSame([], $results, 'vendor and node_modules paths at any depth must be ignored');
        $this->assertSame(0, $gate['code']);
    }

    public function test_a_missing_generated_manifest_throws(): void
    {
        $weld = $this->makeWeld([]);
        $generated = $this->tempDir();
        mkdir($generated.'/.sourdough', 0755, true);
        // No manifest.json: the folder is not a Sourdough generation.

        $this->expectException(RegenCheckError::class);
        (new RegenCheck($generated, $weld))->compare();
    }

    public function test_a_missing_generated_manifest_exits_2_from_the_bin_script(): void
    {
        $generated = $this->tempDir();
        mkdir($generated.'/.sourdough', 0755, true);
        $baseline = $this->writeBaselineFile([]);

        $this->assertSame(2, $this->binExitCode($generated, $baseline));
    }

    public function test_write_baseline_records_the_drift_sorted(): void
    {
        $weld = $this->makeWeld(['config/app.php' => 'weld content']);
        $generated = $this->makeGenerated([
            'config/app.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'other content')],
            'app/Later.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'never here')],
            'app/Earlier.php' => ['class' => 'managed', 'sha256' => hash('sha256', 'also never here')],
        ]);
        $baseline = $this->writeBaselineFile(['something-old']);

        $check = new RegenCheck($generated, $weld);
        $check->writeBaseline($baseline, $check->compare());

        $this->assertSame(['app/Earlier.php', 'app/Later.php', 'config/app.php'], json_decode((string) file_get_contents($baseline), true));
    }

    /**
     * Run bin/regen-check.php as a child process and return its exit code.
     * The script takes the WELD side from the working directory, so when a
     * weld fixture is given the process runs with that directory as cwd.
     */
    private function binExitCode(string $generated, string $baseline, ?string $weld = null): int
    {
        $bin = dirname(__DIR__, 2).'/bin/regen-check.php';
        $command = PHP_BINARY.' '.escapeshellarg($bin)
            .' --generated '.escapeshellarg($generated)
            .' --baseline '.escapeshellarg($baseline).' 2>&1';
        $previous = getcwd();
        if ($weld !== null) {
            chdir($weld);
        }
        try {
            exec($command, $output, $code);
        } finally {
            if ($weld !== null) {
                chdir($previous);
            }
        }

        return $code;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function makeWeld(array $files): string
    {
        $root = $this->tempDir();
        foreach ($files as $relative => $content) {
            $this->put($root, $relative, $content);
        }

        return $root;
    }

    /**
     * @param  array<string, array{class: string, sha256: string}>  $files
     */
    private function makeGenerated(array $files): string
    {
        $root = $this->tempDir();
        $this->put($root, '.sourdough/manifest.json', json_encode(['files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $root;
    }

    /**
     * @param  list<string>  $paths
     */
    private function writeBaselineFile(array $paths): string
    {
        $path = $this->tempDir().'/regen-baseline.json';
        file_put_contents($path, json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $path;
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir().'/regen-check-'.bin2hex(random_bytes(8));
        mkdir($dir, 0755, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function put(string $root, string $relative, string $content): void
    {
        $path = $root.'/'.$relative;
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $content);
    }

    private function removeDir(string $dir): void
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
}
