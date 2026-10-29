<?php

declare(strict_types=1);

/**
 * proctor-check.php: run PROCTOR's version policy check against WELD.
 *
 * Why this file exists: PROCTOR 0.1.0's own bin (vendor/bin/proctor) looks
 * for its autoloader at <package>/vendor/autoload.php, a path that only
 * exists when proctor is the project root. Installed as a dependency, the
 * file is missing and the command exits 2 before it checks anything. This
 * launcher runs the very same check through the package's public classes,
 * with the same options and exit codes as the bin.
 *
 * When proctor ships a bin that finds the host app's autoloader, delete
 * this file and point the "proctor" composer script back at
 * "proctor check --policy=vendor/waynestate/creed/policy/policy.json".
 *
 * Usage:
 *   php bin/proctor-check.php [--path=<dir>] [--format=text|json|codequality]
 *       [--policy=<file>] [--today=YYYY-MM-DD] [--timeout=<seconds>]
 *
 * Exit codes: 0 pass (warnings allowed), 1 one or more failures,
 * 2 tool error.
 */

use Waynestate\Proctor\Check\CheckRunner;
use Waynestate\Proctor\Check\ComposerAuditCheck;
use Waynestate\Proctor\Check\Finding;
use Waynestate\Proctor\Check\LockCommittedCheck;
use Waynestate\Proctor\Check\OverridesCheck;
use Waynestate\Proctor\Check\PhpFloorCheck;
use Waynestate\Proctor\Check\UnboundedConstraintsCheck;
use Waynestate\Proctor\Override\OverrideFile;
use Waynestate\Proctor\Policy\JsonFilePolicySource;
use Waynestate\Proctor\Policy\PolicyException;
use Waynestate\Proctor\Report\CodeQualityWriter;
use Waynestate\Proctor\Report\JsonWriter;
use Waynestate\Proctor\Report\Report;
use Waynestate\Proctor\Report\TextWriter;
use Waynestate\Proctor\ToolError;

require dirname(__DIR__).'/vendor/autoload.php';

// Defaults: check this repository, with the CREED policy it carries as a
// dev dependency. Every option mirrors the one PROCTOR's bin accepts.
$options = [
    'path' => dirname(__DIR__),
    'format' => 'text',
    'policy' => dirname(__DIR__).'/vendor/waynestate/creed/policy/policy.json',
    'today' => null,
    'timeout' => '300',
];

foreach (array_slice($argv, 1) as $argument) {
    if (! preg_match('/^--([a-z]+)=(.+)$/', $argument, $match)) {
        fwrite(STDERR, "Unknown argument: {$argument}\n");
        exit(2);
    }
    if (! array_key_exists($match[1], $options)) {
        fwrite(STDERR, "Unknown option: --{$match[1]}\n");
        exit(2);
    }
    $options[$match[1]] = $match[2];
}

if (! in_array($options['format'], ['text', 'json', 'codequality'], true)) {
    fwrite(STDERR, sprintf(
        'Invalid format "%s". Allowed values: text, json, codequality.',
        $options['format']
    )."\n");
    exit(2);
}

if ($options['policy'] === '') {
    fwrite(STDERR, "Missing required option --policy=<file>.\n");
    exit(2);
}

$today = new DateTimeImmutable('today');
if ($options['today'] !== null) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $options['today']);
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $options['today']) || $parsed === false) {
        fwrite(STDERR, sprintf(
            'Invalid --today value "%s". Expected format: YYYY-MM-DD.',
            $options['today']
        )."\n");
        exit(2);
    }
    $today = $parsed;
}

if (! preg_match('/^\d+$/', $options['timeout']) || (int) $options['timeout'] < 1) {
    fwrite(STDERR, 'Invalid --timeout value. Expected a positive integer number of seconds.'."\n");
    exit(2);
}
$timeout = (int) $options['timeout'];

try {
    $policy = new JsonFilePolicySource((string) $options['policy']);
} catch (PolicyException $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(2);
}

$appPath = (string) $options['path'];

try {
    $overrides = OverrideFile::load($appPath);
    $runner = new CheckRunner(
        $appPath,
        $policy,
        $today,
        [
            new PhpFloorCheck,
            new UnboundedConstraintsCheck,
            new LockCommittedCheck,
            new ComposerAuditCheck,
            new OverridesCheck,
        ],
        $overrides,
        $timeout,
    );
    $findings = $runner->run();
} catch (ToolError $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    if ($options['format'] === 'codequality') {
        echo (new CodeQualityWriter)->writeToolError($e->getMessage()), "\n";
    }
    exit(2);
}

$hasFail = false;
foreach ($findings as $finding) {
    if ($finding->status === Finding::STATUS_FAIL) {
        $hasFail = true;
    }
}

$report = new Report(
    $appPath,
    $today,
    $findings,
    $overrides,
    $hasFail ? Report::EXIT_FAIL : Report::EXIT_PASS,
);

try {
    $writer = match ($options['format']) {
        'json' => new JsonWriter,
        'codequality' => new CodeQualityWriter,
        default => new TextWriter,
    };
    echo $writer->write($report), "\n";
} catch (ToolError $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(2);
}

exit($report->exitCode);
