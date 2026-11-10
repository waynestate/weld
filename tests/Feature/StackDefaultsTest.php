<?php

/*
|--------------------------------------------------------------------------
| Sourdough's two new Laravel defaults
|--------------------------------------------------------------------------
|
| Sourdough's Laravel shapes now set a Composer release cooldown (a new
| package version waits three days before it may be installed, our own
| waynestate/* packages exempt) and HELMET's fixed 4xx error pages (an
| abort() message never reaches the visitor). WELD is the Inertia shape,
| so it carries both.
*/

/**
 * @return array<string, mixed>
 */
function weldComposer(): array
{
    return json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
}

it('waits three days before installing a new package version', function () {
    $cooldown = weldComposer()['config']['policy']['cooldown'];

    expect($cooldown['period'])->toBe('3 days')
        ->and($cooldown['ignore'])->toBe(['waynestate/*']);
});

it('answers 4xx with HELMET error pages by default', function () {
    expect(config('helmet.error_pages'))->toBeTrue();
});
