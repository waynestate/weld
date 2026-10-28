<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * weld:doctor strikes an arc across the setup and reports every joint.
 * A green run exits 0; any FAIL exits 1; --json hands the report to a
 * machine instead of a human.
 */
class DoctorCommandTest extends TestCase
{
    public function test_a_green_run_exits_zero()
    {
        $this->artisan('weld:doctor')
            ->expectsOutputToContain('PASS  php-version')
            ->assertExitCode(0);
    }

    public function test_an_empty_app_key_fails()
    {
        config(['app.key' => '']);

        $this->artisan('weld:doctor')
            ->expectsOutputToContain('FAIL  app-key')
            ->assertExitCode(1);
    }

    public function test_debug_in_production_fails()
    {
        app()->detectEnvironment(fn (): string => 'production');
        config(['app.debug' => true]);

        $this->artisan('weld:doctor')
            ->expectsOutputToContain('FAIL  debug-in-production')
            ->assertExitCode(1);
    }

    public function test_json_output_decodes_and_has_the_php_version_entry()
    {
        $pending = $this->artisan('weld:doctor', ['--json' => true]);

        $exitCode = $pending->run();
        $results = json_decode($pending->output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($results);
        $this->assertCount(7, $results);

        $phpVersion = collect($results)->firstWhere('id', 'php-version');

        $this->assertNotNull($phpVersion, 'the --json report is missing its php-version entry');
        $this->assertSame(['id', 'status', 'message'], array_keys($phpVersion));
        $this->assertContains($phpVersion['status'], ['PASS', 'WARN', 'FAIL']);
    }
}
