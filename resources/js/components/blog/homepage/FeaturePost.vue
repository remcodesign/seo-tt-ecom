<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { PostData } from '@types';
import api from '@/api';
import CardLister from '@/components/common/CardLister.vue';

const props = defineProps<{
    title: string;
}>();

const posts = ref<PostData[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const description = 'A live overview of the most recent blog posts.'
const emptyText = 'No posts available.';

onMounted(async () => {
    try {
        const response = await api.get<{ data: PostData[] }>(`/blog/posts`);

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

        <CardLister v-else :posts="posts" :empty-text="emptyText" />
    </div>
</template>
