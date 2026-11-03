<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TrustedProxies;
use PHPUnit\Framework\TestCase;

class TrustedProxiesTest extends TestCase
{
    public function test_unset_variable_falls_back_to_citadel_proxy(): void
    {
        $this->assertSame(['172.18.0.1'], TrustedProxies::fromEnv(null));
    }

    public function test_set_but_empty_trusts_nothing(): void
    {
        $this->assertSame([], TrustedProxies::fromEnv(''));
    }

    public function test_wildcard_trusts_every_proxy(): void
    {
        $this->assertSame('*', TrustedProxies::fromEnv('*'));
    }

    public function test_single_ip(): void
    {
        $this->assertSame(['10.0.0.1'], TrustedProxies::fromEnv('10.0.0.1'));
    }

    public function test_list_with_spaces_and_empty_item(): void
    {
        $this->assertSame(
            ['10.0.0.1', '192.168.1.0/24'],
            TrustedProxies::fromEnv(' 10.0.0.1 , , 192.168.1.0/24 ')
        );
    }

    public function test_cidr_range(): void
    {
        $this->assertSame(['172.16.0.0/12'], TrustedProxies::fromEnv('172.16.0.0/12'));
    }
}
