import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';

import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        // Squire owns these two kit files (same bytes on every stack front page; agent-ready.js is
        // hash-checked by bin/agent-ready.php). Their lint fixes belong in Squire's kit, not a local
        // fork, so the app's lint leaves them alone until the sync brings the fixed bytes.
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'site/assets/agent-ready.js',
            'site/assets/stack-env.js',
        ],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
    prettier,
);
