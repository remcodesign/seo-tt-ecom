<script setup lang="ts">
import type { PropType } from 'vue';

// todo make dto
type PaginationLink = {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
};

const props = defineProps({
    links: {
        type: Array as PropType<PaginationLink[]>,
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
                <button
                    type="button"
                    :disabled="link.page === null"
                    @click="handlePageClick(link.page)"
                    class="cursor-pointer inline-flex min-w-[3rem] items-center justify-center rounded-md border px-3 py-2 transition-colors disabled:cursor-not-allowed disabled:border-[#d6d6d1] disabled:bg-[#f5f5f3] disabled:text-[#a5a59d] dark:disabled:border-[#3A3A36] dark:disabled:bg-[#1f1f1d] dark:disabled:text-[#5D5D57]"
                    :class="link.active
                        ? 'border-[#f53003] bg-[#f53003] text-white shadow-sm dark:border-[#FF4433] dark:bg-[#FF4433]'
                        : 'border-[#8a7f4f20] bg-white text-[#1b1b18] hover:border-[#f53003] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:border-[#FF4433] dark:hover:text-[#FF4433]'"
                >
                    <span v-if="/previous/i.test(link.label)">←</span>
                    <span v-else-if="/next/i.test(link.label)">→</span>
                    <span v-else>{{ link.label }}</span>
                </button>
            </li>
        </ul>
    </nav>
</template>
