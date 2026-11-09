<?php

/*
|--------------------------------------------------------------------------
| Deprecations fail the suite
|--------------------------------------------------------------------------
|
| From Sourdough's Inertia shape (Sourdough practice 4.13): phpunit.xml
| turns a deprecation that WELD's own code raises or causes into a
| failure, and tests/TestCase.php hands deprecations back to PHPUnit,
| because Laravel's error handler drops them while testing. So the CI
| matrix's newest PHP finds a deprecation before the next PHP upgrade
| does. Deprecated today, gone tomorrow, broken at 2 a.m. the day after.
*/

use Tests\Support\ReportsDeprecationsToPhpUnit;
use Tests\TestCase;

it('fails the run on a deprecation and names the test that raised it', function () {
    $xml = simplexml_load_file(dirname(__DIR__, 2).'/phpunit.xml');

    expect($xml)->not->toBeFalse();
    foreach (['failOnDeprecation', 'failOnPhpunitDeprecation', 'displayDetailsOnTestsThatTriggerDeprecations', 'displayDetailsOnPhpunitDeprecations'] as $attribute) {
        expect((string) $xml[$attribute])->toBe('true', "phpunit.xml lost {$attribute}=\"true\"; deprecations would pass the suite again");
    }
    // A deprecation one package raises because of another is that package's to fix.
    expect((string) $xml->source['ignoreIndirectDeprecations'])->toBe('true');
});

it('hands deprecations to PHPUnit from every app test', function () {
    expect(class_uses(TestCase::class))->toContain(ReportsDeprecationsToPhpUnit::class);
    expect((string) file_get_contents(dirname(__DIR__).'/TestCase.php'))->toContain('$this->reportDeprecationsToPhpUnit();');
});

it('passes every other error on to the handler it replaced', function () {
    $seen = [];
    set_error_handler(static function (int $level, string $message) use (&$seen): bool {
        $seen[] = [$level, $message];

        return true;
    });

    $reporter = new class
    {
        use ReportsDeprecationsToPhpUnit;

        public function install(): void
        {
            $this->reportDeprecationsToPhpUnit();
        }
    };

    try {
        $reporter->install();
        trigger_error('weld test notice', E_USER_NOTICE);
    } finally {
        restore_error_handler();
        restore_error_handler();
    }

    expect($seen)->toBe([[E_USER_NOTICE, 'weld test notice']]);
});
