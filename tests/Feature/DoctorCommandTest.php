<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * weld:doctor strikes an arc across the local setup and reports every
 * joint. A green run exits 0; any FAIL exits 1; --json hands the report to
 * a machine instead of a human. The standards checks (PHP version, debug,
 * session cookies) live in PROCTOR, and the report points there.
 */
class DoctorCommandTest extends TestCase
{
    public function test_a_green_run_exits_zero()
    {
        $this->artisan('weld:doctor')
            ->expectsOutputToContain('PASS  app-key')
            ->assertExitCode(0);
    }

    public function test_an_empty_app_key_fails()
    {
        config(['app.key' => '']);

        $this->artisan('weld:doctor')
            ->expectsOutputToContain('FAIL  app-key')
            ->assertExitCode(1);
    }

    public function test_the_report_points_at_proctor_for_the_standards_checks()
    {
        $this->artisan('weld:doctor')
            ->expectsOutputToContain('Standards (PHP version, debug, session cookies): run composer proctor')
            ->assertExitCode(0);
    }

    public function test_json_output_decodes_and_has_the_setup_entries()
    {
        $output = new BufferedOutput;

        $exitCode = $this->app->make(Kernel::class)
            ->call('weld:doctor', ['--json' => true], $output);

        $results = json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($results);
        $this->assertCount(4, $results);
        $this->assertSame(
            ['app-key', 'storage-writable', 'database', 'migrations'],
            array_column($results, 'id'),
        );

        $appKey = null;

        foreach ($results as $entry) {
            if ($entry['id'] === 'app-key') {
                $appKey = $entry;

                break;
            }
        }

        $this->assertNotNull($appKey, 'the --json report is missing its app-key entry');
        $this->assertSame(['id', 'status', 'message'], array_keys($appKey));
        $this->assertContains($appKey['status'], ['PASS', 'WARN', 'FAIL']);
    }
}
