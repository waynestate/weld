<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Thrown when the check itself cannot run: a missing manifest or baseline, an
 * invalid product-files list, or an unwritable path. The bin script maps it
 * to exit code 2.
 */
final class RegenCheckError extends \RuntimeException {}

/**
 * Is WELD still Sourdough's Inertia shape?
 *
 * Compares a freshly generated Sourdough app with WELD, file by file, by
 * SHA-256. Sourdough writes .sourdough/manifest.json into every app it
 * generates: files maps each path to {class, sha256}. This check skips the
 * product files WELD adds on top of the starter (listed in
 * .sourdough/product-files.json as exact paths or dir/ prefixes) and every
 * path with a vendor or node_modules segment at any depth, and reports each
 * remaining path as IDENTICAL, DIFFERS (the WELD file exists with another
 * SHA-256), MISSING_IN_WELD or RELOCATED (the root file is absent from WELD
 * but deploy/ holds a copy that matches apart from line endings, as WELD
 * does for Squire's kit files).
 *
 * The baseline (.sourdough/regen-baseline.json) records the drift already
 * known and accepted: a sorted list of paths. The gate fails only on NEW
 * drift, so it ratchets: refresh it with --write-baseline once the new drift
 * is fixed or accepted.
 */
final class RegenCheck
{
    public const STATUS_IDENTICAL = 'IDENTICAL';

    public const STATUS_DIFFERS = 'DIFFERS';

    public const STATUS_MISSING_IN_WELD = 'MISSING_IN_WELD';

    public const STATUS_RELOCATED = 'RELOCATED';

    /** @var list<string> folder names ignored at any depth */
    private const IGNORED_SEGMENTS = ['vendor', 'node_modules'];

    public function __construct(
        private readonly string $generatedDir,
        private readonly string $weldDir,
    ) {}

