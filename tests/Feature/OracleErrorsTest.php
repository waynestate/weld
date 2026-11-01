<?php

namespace Tests\Feature;

use App\Support\OracleErrors;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\LogManager;
use Mockery;
use Tests\TestCase;

/**
 * The common Oracle errors get one plain sentence: what to check, never
 * the raw message and never a value. The sentence lands in the log on
 * top of the normal report, which keeps going.
 */
class OracleErrorsTest extends TestCase
{
    public function test_the_driver_missing_cases_get_the_driver_sentence()
    {
        $expected = 'Oracle is set up for this app, but this PHP has no Oracle driver (oci8). Deploy to Citadel, which has it, or use the WSU Sail or Docker image with Oracle.';

        $this->assertSame($expected, OracleErrors::describe(new \Error('Undefined constant "Yajra\Pdo\OCI_DEFAULT"')));
        $this->assertSame($expected, OracleErrors::describe(new \Error('Call to undefined function oci_connect()')));
        $this->assertSame($expected, OracleErrors::describe(new \Error('Call to undefined function OCIConnect()')));
        $this->assertSame($expected, OracleErrors::describe(new \Error("Undefined constant 'OCI_SYSDATE'")));
    }

    public function test_each_ora_code_gets_its_sentence()
    {
        $cases = [
            'ORA-12154: TNS:could not resolve the connect identifier' => 'Oracle could not find the connect name in ORACLE_TNS. Use host:port/service or a full descriptor.',
            'ORA-12514: TNS:could not resolve the connect identifier' => 'The Oracle listener answered but that service is not available. Check ORACLE_SERVICE_NAME; the database may be in maintenance.',
            'ORA-12541: TNS:no listener' => 'There is no Oracle listener at that address. Check the host and port; the database may be down.',
            'ORA-01017: invalid username/password; logon denied' => 'Oracle refused the username or password. Check ORACLE_USERNAME and ORACLE_PASSWORD.',
            'ORA-01031: insufficient privileges' => 'The login worked, but this account lacks permission on that table or view (ORA-1031). Ask the data owner for the grant.',
            'ORA-00942: table or view does not exist' => 'The login worked, but this account lacks permission on that table or view (ORA-942). Ask the data owner for the grant.',
        ];

        foreach ($cases as $message => $expected) {
            $this->assertSame($expected, OracleErrors::describe(new \RuntimeException($message)), $message);
        }
    }

    public function test_an_unrelated_exception_gets_no_sentence()
    {
        $this->assertNull(OracleErrors::describe(new \RuntimeException('ORA-99999: no such case')));
        $this->assertNull(OracleErrors::describe(new \RuntimeException('ORA-125410: no such case')));
        $this->assertNull(OracleErrors::describe(new \RuntimeException('oci_connect failed somewhere in the app')));
        $this->assertNull(OracleErrors::describe(new \RuntimeException('plain app failure')));
    }

    public function test_the_reportable_callback_logs_the_sentence_and_normal_reporting_continues()
    {
        $log = Mockery::mock(LogManager::class);
        $this->app->instance('log', $log);
        $e = new \Error('Undefined constant "Yajra\Pdo\OCI_DEFAULT"');

        // Mockery::close() in tearDown verifies each expectation, so the
        // first proves the sentence was logged and the second proves the
        // normal report kept going after it.
        $log->shouldReceive('error')->once()->with(
            '[oracle] Oracle is set up for this app, but this PHP has no Oracle driver (oci8). Deploy to Citadel, which has it, or use the WSU Sail or Docker image with Oracle.'
        );
        $log->shouldReceive('error')->once()
            ->with($e->getMessage(), Mockery::on(fn ($context) => is_array($context)));

        $this->app->make(ExceptionHandler::class)->report($e);
    }

    public function test_an_unrelated_exception_logs_no_oracle_sentence()
    {
        $log = Mockery::mock(LogManager::class);
        $this->app->instance('log', $log);
        $e = new \RuntimeException('boom');
        // A single expectation: an extra call, such as an [oracle] line
        // for this unrelated error, fails the mock on the spot.
        $log->shouldReceive('error')->once()
            ->with('boom', Mockery::on(fn ($context) => is_array($context)));

        $this->app->make(ExceptionHandler::class)->report($e);
    }
}
