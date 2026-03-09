import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off', // Disabling rule for multi-word component names
            '@typescript-eslint/no-explicit-any': 'off', // Disabling rule for explicit 'any' type
            '@typescript-eslint/no-unused-vars': 'off', // Disabling unused vars rule for TypeScript
            'vue/no-unused-vars': 'off', // Disabling unused vars rule for Vue files
            'vue/require-v-for-key': 'off', // Disabling missing v-bind:key directive rule
            'vue/valid-attribute-name': 'off', // Disabling invalid attribute name rule
        },
    },
    prettier,
);