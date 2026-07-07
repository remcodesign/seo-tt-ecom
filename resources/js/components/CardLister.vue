<script setup lang="ts">
import type { PostData } from '@types';
import PostCard from '@/components/PostCard.vue';

defineProps<{
    posts: PostData[];
}>();
</script>

<template>
    <div
        v-if="posts.length > 0"
        class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
    >
        <PostCard
            v-for="post in posts"
            :key="post.id"
            :post="post"
        />
    </div>

    <p v-else class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
        No posts available.
    </p>
</template>