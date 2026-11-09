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

    expect($rules)->toBeArray()
        ->and($rules[0])->toBe(['if' => '$CI_COMMIT_TAG'])
        ->and($rules[1])->toBe(['if' => '$CI_PIPELINE_SOURCE == "schedule" || $CI_PIPELINE_SOURCE == "web"']);
});

it('runs the gate on a push only when a compared path changes', function () {
    $rules = weldCiJob('regen-gate')['rules'];

    expect($rules)->toHaveCount(3)
        ->and($rules[2]['if'])->toBe('$CI_PIPELINE_SOURCE == "push"')
        ->and($rules[2]['changes']['paths'])->toBe([
            '{.*,*.json,*.js,*.ts,*.xml,*.neon,*.lock,artisan,AGENTS.md,CLAUDE.md}',
            '{.ai,.sourdough,app,bootstrap,config,database,deploy,public,resources,routes,scripts,storage,stubs,tests,tools}/**/*',
            'bin/regen-check.php',
        ]);
});

it('covers every top-level folder the bake writes outside the product files', function () {
    // The folders a sourdough new --inertia bake writes (WELD keeps the
    // Squire kit's under deploy/), less the product files. A new one needs
    // a place in the push rule's folder list.
    $folders = weldCiJob('regen-gate')['rules'][2]['changes']['paths'][1];

    foreach (['.ai', '.sourdough', 'app', 'bootstrap', 'config', 'database', 'deploy', 'public', 'resources', 'routes', 'scripts', 'storage', 'stubs', 'tests'] as $folder) {
        expect(str_contains($folders, '{'.$folder.',') || str_contains($folders, ','.$folder.',') || str_contains($folders, ','.$folder.'}'))
            ->toBeTrue("the regen gate's push rule no longer watches {$folder}/");
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