    /**
     * The manifest Sourdough wrote into the generated app.
     *
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        $path = $this->generatedDir.'/.sourdough/manifest.json';
        if (! is_file($path)) {
            throw new RegenCheckError('The generated app has no manifest: '.$path.' is missing. Generate the app first (sourdough new).');
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! is_array($data['files'] ?? null)) {
            throw new RegenCheckError($path.' is not a valid manifest: expected an object with a "files" map of path to {class, sha256}.');
        }
        $files = $data['files'];

        /** @var array<string, mixed> $files */
        return $files;
    }

    /**
     * The product files WELD adds on top of the starter: exact paths or dir/ prefixes.
     *
     * @return list<string>
     */
    public function productFiles(): array
    {
        $path = $this->weldDir.'/.sourdough/product-files.json';
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            throw new RegenCheckError($path.' is not valid JSON: expected a list of paths or dir/ prefixes.');
        }

        return array_values(array_filter($data, 'is_string'));
    }

    /**
     * Every manifest path that is neither a product file nor a vendor path, with its status, sorted by path.
     *
     * @return list<array{path: string, status: string, class: string}>
     */
    public function compare(): array
    {
        $productFiles = $this->productFiles();
        $results = [];
        foreach ($this->manifest() as $path => $entry) {
            $path = str_replace('\\', '/', (string) $path);
            if ($this->isIgnoredPath($path) || $this->isProductFile($path, $productFiles)) {
                continue;
            }
            $class = is_array($entry) && is_string($entry['class'] ?? null) ? $entry['class'] : '';
            $sha = is_array($entry) && is_string($entry['sha256'] ?? null) ? strtolower($entry['sha256']) : '';
            $results[] = ['path' => $path, 'status' => $this->statusOf($path, $sha), 'class' => $class];
        }
        usort($results, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $results;
    }

    /**
     * The paths that are not identical, sorted: the current drift.
     *
     * @param  list<array{path: string, status: string, class: string}>  $results
     * @return list<string>
     */
    public static function driftPaths(array $results): array
    {
        $out = [];
        foreach ($results as $result) {
            if ($result['status'] === self::STATUS_DIFFERS || $result['status'] === self::STATUS_MISSING_IN_WELD) {
                $out[] = $result['path'];
            }
        }
        sort($out);

        return $out;
    }

    /**
     * The drift that is not in the baseline yet: what makes the gate fail.
     *
     * @param  list<string>  $drift
     * @param  list<string>  $baseline
     * @return list<string> sorted
     */
    public static function newDrift(array $drift, array $baseline): array
    {
        $known = array_flip($baseline);
        $out = [];
        foreach ($drift as $path) {
            if (! isset($known[$path])) {
                $out[] = $path;
            }
        }
        sort($out);

        return $out;
    }

    /**
     * The recorded drift from the baseline file.
     *
     * @return list<string>
     */
    public static function readBaseline(string $path): array
    {
        if (! is_file($path)) {
            throw new RegenCheckError('The baseline '.$path.' is missing. Restore it, or write a new one with --write-baseline.');
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            throw new RegenCheckError($path.' is not valid JSON: expected a list of paths.');
        }

        return array_values(array_filter($data, 'is_string'));
    }

    /**
     * Write the current drift to the baseline file, sorted, as a JSON list.
     *
     * @param  list<array{path: string, status: string, class: string}>  $results
     */
    public function writeBaseline(string $path, array $results): void
    {
        $drift = self::driftPaths($results);
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RegenCheckError('Cannot create '.$dir.'.');
        }
        $json = json_encode($drift, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        if (file_put_contents($path, $json) === false) {
            throw new RegenCheckError('Cannot write '.$path.'.');
        }
    }

    /**
     * The whole gate: compare, load the baseline, decide.
     *
     * @return array{code: int, report: list<string>, newDrift: list<string>}
     */
    public function gate(string $baselinePath): array
    {
        $results = $this->compare();
        $report = [];
        foreach ($results as $result) {
            $line = $result['status'].' '.$result['path'];
            if ($result['class'] !== '') {
                $line .= ' ('.$result['class'].')';
            }
            $report[] = $line;
        }
        $drift = self::driftPaths($results);
        $baseline = self::readBaseline($baselinePath);
        $new = self::newDrift($drift, $baseline);

        return [
            'code' => $new === [] ? 0 : 1,
            'report' => $report,
            'newDrift' => $new,
        ];
    }

    /** True when a vendor or node_modules folder sits anywhere in the path. */
    private function isIgnoredPath(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, self::IGNORED_SEGMENTS, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $productFiles */
    private function isProductFile(string $path, array $productFiles): bool
    {
        foreach ($productFiles as $entry) {
            if (str_ends_with($entry, '/')) {
                if (str_starts_with($path, $entry)) {
                    return true;
                }

                continue;
            }
            if ($path === $entry) {
                return true;
            }
        }

        return false;
    }

    private function statusOf(string $path, string $sha): string
    {
        $weldPath = $this->weldDir.'/'.$path;
        if (! is_file($weldPath)) {
            // WELD keeps Squire's kit files under deploy/: a copy there that
            // matches (line endings forgiven) is expected placement, not
            // drift. A changed copy is real drift and stays MISSING_IN_WELD.
            $deployPath = $this->weldDir.'/deploy/'.$path;
            if (is_file($deployPath)
                && self::matches((string) file_get_contents($deployPath), $sha)) {
                return self::STATUS_RELOCATED;
            }

            return self::STATUS_MISSING_IN_WELD;
        }

        return self::matches((string) file_get_contents($weldPath), $sha)
            ? self::STATUS_IDENTICAL
            : self::STATUS_DIFFERS;
    }

    /**
     * Line endings are forgiven, as Sourdough's Feed::matches() does, so a
     * CRLF checkout still matches the bytes Sourdough wrote and the other
     * way round.
     */
    private static function matches(string $content, string $sha256): bool
    {
        if (hash('sha256', $content) === $sha256) {
            return true;
        }
        $lf = str_replace("\r\n", "\n", $content);
        if (hash('sha256', $lf) === $sha256) {
            return true;
        }

        return hash('sha256', str_replace("\n", "\r\n", $lf)) === $sha256;
    }
}
