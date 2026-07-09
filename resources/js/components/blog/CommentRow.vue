<script setup lang="ts">
import type { CommentDataResponse } from '@types';
import Button from '@/components/common/Button.vue';

defineProps<{
    comment: CommentDataResponse;
    columns?: string[];
}>();
</script>

<template>
    <td class="px-4 py-4 align-top" v-if="columns?.includes('user') ?? true">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold text-[#111113] dark:text-[#EDEDEC]">{{ comment.user.name ?? 'Unknown user' }}</p>
        </div>
    </td>

    <td class="px-4 py-4 align-top" v-if="columns?.includes('comment') ?? true">
        <div class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">{{ comment.comment }}</div>
    </td>

    <td class="px-4 py-4 align-top" v-if="columns?.includes('post')">
        <Button
            v-if="comment.post?.slug"
            variant="text-underline"
            :to="{ name: 'posts.show', params: { slug: comment.post.slug } }"
        >
            {{ comment.post.title ?? 'Unknown post' }}
        </Button>
        <span v-else class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
            {{ comment.post?.title ?? 'Unknown post' }}
        </span>
    </td>

    <td class="px-4 py-4 align-top text-xs text-[#6C6C66] dark:text-[#A1A19A]" v-if="columns?.includes('created_at') ?? true">
        {{ comment.created_at ? new Date(comment.created_at).toLocaleDateString('nl-NL', { year: 'numeric', month: 'short', day: 'numeric' }) : '—' }}
    </td>
</template>
