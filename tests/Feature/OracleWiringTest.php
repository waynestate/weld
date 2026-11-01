<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * WELD ships with the Oracle connection wired but dormant: with the
 * ORACLE_* settings empty, nothing connects, so nothing can fail. These
 * tests run in a PHP that has no oci8 extension at all, which is exactly
 * what a developer's machine and the light compose image look like.
 */
class OracleWiringTest extends TestCase
{
    public function test_the_app_boots_with_no_oracle_settings()
    {
        $this->get('/up')->assertStatus(200);
    }

    public function test_the_oracle_connection_is_defined_with_the_oracle_driver()
    {
        $connection = config('database.connections.oracle');

        $this->assertIsArray($connection);
        $this->assertSame('oracle', $connection['driver']);
    }

    public function test_the_default_connection_is_not_oracle()
    {
        $this->assertNotSame('oracle', config('database.default'));
    }

    public function test_nothing_connects_to_oracle_after_boot()
    {
        $this->assertArrayNotHasKey('oracle', DB::getConnections());
    }
}
