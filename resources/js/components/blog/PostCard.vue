<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import type { PostDataResponse } from '@types';
import Button from '@/components/common/Button.vue';

const route = useRoute();
const props = defineProps<{
    post: PostDataResponse;
}>();

const commentsCount = computed(() => props.post.comments_count ?? 0);

// ? create composable for this logic, as it is used in multiple places
const linkQuery = computed(() => {
    const query: Record<string, string> = {};

    if (typeof route.query.orderby === 'string') {
        query.orderby = route.query.orderby;
    }

    if (typeof route.query.per_page === 'string') {
        query.per_page = route.query.per_page;
    }

    if (typeof route.query.page === 'string') {
        query.page = route.query.page;
    }

    return query;
});
</script>

<template>
    <div class="flex flex-1 flex-col">
        <h3 class="mb-2 leading-snug tracking-tight">
            <Button variant="text" class="font-semibold"
                :to="{ name: 'posts.show', params: { slug: post.slug }, query: linkQuery }">
                {{ post.title }}
            </Button>
        </h3>

        <p v-if="post.body" class="mb-4 line-clamp-3 text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            {{ post.body }}
        </p>

        <div class="mt-auto flex items-center gap-3 border-t border-[#19140020] pt-3 dark:border-[#3E3E3A40]">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-[#e3e3e0] text-xs font-medium text-[#1b1b18] dark:bg-[#3E3E3A] dark:text-[#EDEDEC]">
                {{ post.user.name.charAt(0).toUpperCase() }}
            </div>

            <div class="flex flex-col">
                <span class="text-xs font-medium">{{ post.user.name }}</span>
                <span v-if="post.published_on" class="text-[10px] text-[#6C6C66] dark:text-[#A1A19A]">
                    {{ new Date(post.published_on).toLocaleDateString('nl-NL', {
                        year: 'numeric', month: 'short', day:
                            'numeric'
                    }) }}
                </span>
                <span class="mt-1 text-sm font-medium text-[#111113] dark:text-[#EDEDEC]">
                    {{ commentsCount }} comment{{ commentsCount === 1 ? '' : 's' }}
                </span>
            </div>
        </div>
    </div>
</template>