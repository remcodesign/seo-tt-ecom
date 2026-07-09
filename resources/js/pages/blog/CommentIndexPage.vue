<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import type {
    CommentDataResponse,
    PaginationLinkData,
    PaginationMetaData,
    PaginatedResponseData,
} from '@types';
import api from '@/api';
import CommentRow from '@/components/blog/CommentRow.vue';
import PaginationLinks from '@/components/common/PaginationLinks.vue';
import SectionHeaderControls from '@/components/common/SectionHeaderControls.vue';
import TableLister from '@/components/common/TableLister.vue';
import { usePagination } from '@/composable/common/usePagination';

const route = useRoute();

// Main data for the page
const comments = ref<CommentDataResponse[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Filter and sorting options
const orderOptions = [
    { label: 'Newest', value: 'created_at_desc' },
    { label: 'Oldest', value: 'created_at' },
    { label: 'Updated (A→Z)', value: 'updated_at' },
    { label: 'Updated (Z→A)', value: 'updated_at_desc' },
] as const;

const orderBy = ref(String(route.query.orderby ?? 'created_at_desc'));
const perPageOptions = [5, 10, 25, 50, 100] as const;

// Table column configuration
const commentColumnLabels = {
    user: 'Author',
    comment: 'Comment',
    post: 'Post',
    created_at: 'Created',
} as const;

type CommentColumn = keyof typeof commentColumnLabels;
const commentColumns: CommentColumn[] = ['user', 'comment', 'post', 'created_at'];

// Used for the pagination composable
const {
    page,
    perPage,
    links,
    meta,
    setPage,
    updateRoute,
} = usePagination<PaginationLinkData, PaginationMetaData>({
    defaultPage: 1,
    defaultPerPage: 5,
    initialMeta: {
        current_page: 1,
        first_page_url: '',
        from: null,
        last_page: 1,
        last_page_url: '',
        next_page_url: null,
        path: '',
        per_page: 5,
        prev_page_url: null,
        to: null,
        total: 0,
    },
});

const fetchComments = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    updateRoute({
        page: page.value,
        per_page: perPage.value,
        orderby: orderBy.value,
    });

    try {
        const response = await api.get<PaginatedResponseData<CommentDataResponse>>('/blog/comments', {
            params: {
                page: page.value,
                per_page: perPage.value,
                orderby: orderBy.value,
            },
        });

        comments.value = response.data.data;
        links.value = response.data.links;
        meta.value = response.data.meta;
    } catch {
        error.value = 'Failed to load comments.';
    } finally {
        loading.value = false;
    }
};

// Watchers to refetch comments when relevant parameters change
watch([orderBy, perPage], () => {
    page.value = 1;
    void fetchComments();
});

// Watch for changes in the page number to refetch comments
watch(page, () => {
    void fetchComments();
});

onMounted(() => {
    void fetchComments();
});
</script>

<template>
    <div>
        <SectionHeaderControls
            title="Comments"
            description="Browse all comments across blog posts."
            :order-by="orderBy"
            :per-page="perPage"
            :total="meta.total"
            :show-order="true"
            :show-items="true"
            :order-options="orderOptions"
            :items-options="perPageOptions"
            @update:orderBy="(value) => orderBy = value"
            @update:perPage="(value) => perPage = value"
        />

        <!-- Loading, Error, and Content -->
        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading comments…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <div v-else>
            <!-- Content -->
            <TableLister
                :items="comments"
                row-prop-name="comment"
                :row-component="CommentRow"
                :columns="commentColumns"
                :max-rows="0"
                empty-text="No comments available."
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

            <!-- Pagination -->
            <PaginationLinks :links="links" @page-change="setPage" />
        </div>
    </div>
</template>