<script setup lang="ts">
import type { PropType } from 'vue';
import type { PaginationLinkData } from '@types';
import Button from '@/components/common/Button.vue';

const props = defineProps({
    links: {
        type: Array as PropType<PaginationLinkData[]>,
        required: true,
    },
});

const emit = defineEmits<{
    (event: 'page-change', page: number | null): void;
}>();

const handlePageClick = (page: number | null): void => {
    emit('page-change', page);
};
</script>

<template>
    <nav v-if="props.links.length > 0" class="mt-8">
        <ul class="flex flex-wrap items-center justify-center gap-2 text-sm">
            <li v-for="link in props.links" :key="link.label">
                <Button
                    :disabled="link.page === null"
                    :active="link.active"
                    variant="bordered_normal"
                    size="sm"
                    class="min-w-[3rem]"
                    @click="handlePageClick(link.page)"
                >
                    <span v-if="/previous/i.test(link.label)">←</span>
                    <span v-else-if="/next/i.test(link.label)">→</span>
                    <span v-else>{{ link.label }}</span>
                </Button>
            </li>
        </ul>
    </nav>
</template>
