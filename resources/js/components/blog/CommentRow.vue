<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue';
import type { CommentDataResponse } from '@types';
import CommentRowActions from '@/components/blog/CommentRowActions.vue';
import ConfirmModal from '@/components/common/ConfirmModal.vue';
import Button from '@/components/common/Button.vue';

const props = defineProps<{
    comment: CommentDataResponse;
    columns?: string[];
    currentUserId?: number | null;
    onUpdate?: (payload: { id: number; comment: string }) => Promise<void> | void;
    onDelete?: (id: number) => Promise<void> | void;
}>();

const editing = ref(false);
const draft = ref(props.comment.comment);
const submitting = ref(false);
const error = ref<string | null>(null);

const isOwner = computed(() => props.currentUserId !== null && props.currentUserId === props.comment.user.id);

watchEffect(() => {
    draft.value = props.comment.comment;
});

const handleEdit = (): void => {
    editing.value = true;
    error.value = null;
};

const handleCancel = (): void => {
    editing.value = false;
    draft.value = props.comment.comment;
    error.value = null;
};

const handleSave = async (): Promise<void> => {
    if (!props.onUpdate) {
        return;
    }

    if (!draft.value.trim()) {
        error.value = 'Comment cannot be empty.';
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        await props.onUpdate({ id: props.comment.id, comment: draft.value.trim() });
        editing.value = false;
    } catch (error_) {
        if (error_ instanceof Error) {
            error.value = error_.message;
        } else {
            error.value = 'Failed to update comment.';
        }
    } finally {
        submitting.value = false;
    }
};

const showDeleteConfirm = ref(false);

const handleDelete = (): void => {
    if (!isOwner.value) {
        return;
    }

    showDeleteConfirm.value = true;
};

const confirmDelete = async (): Promise<void> => {
    if (!props.onDelete) {
        showDeleteConfirm.value = false;
        return;
    }

    await props.onDelete(props.comment.id);
    showDeleteConfirm.value = false;
};
</script>

<template>
    <td class="px-4 py-4 align-top" v-if="columns?.includes('user') ?? true">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold text-[#111113] dark:text-[#EDEDEC]">{{ props.comment.user.name ?? 'Unknown user' }}</p>
        </div>
    </td>

    <td class="px-4 py-4 align-top" v-if="columns?.includes('comment') ?? true">
        <div class="space-y-2">
            <div v-if="!editing" class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                {{ props.comment.comment }}
            </div>

            <div v-else>
                <textarea
                    data-test="comment-edit-textarea"
                    v-model="draft"
                    rows="3"
                    class="w-full rounded-xl border border-[#d6d6d1] bg-[#fcfcfa] px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/10 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC]"
                ></textarea>
                <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>
        </div>
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
        {{ props.comment.created_at ? new Date(props.comment.created_at).toLocaleDateString('nl-NL', { year: 'numeric', month: 'short', day: 'numeric' }) : '—' }}
    </td>

    <CommentRowActions
        v-if="columns?.includes('actions')"
        :is-owner="isOwner"
        :editing="editing"
        :submitting="submitting"
        @edit="handleEdit"
        @save="handleSave"
        @cancel="handleCancel"
        @delete="handleDelete"
    />

    <ConfirmModal
        :show="showDeleteConfirm"
        title="Delete comment"
        message="Are you sure you want to delete this comment? This action cannot be undone."
        confirm-label="Delete"
        cancel-label="Cancel"
        confirm-state="danger"
        @close="showDeleteConfirm = false"
        @confirm="confirmDelete"
    />
</template>
