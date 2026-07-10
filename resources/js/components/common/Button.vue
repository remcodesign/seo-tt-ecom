<script setup lang="ts">
import { computed } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

const props = withDefaults(defineProps<{
    variant?: 'bordered_normal' | 'nav' | 'text' | 'text-underline';
    size?: 'xs' | 'sm' | 'md' | 'lg';
    disabled?: boolean;
    active?: boolean;
    to?: RouteLocationRaw;
}>(), {
    variant: 'bordered_normal',
    size: 'md',
    disabled: false,
    active: false,
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const isLink = computed(() => props.to !== undefined);

const buttonClasses = computed(() => {
    const classes: string[] = ['inline-flex items-center justify-center transition-colors'];

    if (props.variant === 'bordered_normal') {
        classes.push('rounded-md border text-sm cursor-pointer hover:shadow-md');

        if (props.size === 'sm') {
            classes.push('px-2 py-1');
        } else {
            classes.push('px-3 py-2');
        }

        if (props.disabled) {
            classes.push(
                'disabled:cursor-not-allowed',
                'disabled:border-[#d6d6d1]',
                'disabled:bg-[#f5f5f3]',
                'disabled:text-[#a5a59d]',
                'dark:disabled:border-[#3A3A36]',
                'dark:disabled:bg-[#1f1f1d]',
                'dark:disabled:text-[#5D5D57]',
            );
        } else if (props.active) {
            classes.push(
                'border-[#f53003]',
                'bg-[#f53003]',
                'text-white',
                'shadow-sm',
                'dark:border-[#FF4433]',
                'dark:bg-[#FF4433]',
            );
        } else {
            classes.push(
                'border-[#8a7f4f40]',
                'bg-white',
                'text-[#1b1b18]',
                'hover:border-[#f53003]',
                'hover:text-[#f53003]',
                'dark:border-[#3E3E3A]',
                'dark:bg-[#161615]',
                'dark:text-[#EDEDEC]',
                'dark:hover:border-[#FF4433]',
                'dark:hover:text-[#FF4433]',
            );
        }
    }

    if (props.variant === 'nav') {
        classes.push('rounded-md text-sm cursor-pointer');

        if (props.size === 'md') {
            classes.push('px-3 py-2');
        }

        if (props.active) {
            classes.push(
                'bg-[#f53003]',
                'text-white',
                'shadow-sm',
                'dark:bg-[#FF4433]',
                'font-bold',
            );
        } else {
            classes.push(
                'text-[#1b1b18]',
                'hover:text-[#6C6C66]',
                'dark:text-[#EDEDEC]',
                'dark:hover:text-[#A1A19A]',
            );
        }
    }

    if (props.variant === 'text') {
        classes.push('font-medium cursor-pointer');

        if (props.size === 'xs') {
            classes.push('text-xs');
        } else if (props.size === 'lg') {
            classes.push('text-lg');
        }

        classes.push(
            'text-[#1b1b18]',
            'hover:text-[#f53003]',
            'dark:text-[#EDEDEC]',
            'dark:hover:text-[#FF4433]',
        );
    }

    if (props.variant === 'text-underline') {
        classes.push('underline decoration-dotted underline-offset-2 cursor-pointer');

        if (props.size === 'xs') {
            classes.push('text-xs');
        }

        // Add specific styles for 'lg' vs other sizes
        if (props.size !== 'lg') {
            classes.push(
                'text-[#6C6C66]',
                'hover:text-[#f53003]',
                'dark:text-[#A1A19A]',
                'dark:hover:text-[#FF4433]',
            );
        } else {
            classes.push(
                'text-lg',
                'text-[#1b1b18]',
                'hover:text-[#f53003]',
                'dark:text-[#EDEDEC]',
                'dark:hover:text-[#FF4433]',
            );
        }

        // Extra pronounced styles using active state
        if (props.active) {
            classes.push(
                'text-[#f53003]',
                'dark:text-[#FF4433]',
                'hover:hover:no-underline',
                'hover:opacity-80',
            );
        }
    }

    return classes;
});

const handleClick = (event: MouseEvent): void => {
    if (!props.disabled) {
        emit('click', event);
    }
};
</script>

<template>
    <button v-if="!isLink" :disabled="disabled" :class="buttonClasses" @click="handleClick" v-bind="$attrs">
        <slot />
    </button>
    <router-link v-else :to="to!" :class="buttonClasses" @click="handleClick" v-bind="$attrs">
        <slot />
    </router-link>
</template>