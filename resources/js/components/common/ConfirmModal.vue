<script setup lang="ts">
import Button from '@/components/common/Button.vue';
import Modal from '@/components/common/Modal.vue';

const props = withDefaults(defineProps<{
    show?: boolean;
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    confirmState?: 'danger' | 'normal' | 'primary';
}>(), {
    show: false,
    title: 'Confirm action',
    message: 'Are you sure you want to continue?',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    confirmState: 'danger',
});

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

const handleClose = (): void => {
    emit('close');
};

const handleConfirm = (): void => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="props.show" @close="handleClose">
        <div class="space-y-6 p-6">
            <div>
                <h2 class="text-xl font-semibold text-[#111113] dark:text-[#EDEDEC]">{{ props.title }}</h2>
                <p class="mt-2 text-sm text-[#6C6C66] dark:text-[#A1A19A]">{{ props.message }}</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <Button variant="text" size="md" @click="handleClose">
                    {{ props.cancelLabel }}
                </Button>

                <Button
                    :variant="props.confirmState === 'danger' ? 'bordered_normal' : 'bordered_normal'"
                    :state="props.confirmState === 'danger' ? 'danger' : undefined"
                    size="md"
                    @click="handleConfirm"
                >
                    {{ props.confirmLabel }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
