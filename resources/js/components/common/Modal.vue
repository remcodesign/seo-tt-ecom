<script setup lang="ts">
const props = withDefaults(defineProps<{ show?: boolean }>(), {
    show: false,
});
const emit = defineEmits<{
    close: [];
}>();

const close = (): void => {
    emit('close');
};
</script>

<template>
    <div v-if="props.show" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog"
        aria-modal="true">

        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-lg 
            shadow-[#f53003]/20 border dark:border-[#292927]
            dark:bg-[#161615] dark:shadow-[#FF4433]/30">
            <slot />
        </div>

        <div class="absolute inset-0 bg-black/45 backdrop-blur-xs" @click="close"></div>
    </div>
</template>
