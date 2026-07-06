<script setup lang="ts">
import type { PostData } from '@/types';
import CardLister from '@/components/CardLister.vue';

const demoSixPosts: PostData[] = [
    {
        id: 1,
        user_id: 1,
        title: 'Getting Started with Laravel 13',
        slug: 'getting-started-with-laravel-13',
        body: 'Laravel 13 brings a host of new features including improved routing, enhanced queue workers, and a refreshed frontend scaffolding system. In this post we explore the highlights.',
        published_on: '2026-06-28T10:00:00Z',
        user: {
            id: 1,
            name: 'Alice Jansen',
            email: 'alice@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-28T10:00:00Z',
        updated_at: '2026-06-28T10:00:00Z',
    },
    {
        id: 2,
        user_id: 2,
        title: 'Vue 3 Composition API Patterns',
        slug: 'vue-3-composition-api-patterns',
        body: 'Learn how to write clean, reusable composition functions in Vue 3. We cover composables, lifecycle hooks, and reactive state management without external libraries.',
        published_on: '2026-06-25T14:30:00Z',
        user: {
            id: 2,
            name: 'Bob Martens',
            email: 'bob@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-25T14:30:00Z',
        updated_at: '2026-06-25T14:30:00Z',
    },
    {
        id: 3,
        user_id: 1,
        title: 'TypeScript Strict Mode in Practice',
        slug: 'typescript-strict-mode-in-practice',
        body: 'Enabling strict mode in TypeScript can feel daunting. This guide walks through each strict flag, what it does, and how to migrate an existing codebase.',
        published_on: '2026-06-22T09:15:00Z',
        user: {
            id: 1,
            name: 'Alice Jansen',
            email: 'alice@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-22T09:15:00Z',
        updated_at: '2026-06-22T09:15:00Z',
    },
    {
        id: 4,
        user_id: 3,
        title: 'Tailwind CSS 4 – What Changed',
        slug: 'tailwind-css-4-what-changed',
        body: 'Tailwind CSS 4 ships with a rewritten engine, CSS-first configuration, and the new `@theme` directive. Here is everything you need to know to upgrade.',
        published_on: '2026-06-20T11:00:00Z',
        user: {
            id: 3,
            name: 'Carol Dewitt',
            email: 'carol@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-20T11:00:00Z',
        updated_at: '2026-06-20T11:00:00Z',
    },
    {
        id: 5,
        user_id: 2,
        title: 'Building REST APIs with Sanctum',
        slug: 'building-rest-apis-with-sanctum',
        body: 'Laravel Sanctum provides a featherweight authentication system for SPAs and mobile apps. Learn how to set up token-based API authentication in minutes.',
        published_on: '2026-06-18T08:45:00Z',
        user: {
            id: 2,
            name: 'Bob Martens',
            email: 'bob@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-18T08:45:00Z',
        updated_at: '2026-06-18T08:45:00Z',
    },
    {
        id: 6,
        user_id: 1,
        title: 'Pest 4 – Next-Gen PHP Testing',
        slug: 'pest-4-next-gen-php-testing',
        body: 'Pest 4 introduces architectural assertions, test impact analysis, and deeper Laravel integration. See how your test suite can benefit from the latest version.',
        published_on: '2026-06-15T16:00:00Z',
        user: {
            id: 1,
            name: 'Alice Jansen',
            email: 'alice@example.com',
            role_label: 'writer',
        },
        comments: null,
        created_at: '2026-06-15T16:00:00Z',
        updated_at: '2026-06-15T16:00:00Z',
    },
];
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="mb-2 text-3xl font-bold tracking-tight">Latest Posts</h1>
            <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                A demo overview of the six most recent blog posts.
            </p>
        </div>

        <CardLister :posts="demoSixPosts" />
    </div>
</template>