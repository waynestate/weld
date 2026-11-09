<?php

/*
|--------------------------------------------------------------------------
| The portability job (ADR-0010; BEST_PRACTICES W10, W13, W14, W16, W17)
|--------------------------------------------------------------------------
|
| Preflight's portability component builds the portable image and boots it
| the way OpenShift, Coolify and plain Docker would. These tests hold the
| include and the job's own setup in .gitlab-ci.yml, so the checks the
| practices cite cannot drop out quietly: the image it builds, the base it
| builds first, the checks it runs, and the empty proxy lists that make the
| forged headers come from a stranger. A probe that skips the hard checks
| is a weld inspected from across the parking lot.
*/

use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, mixed>
 */
function weldPortabilityCi(): array
{
    $ci = Yaml::parseFile(dirname(__DIR__, 2).'/.gitlab-ci.yml');

    expect($ci)->toBeArray()->toHaveKey('include');

    return $ci;
}

/**
 * @return array<string, mixed>
 */
function weldPortabilityInputs(): array
{
    $found = [];
    foreach (weldPortabilityCi()['include'] as $include) {
        if (is_array($include) && str_contains((string) ($include['component'] ?? ''), '/preflight/portability@')) {
            $found[] = $include;
        }
    }

    expect($found)->toHaveCount(1, '.gitlab-ci.yml includes the preflight/portability component once');

    return $found[0];
}

it('pins the portability component at the Preflight tag every other component uses', function () {
    expect(weldPortabilityInputs()['component'])
        ->toBe('$CI_SERVER_FQDN/enterprise-apps/preflight/portability@0.5.0');
});

it('builds the portable image from the kit in deploy/, on the base the job builds first', function () {
    $inputs = weldPortabilityInputs()['inputs'];

    expect($inputs['context'])->toBe('.')
        ->and($inputs['dockerfile'])->toBe('deploy/.squire/portable.Dockerfile')
        ->and($inputs['app_root'])->toBe('/citadel/src')
        ->and($inputs['build_args'])->toContain('SQUIRE_KIT_DIR=deploy')
        ->and($inputs['build_args'])->toContain('BASE_IMAGE=localhost/weld-portable-base:8.4');

    $setup = implode("\n", weldPortabilityCi()['portability']['before_script']);

    expect($setup)->toContain('deploy/.squire/base-fallback.Dockerfile')
        ->and($setup)->toContain('-t localhost/weld-portable-base:8.4')
        ->and($setup)->toContain('chmod -R go-w .');
});

it('passes all three kit images through the proxy by swapping only the registry part', function () {
    $setup = implode("\n", weldPortabilityCi()['portability']['before_script']);

    // Squire names each image in full (docker.io/library/php:...). Prefixing
    // the proxy onto that would ask the proxy for docker.io/library/... and
    // fail, so the Docker Hub part is cut first, as Squire's guide shows.
    expect($setup)->toContain('for arg in PHP_IMAGE NODE_IMAGE COMPOSER_IMAGE; do')
        ->and($setup)->toContain('ref="$proxy${ref#docker.io/library/}"')
        ->and($setup)->toContain('-f "$base"')
        // The kit file is built as it ships: no rewritten copy.
        ->and($setup)->not->toContain('--from=');

    expect((string) file_get_contents(dirname(__DIR__, 2).'/deploy/.squire/base-fallback.Dockerfile'))
        ->toMatch('/^ARG PHP_IMAGE=docker\.io\/library\//m')
        ->toMatch('/^ARG NODE_IMAGE=docker\.io\/library\//m')
        ->toMatch('/^ARG COMPOSER_IMAGE=docker\.io\/library\//m');
});

it('starts the image\'s own entrypoint with no shim, as the platforms do', function () {
    // Preflight 0.5.0's launcher gives each replica its own session and a
    // stdio pipe the uid owns. A setsid shim on top forks Apache away from
    // the replica's main process, and health fails.
    expect(weldPortabilityInputs()['inputs'])->not->toHaveKey('start_command');
});

it('runs on main and tags, waits for a click on a merge request, and never runs from the schedule', function () {
    expect(weldPortabilityCi()['portability']['rules'])->toBe([
        ['if' => '$CI_PIPELINE_SOURCE == "schedule"', 'when' => 'never'],
        ['if' => '$CI_COMMIT_TAG'],
        ['if' => '$CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH'],
        ['if' => '$CI_PIPELINE_SOURCE == "merge_request_event"', 'when' => 'manual'],
    ]);
});

it('blocks, can be cancelled by a newer push, and cleans its Buildah storage', function () {
    $job = weldPortabilityCi()['portability'];

    expect($job)->not->toHaveKey('allow_failure')
        ->and($job['interruptible'])->toBeTrue()
        ->and($job['after_script'])->toBe([
            'buildah rm --all >/dev/null 2>&1 || true',
            'buildah rmi --all --force >/dev/null 2>&1 || true',
        ]);
});

it('runs every check but session-hop', function () {
    expect(weldPortabilityInputs()['inputs']['checks'])
        ->toBe(['health', 'env-only', 'forwarded-headers', 'logs', 'sigterm']);
});

it('boots in production on a key made for the job, trusting no proxy', function () {
    $env = weldPortabilityInputs()['inputs']['env'];

    expect($env)->toContain('APP_ENV=production')
        ->and($env)->toContain('APP_DEBUG=false')
        ->and($env)->toContain('APP_KEY=${PORTABILITY_APP_KEY}')
        ->and($env)->toContain('LOG_CHANNEL=stderr')
        ->and($env)->toContain('TRUSTED_PROXIES=')
        ->and($env)->toContain('HELMET_PROXIES=');

    expect(implode("\n", weldPortabilityCi()['portability']['before_script']))
        ->toContain('export PORTABILITY_APP_KEY');
});
