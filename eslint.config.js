import { defineConfig } from 'eslint/config';
import js from '@eslint/js';
import vuePlugin from 'eslint-plugin-vue';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import vueParser from 'vue-eslint-parser';

export default defineConfig([
  {
    files: ['resources/js/**/*.{js,ts,vue}'],
    ignores: ['**/node_modules/**', '**/public/**', '**/storage/**', '**/.git/**'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        extraFileExtensions: ['.vue'],
        parser: tsParser,
        project: './tsconfig.json',
      },
      globals: {
        document: 'readonly',
        localStorage: 'readonly',
        window: 'readonly',
      },
    },
    plugins: {
      '@typescript-eslint': tsPlugin,
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },
]);
