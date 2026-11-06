import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';

import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

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

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        // site/assets/docs.js is the shared docs builder's kit file, synced byte for byte
        // from Squire; its three unused catch bindings get fixed there, not here.
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'site/assets/docs.js'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            'no-restricted-imports': ['error', wayneUiOnly],
        },
    },
    prettier,
);
