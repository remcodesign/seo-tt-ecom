<script setup lang="ts">
import Button from '@/components/common/Button.vue';

const props = defineProps<{
    isOwner: boolean;
    editing: boolean;
    submitting: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'save'): void;
    (e: 'cancel'): void;
    (e: 'delete'): void;
}>();
</script>

<template>
    <td class="px-4 py-4 align-top">
        <div class="space-y-2">
            <div v-if="props.isOwner && !props.editing" class="flex items-center gap-2">
                <Button variant="text" size="sm" @click="emit('edit')">
                    Edit
                </Button>
            </div>

            <div v-if="props.isOwner && props.editing" class="flex items-center gap-2">
                <Button variant="bordered_normal" size="sm" :disabled="props.submitting" @click="emit('save')">
                    {{ props.submitting ? 'Saving…' : 'Save' }}
                </Button>

                <Button variant="text" size="sm" @click="emit('cancel')">
                    Cancel
                </Button>
            </div>

            <div>
                <Button
                    v-if="props.isOwner && !props.editing"
                    variant="text"
                    state="danger"
                    size="sm"
                    @click="emit('delete')"
                    aria-label="Delete comment"
                    class="mt-1"
                >
                    <font-awesome-icon icon="trash" class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </td>
</template>
