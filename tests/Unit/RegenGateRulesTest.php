<?php

/*
|--------------------------------------------------------------------------
| When the regen gate runs (BEST_PRACTICES W3)
|--------------------------------------------------------------------------
|
| Every tag, the nightly schedule and Run pipeline always; a push only
| when it touches a path the gate compares, or the gate itself. Only the
| network steps retry, so drift is never retried into green, and the job
| is interruptible, so a newer push can cancel a superseded pipeline.
| .deploy never runs from the schedule. symfony/yaml comes with the dev
| packages (PROCTOR and Sail require it). A gate that runs on every
| docs push is a smoke alarm that goes off for toast.
*/

use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, mixed>
 */
function weldCiJob(string $name): array
{
    $ci = Yaml::parseFile(dirname(__DIR__, 2).'/.gitlab-ci.yml');

    expect($ci)->toBeArray()->toHaveKey($name);

    return $ci[$name];
}

it('always runs the gate on a tag, the schedule and Run pipeline', function () {
    $rules = weldCiJob('regen-gate')['rules'] ?? null;

    expect($rules)->toBeArray()->toHaveCount(3)
        ->and($rules[0])->toBe(['if' => '$PREFLIGHT_TIER != "full"', 'when' => 'never'])
        ->and($rules[1])->toBe(['if' => '$CI_COMMIT_TAG'])
        ->and($rules[2])->toBe(['if' => '$CI_PIPELINE_SOURCE == "schedule" || $CI_PIPELINE_SOURCE == "web"']);
});

it('never runs the gate on a push: the fast tier skips it', function () {
    $rules = weldCiJob('regen-gate')['rules'];

    expect($rules[0])->toBe(['if' => '$PREFLIGHT_TIER != "full"', 'when' => 'never']);

    foreach ($rules as $rule) {
        expect(str_contains((string) ($rule['if'] ?? ''), 'push'))->toBeFalse('a rule still runs the gate on a push');
        expect($rule)->not->toHaveKey('changes');
    }
});

it('is interruptible and retries only the network steps', function () {
    $job = weldCiJob('regen-gate');
    $script = $job['script'];

    expect($job['interruptible'] ?? null)->toBeTrue()
        ->and($script[0])->toContain('retry3()')
        ->and($script)->toContain('retry3 composer install --no-interaction --no-progress --prefer-dist')
        ->and($script)->toContain('cd /tmp/sourdough && retry3 composer install --no-interaction --no-progress --prefer-dist')
        ->and($script)->toContain('php bin/sourdough new /tmp/regen --inertia --no-advisor --no-input')
        ->and($script)->toContain('cd "$CI_PROJECT_DIR" && php bin/regen-check.php --generated /tmp/regen');

    $clone = array_values(array_filter($script, fn (string $line): bool => str_contains($line, 'git clone')));
    expect($clone)->toHaveCount(1)
        ->and($clone[0])->toStartWith('retry3 ')
        ->and($clone[0])->toContain('https://github.com/waynestate/sourdough.git');

    foreach ($script as $line) {
        if (str_contains($line, 'regen-check.php') || str_contains($line, 'bin/sourdough new')) {
            expect($line)->not->toContain('retry3', 'the bake and the check must run once: drift is never retried into green');
        }
    }
    expect($job)->not->toHaveKey('retry');
});

it('never deploys from the schedule', function () {
    $rules = weldCiJob('.deploy')['rules'];

    expect($rules[0])->toBe(['if' => '$CI_PIPELINE_SOURCE == "schedule"', 'when' => 'never']);
});
