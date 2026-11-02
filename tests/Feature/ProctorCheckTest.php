<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * PROCTOR measures WELD against CREED's policy.json. It runs in CI as its
 * own job and as the deploy gate (`composer proctor`), but it is not part of
 * `composer ci`, so nothing in the test suite would notice if the check
 * started failing. This test closes that gap: it runs the same command the
 * composer script runs and fails when PROCTOR does not pass.
 *
 * Warnings pass, failures block: PROCTOR exits 0 while it only warns, as it
 * does today about the PHP floor and the hard-coded proxy address.
 */
class ProctorCheckTest extends TestCase
{
    public function test_proctor_passes_against_creed_policy()
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/proctor', 'check', '--policy=vendor/waynestate/creed/policy/policy.json'],
            $this->app->basePath(),
        );
        $process->setTimeout(120);
        $process->run();

        $this->assertSame(
            0,
            $process->getExitCode(),
            'PROCTOR did not pass: '.$process->getOutput().$process->getErrorOutput(),
        );
    }
}
