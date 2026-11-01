<?php

namespace Tests\Feature;

use App\Console\Commands\OracleCheck;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * oracle:check is a thin wire to Squire's shared check file: it runs
 * deploy/oracle-check.php, streams the plain-language lines, and returns
 * the check's exit code. Until a kit refresh puts the file in deploy/,
 * the command says so and exits 0, so nothing local can fail on Oracle.
 */
class OracleCheckTest extends TestCase
{
    public function test_an_absent_check_file_prints_the_hint_and_exits_zero()
    {
        $this->artisan('oracle:check')
            ->expectsOutput('The Oracle check file is not here yet. Run "vendor/bin/squire update" to get it.')
            ->assertExitCode(0);
    }

    public function test_a_check_that_reports_a_problem_streams_the_line_and_exits_one()
    {
        $stub = $this->stubCheckScript();

        try {
            $this->app->bind(OracleCheck::class, fn () => new StubOracleCheck($stub));

            $output = new BufferedOutput;
            $exitCode = $this->app->make(Kernel::class)->call('oracle:check', [], $output);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('[oracle] stub', $output->fetch());
        } finally {
            unlink($stub);
        }
    }

    /**
     * A stub of Squire's check file: one line and exit 1, in a temp
     * directory, standing in for deploy/oracle-check.php.
     */
    private function stubCheckScript(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'weld-oracle-check-');

        file_put_contents($path, '<?php echo "[oracle] stub\n"; exit(1);');

        return $path;
    }
}

/**
 * The real oracle:check pointed at a stub check script, for the tests.
 */
class StubOracleCheck extends OracleCheck
{
    public function __construct(private string $scriptPath)
    {
        parent::__construct();
    }

    protected function checkScriptPath(): string
    {
        return $this->scriptPath;
    }
}
