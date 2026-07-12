<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/common/Button.vue';
import Card from '@/components/common/Card.vue';

const props = defineProps<{
    onSubmit: (comment: string) => Promise<void> | void;
}>();

const commentText = ref('');
const submitting = ref(false);
const error = ref<string | null>(null);

const handleSubmit = async (): Promise<void> => {
    if (!commentText.value.trim()) {
        error.value = 'Please enter a comment.';
        return;
    }

    error.value = null;
    submitting.value = true;

    try {
        await props.onSubmit(commentText.value.trim());

        commentText.value = '';
    } catch (error_) {
        error.value = 'Failed to post comment.';
        if (error_ instanceof Error) {
            error.value = error_.message;
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Card class="p-5">
        <div class="mb-4 space-y-2">
            <h3 class="text-lg font-semibold tracking-tight text-[#111113] dark:text-[#EDEDEC]">
                Add a comment
            </h3>
            <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                Share your thoughts on this post. Your comment will appear immediately after posting.
            </p>
        </div>

        <div class="space-y-4">
            <!-- Textarea -->
            <textarea
                data-test="comment-input"
                v-model="commentText"
                rows="4"
                class="w-full rounded-xl border border-[#d6d6d1] bg-[#fcfcfa] px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/10 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC]"
                placeholder="Write a comment..."
            ></textarea>

            <!-- Button Section -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

                <Button
                    data-test="comment-submit-button"
                    variant="bordered_normal"
                    size="md"
                    :disabled="submitting"
                    @click="handleSubmit"
                >
                    {{ submitting ? 'Posting…' : 'Post comment' }}
                </Button>
            </div>
        </div>
    </Card>
</template>
