<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * config/helmet.php keeps TRUSTED_PROXIES as a documented alias for
 * HELMET_PROXIES. Each check requires the file under a temporary
 * environment and inspects the array it returns, so the alias rules are
 * pinned without booting the app.
 */
class HelmetConfigAliasTest extends TestCase
{
    public function test_no_proxies_key_when_neither_variable_is_set(): void
    {
        $this->assertArrayNotHasKey('proxies', $this->configUnder(null, null));
    }

    public function test_no_proxies_key_when_helmet_proxies_is_set(): void
    {
        // HELMET_PROXIES wins, so the file stays out of the way and lets the
        // package parse its own variable (and any HELMET_PRESET) as usual.
        $this->assertArrayNotHasKey('proxies', $this->configUnder('10.0.0.1', '127.0.0.1'));
    }

    public function test_the_parsed_alias_list_when_only_the_alias_is_set(): void
    {
        $config = $this->configUnder(' 10.0.0.1 , , 192.168.1.0/24 ', null);

        $this->assertSame(['10.0.0.1', '192.168.1.0/24'], $config['proxies']);
    }

    public function test_a_set_but_empty_alias_trusts_nobody(): void
    {
        $this->assertSame([], $this->configUnder('', null)['proxies']);
    }

    /**
     * Require config/helmet.php with TRUSTED_PROXIES and HELMET_PROXIES
     * pinned to the given values (null removes the variable from every
     * source env() reads), and restore whatever was there before.
     */
    private function configUnder(?string $trustedProxies, ?string $helmetProxies): array
    {
        $saved = [
            'TRUSTED_PROXIES' => $this->readVar('TRUSTED_PROXIES'),
            'HELMET_PROXIES' => $this->readVar('HELMET_PROXIES'),
            // A bare unit test has no app container, so base_path() cannot
            // run: name a policy path and the file never calls it.
            'HELMET_POLICY_PATH' => $this->readVar('HELMET_POLICY_PATH'),
        ];

        $this->setVar('HELMET_POLICY_PATH', 'policy.json');
        $this->setVar('TRUSTED_PROXIES', $trustedProxies);
        $this->setVar('HELMET_PROXIES', $helmetProxies);

        try {
            return require __DIR__.'/../../config/helmet.php';
        } finally {
            $this->setVar('TRUSTED_PROXIES', $saved['TRUSTED_PROXIES']);
            $this->setVar('HELMET_PROXIES', $saved['HELMET_PROXIES']);
            $this->setVar('HELMET_POLICY_PATH', $saved['HELMET_POLICY_PATH']);
        }
    }

    private function readVar(string $name): ?string
    {
        $value = getenv($name);
        if (is_string($value)) {
            return $value;
        }

        return $_ENV[$name] ?? $_SERVER[$name] ?? null;
    }

    private function setVar(string $name, ?string $value): void
    {
        putenv($value === null ? $name : $name.'='.$value);

        if ($value === null) {
            unset($_ENV[$name], $_SERVER[$name]);
        } else {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
