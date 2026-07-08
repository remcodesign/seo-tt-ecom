<script setup lang="ts">
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const indexQuery = computed(() => {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(route.query)) {
        if (typeof value === 'string') {
            query[key] = value;
        } else if (Array.isArray(value) && value.length > 0) {
            const firstString = value.find((item): item is string => typeof item === 'string');

            if (firstString) {
                query[key] = firstString;
            }
        }
    }

    return query;
});

const backToIndex = (): void => {
    router.push({
        name: 'posts.index',
        query: indexQuery.value,
    });
};
</script>

<template>
    <button
        type="button"
        @click="backToIndex"
        class="cursor-pointer inline-flex items-center gap-2 text-sm font-medium text-[#1b1b18] transition-colors hover:text-[#f53003] dark:text-[#EDEDEC] dark:hover:text-[#FF4433]"
    >
        <span aria-hidden="true">←</span>
        <span>Back to posts</span>
    </button>
</template>
