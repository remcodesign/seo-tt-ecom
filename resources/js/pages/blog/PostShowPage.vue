<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { PostData } from '@types';
import api from '@/api';
import CommentRow from '@/components/blog/CommentRow.vue';
import TableLister from '@/components/common/TableLister.vue';

const route = useRoute();
const slug = route.params.slug as string;

const post = ref<PostData | null>(null);
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
        const response = await api.get<PostData>(`/blog/posts/${slug}`);
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
            <header class="mb-8">
                <h1 class="mb-4 text-3xl font-bold tracking-tight">
                    {{ post.title }}
                </h1>

                <div v-if="post.user" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e3e3e0] text-sm font-medium text-[#1b1b18] dark:bg-[#3E3E3A] dark:text-[#EDEDEC]">
                        {{ post.user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ post.user.name }}</span>
                    </div>

                    <span v-if="post.published_on" class="ml-auto text-xs text-[#6C6C66] dark:text-[#A1A19A]">
                        {{ new Date(post.published_on).toLocaleDateString('nl-NL', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </span>
                </div>
            </header>

            <div v-if="post.body" class="prose prose-sm max-w-none text-[#1b1b18] dark:text-[#EDEDEC]">
                {{ post.body }}
            </div>

            <section class="mt-10 rounded-lg border border-[#19140035] bg-white p-5 shadow-xs dark:border-[#3E3E3A] dark:bg-[#161615]">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-[#111113] dark:text-[#EDEDEC]">Comments</h2>
                        <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">Showing the latest comments for this post.</p>
                    </div>
                </div>

                <TableLister
                    :items="post.comments ?? []"
                    row-prop-name="comment"
                    :row-component="CommentRow"
                    :columns="commentColumns"
                    :max-rows="5"
                    empty-text="No comments yet."
                >
                    <template #header>
                        <tr class="bg-[#f7f6f3] text-xs uppercase tracking-[0.16em] text-[#6C6C66] dark:bg-[#262624] dark:text-[#9B9B92]">
                            <th
                                v-for="column in commentColumns"
                                :key="column"
                                class="px-4 py-3"
                            >
                                {{ commentColumnLabels[column] }}
                            </th>
                        </tr>
                    </template>
                </TableLister>
            </section>
        </article>
    </div>
</template>