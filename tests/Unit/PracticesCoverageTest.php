<?php

namespace Tests\Unit;

use App\Support\PracticesSync;

/*
 * The guides must cover BEST_PRACTICES.md one for one. Until this test, the
 * sync read only `## W<n>.` headings, so the nine practices under
 * `## W<n>:` headings (security, build and supply chain) were skipped
 * without a word and their guides went stale: W26 still said `Not yet`
 * long after it was `Enforced`. Two checks now: the sync reads both
 * separators, and coverage() names any practice the guides miss, repeat,
 * invent, or leave without a tag. A practice nobody reads is a guide
 * gathering dust; WELD likes its joints inspected.
 */

function coveragePractices(): array
{
    return [
        'W25' => ['Stack' => '`Checked`. composer audit runs in CI.'],
        'W26' => ['Practice' => 'Debug is off in production.', 'Stack' => '`Enforced`. The app refuses to boot.'],
    ];
}

it('syncs a guide section whose heading separates the id with a colon', function () {
    $guide = "## W26: Debug is off outside local\n\n**Practice:** stale\n\n**Stack:** `Not yet`. Nothing.\n\n## Related\n";

    expect(PracticesSync::sync($guide, coveragePractices()))->toBe(
        "## W26: Debug is off outside local\n\n**Practice:** Debug is off in production.\n\n**Stack:** `Enforced`. The app refuses to boot.\n\n## Related\n",
    );
});

it('reads a BEST_PRACTICES heading with a colon the same as one with a period', function () {
    $source = "### W25. Audit\n- **Stack:** period\n\n### W26: Debug\n- **Stack:** colon\n";

    expect(PracticesSync::practices($source))->toBe([
        'W25' => ['Stack' => 'period'],
        'W26' => ['Stack' => 'colon'],
    ]);
});

it('lists the sections of a guide and whether each has its Stack line', function () {
    $guide = "# Security\n\n## W25. Audit\n\n**Stack:** yes\n\n## W26: Debug\n\n**Practice:** only\n\n## Related\n\n**Stack:** not in a section\n";

    expect(PracticesSync::sections($guide))->toBe([['W25', true], ['W26', false]]);
});

it('finds nothing wrong when every practice has one section with a Stack line', function () {
    $guides = [
        'docs/guides/a.md' => "## W25. Audit\n\n**Stack:** x\n",
        'docs/guides/b.md' => "## W26: Debug\n\n**Stack:** y\n",
    ];

    expect(PracticesSync::coverage(coveragePractices(), $guides))->toBe([]);
});

it('names a practice no guide covers', function () {
    $guides = ['docs/guides/a.md' => "## W25. Audit\n\n**Stack:** x\n"];

    expect(PracticesSync::coverage(coveragePractices(), $guides))->toBe([
        'W26 is in BEST_PRACTICES.md, but no guide in docs/guides has a "## W26." section.',
    ]);
});

it('names a practice with two sections, a section without a Stack line, and an unknown id', function () {
    $guides = [
        'docs/guides/a.md' => "## W25. Audit\n\n**Stack:** x\n\n## W26. Debug\n\n**Practice:** no tag here\n",
        'docs/guides/b.md' => "## W25: Audit again\n\n**Stack:** x\n\n## W99. Invented\n\n**Stack:** z\n",
    ];

    expect(PracticesSync::coverage(coveragePractices(), $guides))->toBe([
        'docs/guides/a.md: the W26 section has no **Stack:** line.',
        'docs/guides/b.md: W99 is not a practice in BEST_PRACTICES.md.',
        'W25 has more than one section: docs/guides/a.md, docs/guides/b.md.',
    ]);
});

it('covers every practice in BEST_PRACTICES.md from the guides in docs/guides', function () {
    $root = dirname(__DIR__, 2);
    $practices = PracticesSync::practices((string) file_get_contents($root.'/BEST_PRACTICES.md'));

    $guides = [];
    foreach (glob($root.'/docs/guides/*.md') ?: [] as $path) {
        $guides['docs/guides/'.basename($path)] = (string) file_get_contents($path);
    }

    // W1 to W33 today; the floor stops an empty parse from passing as "no problems".
    expect(count($practices))->toBeGreaterThanOrEqual(33)
        ->and(PracticesSync::coverage($practices, $guides))->toBe([]);
});
