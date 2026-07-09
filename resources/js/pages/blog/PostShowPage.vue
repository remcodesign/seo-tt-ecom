<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { PostDataResponse } from '@types';
import api from '@/api';
import BackLink from '@/components/common/BackLink.vue';
import CommentRow from '@/components/blog/CommentRow.vue';
import TableLister from '@/components/common/TableLister.vue';
import Card from '@/components/common/Card.vue';

const route = useRoute();
const slug = route.params.slug as string;

const post = ref<PostDataResponse | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const commentColumnLabels = {
    user: 'Author',
    comment: 'Comment',
    created_at: 'Created',
} as const;

type CommentColumn = keyof typeof commentColumnLabels;
const commentColumns: CommentColumn[] = ['user', 'comment', 'created_at'];

onMounted(async () => {
    try {
        const response = await api.get<PostDataResponse>(`/blog/posts/${slug}`);
        post.value = response.data;
    } catch {
        error.value = 'Post not found.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <!-- Loading -->
        <div v-if="loading" class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            Loading post…
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>

        <!-- Post -->
        <article v-else-if="post" class="mx-auto max-w-3xl">
            <!-- Back Link -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <BackLink />
            </div>

            <!-- Post Header -->
            <header class="mb-8">
                <h1 class="mb-4 text-3xl font-bold tracking-tight">
                    {{ post.title }}
                </h1>

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e3e3e0] text-sm font-medium text-[#1b1b18] dark:bg-[#3E3E3A] dark:text-[#EDEDEC]">
                        {{ post.user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ post.user.name }}</span>
                    </div>

                    <span v-if="post.published_on" class="ml-auto text-xs text-[#6C6C66] dark:text-[#A1A19A]">
                        {{ new Date(post.published_on).toLocaleDateString('nl-NL', {
                            year: 'numeric', month: 'long',
                            day: 'numeric'
                        }) }}
                    </span>
                </div>
            </header>

            <!-- Post Body -->
            <div v-if="post.body" class="prose prose-sm max-w-none text-[#1b1b18] dark:text-[#EDEDEC]">
                {{ post.body }}
            </div>

            <!-- Comments Section -->
            <Card class="mt-10  p-5">
                <div class=" mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-[#111113] dark:text-[#EDEDEC]">Comments
                        </h2>
                        <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">Showing the latest comments for this post.
                        </p>
                    </div>
                </div>

                <TableLister :items="post.comments ?? []" row-prop-name="comment" :row-component="CommentRow"
                    :columns="commentColumns" :max-rows="5" empty-text="No comments yet.">
                    <template #header>
                        <tr
                            class="bg-[#f7f6f3] text-xs uppercase tracking-[0.16em] text-[#6C6C66] dark:bg-[#262624] dark:text-[#9B9B92]">
                            <th v-for="column in commentColumns" :key="column" class="px-4 py-3">
                                {{ commentColumnLabels[column] }}
                            </th>
                        </tr>
                    </template>
                </TableLister>
            </Card>
            <!-- END Comments Section -->

        </article>
    </div>
</template>