<?php

/*
 * BEST_PRACTICES W2, as a check: WayneUI is the only component source.
 *
 * The Laravel starter kit arrived with shadcn-vue copied into components/ui,
 * a Tailwind theme and a drawer of helper libraries. They are gone, and this
 * file is what keeps them gone. ESLint holds the import side (no-restricted-
 * imports in eslint.config.js); these tests hold the files and the packages.
 * Like a weld, it only counts if it holds under load.
 */

function weldPath(string $relative): string
{
    return dirname(__DIR__, 2).'/'.$relative;
}

/** @return array<string, mixed> */
function weldJson(string $relative): array
{
    $decoded = json_decode((string) file_get_contents(weldPath($relative)), true);

    return is_array($decoded) ? $decoded : [];
}

it('has no shadcn-vue components.json', function () {
    // shadcn-vue's CLI writes this file and then copies components into the
    // app. Without it, `npx shadcn-vue add` has nowhere to put them.
    expect(file_exists(weldPath('components.json')))->toBeFalse(
        'components.json is shadcn-vue\'s config. WELD takes its components from WayneUI: delete it.',
    );
});

it('has no kit components/ui folder', function () {
    expect(is_dir(weldPath('resources/js/components/ui')))->toBeFalse(
        'resources/js/components/ui held the kit\'s shadcn-vue copies. Use @waynestate/wayne-ui-vue instead.',
    );
});

it('keeps the ESLint rule that bans the kit import path', function () {
    $config = (string) file_get_contents(weldPath('eslint.config.js'));

    expect($config)
        ->toContain("'no-restricted-imports': ['error', wayneUiOnly]")
        ->toContain("'@/components/ui'")
        ->toContain("'**/components/ui/*'");
});

it('ships no Tailwind theme and none of the kit\'s helper packages', function () {
    expect(file_exists(weldPath('tailwind.config.js')))->toBeFalse()
        ->and(file_exists(weldPath('resources/js/lib/utils.ts')))->toBeFalse();

    $package = weldJson('package.json');
    $installed = array_keys(array_merge(
        $package['dependencies'] ?? [],
        $package['devDependencies'] ?? [],
        $package['optionalDependencies'] ?? [],
    ));

    $banned = [
        'tailwindcss', '@tailwindcss/forms', 'tailwind-merge', 'clsx', 'class-variance-authority',
        '@headlessui/vue', 'radix-vue', 'reka-ui', 'lucide-vue-next', 'prettier-plugin-tailwindcss',
    ];

    expect(array_values(array_intersect($banned, $installed)))->toBe([]);
});

it('pins both WayneUI packages to one exact version, and the lock agrees', function () {
    $dependencies = weldJson('package.json')['dependencies'] ?? [];
    $lock = weldJson('package-lock.json')['packages'] ?? [];

    $css = $dependencies['@waynestate/wayne-ui-css'] ?? null;
    $vue = $dependencies['@waynestate/wayne-ui-vue'] ?? null;

    // Exact versions only: a caret would let the look of the app change on an
    // unrelated npm install. The lock's integrity hash is the checksum.
    expect($css)->toMatch('/^\d+\.\d+\.\d+$/')
        ->and($vue)->toBe($css)
        ->and($lock['node_modules/@waynestate/wayne-ui-css']['version'] ?? null)->toBe($css)
        ->and($lock['node_modules/@waynestate/wayne-ui-vue']['version'] ?? null)->toBe($css)
        ->and($lock['node_modules/@waynestate/wayne-ui-css']['integrity'] ?? '')->toStartWith('sha512-')
        ->and($lock['node_modules/@waynestate/wayne-ui-vue']['integrity'] ?? '')->toStartWith('sha512-');
});

it('loads the WayneUI stylesheet and leaves no stand-in behind', function () {
    // The layered dist file: see the comment at the top of app.css for why.
    expect((string) file_get_contents(weldPath('resources/css/app.css')))
        ->toContain("@import '@waynestate/wayne-ui-css/dist/wayne-ui.css';")
        ->not->toContain('@tailwind');

    $leftovers = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(weldPath('resources/js'), FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (str_contains((string) file_get_contents($file->getPathname()), 'TODO(wayneui)')) {
            $leftovers[] = $file->getFilename();
        }
    }

    expect($leftovers)->toBe([]);
});

it('runs WayneUI\'s pre-paint theme script under HELMET\'s nonce', function () {
    $view = (string) file_get_contents(weldPath('resources/views/app.blade.php'));

    expect($view)
        ->toContain("localStorage.getItem('wsu-theme')")
        ->toContain('<script @if($cspNonce) nonce="{{ $cspNonce }}" @endif>');
});
