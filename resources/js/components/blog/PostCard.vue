<script setup lang="ts">
import type { PostData } from '@types';

defineProps<{
    post: PostData;
}>();
</script>

<template>
    <div class="flex flex-1 flex-col">
        <h3 class="mb-2 text-lg font-semibold leading-snug tracking-tight">
            <router-link
                :to="`/blog/posts/${post.slug}`"
                class="transition-colors hover:text-[#f53003] dark:hover:text-[#FF4433]"
            >
                {{ post.title }}
            </router-link>
        </h3>

        <p v-if="post.body" class="mb-4 line-clamp-3 text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            {{ post.body }}
        </p>

        <div class="mt-auto flex items-center gap-3 border-t border-[#19140020] pt-3 dark:border-[#3E3E3A40]">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#e3e3e0] text-xs font-medium text-[#1b1b18] dark:bg-[#3E3E3A] dark:text-[#EDEDEC]">
                {{ post.user?.name.charAt(0).toUpperCase() ?? '?' }}
            </div>

            <div class="flex flex-col">
                <span class="text-xs font-medium">{{ post.user?.name ?? 'Unknown' }}</span>
                <span v-if="post.published_on" class="text-[10px] text-[#6C6C66] dark:text-[#A1A19A]">
                    {{ new Date(post.published_on).toLocaleDateString('nl-NL', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                </span>
            </div>
        </div>
    </div>
</template>