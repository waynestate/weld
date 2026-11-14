import pinstripe from '@waynestate/pinstripe/eslint/typescript';

// WayneUI is the only component source (BEST_PRACTICES W2). The Laravel kit's
// shadcn-vue copies lived in components/ui; this rule keeps them from moving
// back in, and keeps out the class-merging and headless libraries that came
// with them. tests/Feature/WayneUiOnlyTest.php checks this rule is still here.
const wayneUiOnly = {
    paths: [
        { name: 'clsx', message: 'WayneUI components carry their own classes; there is nothing to merge.' },
        { name: 'tailwind-merge', message: 'WELD has no Tailwind; style with WayneUI components and tokens.' },
        { name: '@headlessui/vue', message: 'Use the WayneUI component (@waynestate/wayne-ui-vue) instead.' },
        { name: 'lucide-vue-next', message: "Use WayneUI's Icon with a sprite name, for example <Icon name=\"home\" />." },
    ],
    patterns: [
        {
            group: ['@/components/ui', '@/components/ui/*', '**/components/ui', '**/components/ui/*'],
            message: 'WayneUI is the only component source: import from @waynestate/wayne-ui-vue, not the kit components/ui path.',
        },
    ],
};

// Pinstripe cuts the suit (ESLint's, Vue's, typescript-eslint's typed and the
// accessibility rules, every warning raised to an error, Prettier last); WELD
// only sews on its own cufflinks below.
export default [
    ...pinstripe,
    {
        // Copies kept byte for byte from their owners get fixed there, not here:
        // site/assets/docs.js and site/assets/agent-ready.js are Squire's kit files
        // (bin/agent-ready.php --check holds the second), tools/a11y/check.mjs is
        // Preflight's a11y checker. resources/js/ziggy.* is written by
        // `composer ziggy`, and the ziggy CI job compares it with a fresh
        // generation, so it is linted as little as Prettier formats it.
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'site/assets/docs.js',
            'site/assets/agent-ready.js',
            'tools/a11y/check.mjs',
            'resources/js/ziggy.js',
            'resources/js/ziggy.d.ts',
        ],
    },
    {
        // The Vite config is the one TypeScript file outside tsconfig.json's
        // resources/js, so typed linting reads it as a project of its own. One
        // project service serves every typed file, so this sits on all of them.
        files: ['**/*.ts', '**/*.vue'],
        languageOptions: {
            parserOptions: {
                projectService: { allowDefaultProject: ['vite.config.ts'] },
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
    {
        // The README art kit runs in Node, not in the browser the preset assumes.
        files: ['scripts/**/*.mjs'],
        languageOptions: {
            globals: { process: 'readonly', Buffer: 'readonly' },
        },
    },
    {
        rules: {
            'no-restricted-imports': ['error', wayneUiOnly],
        },
    },
];
