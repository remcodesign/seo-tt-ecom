<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { PostData } from '@types';
import api from '@/api';

const route = useRoute();
const slug = route.params.slug as string;

const post = ref<PostData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

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
                        <span class="text-xs text-[#6C6C66] dark:text-[#A1A19A]">{{ post.user.email }}</span>
                    </div>

                    <span v-if="post.published_on" class="ml-auto text-xs text-[#6C6C66] dark:text-[#A1A19A]">
                        {{ new Date(post.published_on).toLocaleDateString('nl-NL', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </span>
                </div>
            </header>

            <div v-if="post.body" class="prose prose-sm max-w-none text-[#1b1b18] dark:text-[#EDEDEC]">
                {{ post.body }}
            </div>
        </article>
    </div>
</template>