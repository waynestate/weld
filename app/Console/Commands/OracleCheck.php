<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * A thin wire to Squire's shared check file: it runs deploy/oracle-check.php
 * with the current environment, streams the plain-language lines straight
 * through, and returns the check's exit code (0 when Oracle is not
 * configured or all passed, 1 when a problem was found). WELD does not own
 * the check; a kit refresh (vendor/bin/squire update) puts the file here.
 */
class OracleCheck extends Command
{
    protected $signature = 'oracle:check';

    protected $description = 'Check the Oracle settings and connection, in plain language';

    public function handle(): int
    {
        $script = $this->checkScriptPath();

        if (! is_file($script)) {
            $this->line('The Oracle check file is not here yet. Run "vendor/bin/squire update" to get it.');

            return self::SUCCESS;
        }

        $process = new Process([PHP_BINARY, $script]);

        $process->run(function (string $type, string $buffer): void {
            // Stream the check's lines verbatim; the check file promises to
            // never print a setting value.
            $this->output->write($buffer);
        });

        // null only if the process never ran; the contract is 0 or 1.
        return $process->getExitCode() ?? self::FAILURE;
    }

    /**
     * Where Squire keeps the check file. Tests override this to point a
     * stub script in a temp directory in its place.
     */
    protected function checkScriptPath(): string
    {
        return base_path('deploy/oracle-check.php');
    }
}
