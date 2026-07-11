<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { CommentDataResponse, PaginatedResponseData } from '@types';
import type { Component } from 'vue';
import api from '@/api';
import Button from '@/components/common/Button.vue';
import TableLister from '@/components/common/TableLister.vue';
import CommentRow from '@/components/blog/CommentRow.vue';

const props = withDefaults(defineProps<{
    title: string;
    endpoint?: string;
    description?: string;
    rowComponent?: Component;
    rowPropName?: string;
    maxRows?: number;
    emptyText?: string;
}>(), {
    endpoint: '/blog/comments',
    description: 'A quick view of the newest blog comments.',
    rowComponent: CommentRow,
    rowPropName: 'comment',
    maxRows: 3,
    emptyText: 'No comments available.',
});

const comments = ref<CommentDataResponse[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const commentColumnLabels = {
    user: 'Author',
    comment: 'Comment',
    post: 'Post',
    created_at: 'Created',
} as const;

type CommentColumn = keyof typeof commentColumnLabels;
const commentColumns: CommentColumn[] = ['user', 'comment', 'post', 'created_at'];

onMounted(async () => {
    try {
        const response = await api.get<PaginatedResponseData<CommentDataResponse>>(props.endpoint, {
            params: {
                per_page: props.maxRows,
                orderby: 'created_at_desc',
            },
        });

        comments.value = response.data.data;
    } catch {
        error.value = 'Failed to load comments.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="mt-16">
        <div class="mb-8">
            <h1 class="mb-2 text-3xl font-bold tracking-tight">{{ props.title }}</h1>
            <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                {{ description }}
            </p>
        </div>

        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading comments…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <TableLister
            v-else
            :items="comments"
            row-prop-name="comment"
            :row-component="props.rowComponent"
            :columns="commentColumns"
            :max-rows="props.maxRows"
            :empty-text="props.emptyText"
        >
            <template #header>
                <tr
                    class="bg-[#f7f6f3] text-xs uppercase tracking-[0.16em] text-[#6C6C66] dark:bg-[#262624] dark:text-[#9B9B92]"
                >
                    <th v-for="column in commentColumns" :key="column" class="px-4 py-3">
                        {{ commentColumnLabels[column] }}
                    </th>
                </tr>
            </template>
        </TableLister>

        <div v-if="!loading && !error && comments.length > 0" class="mt-6 flex flex-wrap items-center gap-3">
            <Button variant="text-underline" size="sm" :active="true" :to="{ name: 'comments.index' }">
                View all blog comments
            </Button>
        </div>
    </div>
</template>
