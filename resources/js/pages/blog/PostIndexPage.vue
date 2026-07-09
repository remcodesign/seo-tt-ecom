<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import type {
    PaginationLinkData,
    PaginationMetaData,
    PaginatedResponseData,
    PostDataResponse,
} from '@types';
import api from '@/api';
import CardLister from '@/components/common/CardLister.vue';
import PaginationLinks from '@/components/common/PaginationLinks.vue';
import PostCard from '@/components/blog/PostCard.vue';
import SectionHeaderControls from '@/components/common/SectionHeaderControls.vue';
import { usePagination } from '@/composable/common/usePagination';



const route = useRoute();

// Main data for the page
const posts = ref<PostDataResponse[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Filter and sorting options
const orderOptions = [
    { label: 'Published (A→Z)', value: 'published_on' },
    { label: 'Published (Z→A)', value: 'published_on_desc' },
    { label: 'Updated (A→Z)', value: 'updated_at' },
    { label: 'Updated (Z→A)', value: 'updated_at_desc' },
] as const;

const orderBy = ref(String(route.query.orderby ?? 'published_on_desc'));
const perPageOptions = [3, 6, 9, 12] as const;

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
    defaultPerPage: 6,
    initialMeta: {
        current_page: 1,
        first_page_url: '',
        from: null,
        last_page: 1,
        last_page_url: '',
        next_page_url: null,
        path: '',
        per_page: 6,
        prev_page_url: null,
        to: null,
        total: 0,
    },
});

const fetchPosts = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    updateRoute({
        page: page.value,
        per_page: perPage.value,
        orderby: orderBy.value,
    });

    try {
        const response = await api.get<PaginatedResponseData<PostDataResponse>>('/blog/posts', {
            params: {
                page: page.value,
                per_page: perPage.value,
                orderby: orderBy.value,
            },
        });

        posts.value = response.data.data;
        links.value = response.data.links;
        meta.value = response.data.meta;
    } catch {
        error.value = 'Failed to load blog posts.';
    } finally {
        loading.value = false;
    }
};

// Watchers to refetch posts when relevant parameters change
watch([orderBy, perPage], () => {
    page.value = 1;
    void fetchPosts();
});

// Watch for changes in the page number to refetch posts
watch(page, () => {
    void fetchPosts();
});

onMounted(() => {
    void fetchPosts();
});
</script>

<template>
    <div>
        <SectionHeaderControls
            title="Blog Posts"
            description="Browse recent blog posts."
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
            Loading posts…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <div v-else>
            <!-- Content -->
            <CardLister :items="posts" :card-component="PostCard" card-prop-name="post" :max-items="posts.length"
                empty-text="No posts available." />

            <!-- Pagination -->
            <PaginationLinks :links="links" @page-change="setPage" />
        </div>
    </div>
</template>
