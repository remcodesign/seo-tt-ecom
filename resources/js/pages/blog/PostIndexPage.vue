<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import type { LocationQueryRaw } from 'vue-router';
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

const orderOptions = [
    { label: 'Published (A→Z)', value: 'published_on' },
    { label: 'Published (Z→A)', value: 'published_on_desc' },
    { label: 'Updated (A→Z)', value: 'updated_at' },
    { label: 'Updated (Z→A)', value: 'updated_at_desc' },
] as const;


const route = useRoute();
const router = useRouter();

const posts = ref<PostDataResponse[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const perPageOptions = [3, 6, 9, 12] as const;
const orderBy = ref(String(route.query.orderby ?? 'published_on_desc'));

// pagination and filtering state
const page = ref(Number(route.query.page ?? 1));
const perPage = ref(Number(route.query.per_page ?? 6));
const links = ref<PaginationLinkData[]>([]);
const meta = ref<PaginationMetaData>({
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
});

const totalLabel = computed(() => {
    if (meta.value.total === 0) {
        return 'No posts available.';
    }

    return `${meta.value.total} post${meta.value.total === 1 ? '' : 's'} total`;
});

// URL CURRENT HANDLING :: Update the route query parameters without reloading the page
const updateRoute = (query: LocationQueryRaw): void => {
    router.replace({
        name: 'posts.index',
        query: {
            ...route.query,
            ...query,
        },
    });
};

const fetchPosts = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    // URL CURRENT HANDLING :: Update the route query parameters to reflect the current state
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

// URL CURRENT HANDLING :: Handle page change from PaginationLinks component (change the route.query.page)
const setPage = (pageNumber: number | null): void => {
    if (pageNumber === null || pageNumber === page.value) {
        return;
    }

    page.value = pageNumber;
};

watch([orderBy, perPage], () => {
    page.value = 1;
    void fetchPosts();
});

watch(page, () => {
    void fetchPosts();
});

watch(
    () => route.query,
    (query) => {
        if (query.page && Number(query.page) !== page.value) {
            page.value = Number(query.page);
        }

        if (query.per_page && Number(query.per_page) !== perPage.value) {
            perPage.value = Number(query.per_page);
        }

        if (query.orderby && String(query.orderby) !== orderBy.value) {
            orderBy.value = String(query.orderby);
        }
    },
    { immediate: true },
);

onMounted(() => {
    void fetchPosts();
});
</script>

<template>
    <div>
        <div
            class="mb-8 flex flex-col gap-6 rounded-xl border border-[#19140035] bg-white p-6 shadow-xs dark:border-[#3E3E3A] dark:bg-[#161615]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Blog Posts</h1>
                    <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                        Browse recent blog posts.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:items-end">
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]" for="orderby">
                            Order
                        </label>

                        <select id="orderby" v-model="orderBy"
                            class="rounded-md border border-[#19140035] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition-colors focus:border-[#f53003] dark:border-[#3E3E3A] dark:bg-[#1c1c1a] dark:text-[#EDEDEC]">
                            <option v-for="option in orderOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <label class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]" for="per-page">
                            Items
                        </label>

                        <select id="per-page" v-model.number="perPage"
                            class="rounded-md border border-[#19140035] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition-colors focus:border-[#f53003] dark:border-[#3E3E3A] dark:bg-[#1c1c1a] dark:text-[#EDEDEC]">
                            <option v-for="size in perPageOptions" :key="size" :value="size">
                                {{ size }} per page
                            </option>
                        </select>
                    </div>

                    <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                        {{ totalLabel }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading posts…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <div v-else>
            <CardLister :items="posts" :card-component="PostCard" card-prop-name="post" :max-items="posts.length"
                empty-text="No posts available." />

            <PaginationLinks :links="links" @page-change="setPage" />
        </div>
    </div>
</template>
