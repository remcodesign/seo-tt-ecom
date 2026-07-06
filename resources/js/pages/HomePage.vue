<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { PostData } from '@/types';
import api from '@/api';
import CardLister from '@/components/CardLister.vue';

const posts = ref<PostData[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        const response = await api.get<{ data: PostData[] }>('/blog/posts');
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
            <h1 class="mb-2 text-3xl font-bold tracking-tight">Latest Posts</h1>
            <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                A live overview of the most recent blog posts.
            </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading posts…
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <!-- Posts -->
        <CardLister v-else :posts="posts" />
    </div>
</template>