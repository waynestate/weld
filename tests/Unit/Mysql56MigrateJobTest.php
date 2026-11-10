<?php

/*
|--------------------------------------------------------------------------
| The MySQL 5.6 migrate job (BEST_PRACTICES W24, the supply-chain pin W33)
|--------------------------------------------------------------------------
|
| WELD's tests run on SQLite, which takes a JSON column or a long utf8mb4
| index that Citadel's shared MySQL 5.6 rejects at deploy. The mysql56-migrate
| component runs php artisan migrate against a real 5.6.51 service instead, so
| the schema is checked on the metal the gate hangs from. These tests hold the
| include in .gitlab-ci.yml and keep every Preflight component on one tag, so
| the pin cannot drift component by component.
*/

use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, mixed>
 */
function weldMysql56Ci(): array
{
    $ci = Yaml::parseFile(dirname(__DIR__, 2).'/.gitlab-ci.yml');

    expect($ci)->toBeArray()->toHaveKey('include');

    return $ci;
}

/**
 * @return array<string, mixed>
 */
function weldMysql56Include(): array
{
    $found = [];
    foreach (weldMysql56Ci()['include'] as $include) {
        if (is_array($include) && str_contains((string) ($include['component'] ?? ''), '/preflight/mysql56-migrate@')) {
            $found[] = $include;
        }
    }

    expect($found)->toHaveCount(1, '.gitlab-ci.yml includes the preflight/mysql56-migrate component once');

    return $found[0];
}

it('includes the mysql56-migrate component pinned at the release tag', function () {
    expect(weldMysql56Include()['component'])
        ->toBe('$CI_SERVER_FQDN/enterprise-apps/preflight/mysql56-migrate@0.7.0');
});

it('takes the component defaults, passing no inputs', function () {
    // The Dependency Proxy prefix, the digest-pinned images, the amd64 runner
    // tag and the DB_* variables are the component's defaults; WELD needs none
    // of them overridden.
    expect(weldMysql56Include())->not->toHaveKey('inputs');
});

it('pins every Preflight component in the include list to the same tag', function () {
    $tags = [];
    foreach (weldMysql56Ci()['include'] as $include) {
        $component = (string) ($include['component'] ?? '');
        if (! str_contains($component, '/enterprise-apps/preflight/')) {
            continue;
        }
        $tags[] = substr($component, strrpos($component, '@') + 1);
    }

    expect($tags)->not->toBeEmpty()
        ->and(array_unique($tags))->toBe(['0.7.0']);
});
