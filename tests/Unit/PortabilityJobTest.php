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
        ->toBe('$CI_SERVER_FQDN/enterprise-apps/preflight/portability@0.4.0');
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

it('starts the image\'s own entrypoint, in its own session, behind a pipe the app owns', function () {
    expect(weldPortabilityInputs()['inputs']['start_command'])
        ->toBe("exec setsid bash -c 'exec > >(exec cat) 2>&1; exec squire-entrypoint'");
});

it('runs on main and tags, waits for a click on a merge request, and never runs from the schedule', function () {
    expect(weldPortabilityCi()['portability']['rules'])->toBe([
        ['if' => '$CI_PIPELINE_SOURCE == "schedule"', 'when' => 'never'],
        ['if' => '$CI_COMMIT_TAG'],
        ['if' => '$CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH'],
        ['if' => '$CI_PIPELINE_SOURCE == "merge_request_event"', 'when' => 'manual'],
    ]);
});

it('does not block yet, can be cancelled by a newer push, and cleans its Buildah storage', function () {
    $job = weldPortabilityCi()['portability'];

    expect($job['allow_failure'])->toBeTrue()
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
