<script setup lang="ts">
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Button from '@/components/common/Button.vue';

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
    <Button
        variant="text"
        size="xs"
        class="gap-2"
        @click="backToIndex"
    >
        <span aria-hidden="true">←</span>
        <span>Back to posts</span>
    </Button>
</template>
