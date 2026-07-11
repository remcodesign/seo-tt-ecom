<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { Component } from 'vue';
import type { PostDataResponse } from '@types';
import api from '@/api';
import Button from '@/components/common/Button.vue';
import CardLister from '@/components/common/CardLister.vue';
import PostCard from '@/components/blog/PostCard.vue';

const props = withDefaults(defineProps<{
    title: string;
    endpoint?: string;
    description?: string;
    cardComponent?: Component;
    cardPropName?: string;
    maxItems?: number;
    emptyText?: string;
}>(), {
    endpoint: '/blog/posts',
    description: 'A live overview of the most recent blog posts.',
    cardComponent: PostCard,
    cardPropName: 'post',
    maxItems: 3,
    emptyText: 'No posts available.',
});

const posts = ref<PostDataResponse[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        const response = await api.get<{ data: PostDataResponse[] }>(props.endpoint);

        posts.value = response.data.data;
    } catch {
        error.value = 'Failed to load posts.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="mb-2 text-3xl font-bold tracking-tight">{{ props.title }}</h1>
            <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                {{ description }}
            </p>
        </div>

        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading posts…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <CardLister
            v-else
            :items="posts"
            :card-component="props.cardComponent"
            :card-prop-name="props.cardPropName"
            :empty-text="props.emptyText"
            :max-items="props.maxItems"
        />

        <div v-if="!loading && !error && posts.length > 0" class="mt-6 flex flex-wrap items-center gap-3">
            <Button variant="text-underline" size="sm" :active="true" :to="{ name: 'posts.index' }">
                View all blog posts
            </Button>
        </div>
    </div>
</template>
