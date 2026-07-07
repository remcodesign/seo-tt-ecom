<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';

const props = withDefaults(defineProps<{
    items: unknown[];
    rowPropName: string;
    rowComponent: Component;
    columns?: string[];
    maxRows?: number;
    emptyText?: string;
}>(), {
    maxRows: 0,
    emptyText: 'No items available.',
});

const renderedItems = computed(() => {
    if (props.maxRows && props.maxRows > 0) {
        return props.items.slice(0, props.maxRows);
    }

    return props.items;
});
</script>

<template>
    <div v-if="renderedItems.length > 0" class="overflow-x-auto rounded-lg border border-[#19140035] bg-white shadow-xs dark:border-[#3E3E3A] dark:bg-[#161615]">
        <table class="min-w-full divide-y divide-[#19140020] text-left text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
            <thead v-if="$slots.header">
                <slot name="header" />
            </thead>

            <tbody class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                <tr v-for="(item, index) in renderedItems" :key="index" class="hover:bg-[#f8f6f1] odd:bg-[#faf9f7] dark:hover:bg-[#2a2a28] dark:odd:bg-[#1c1c1b]">
                    <component
                        :is="props.rowComponent"
                        v-bind="{ [props.rowPropName]: item, columns: props.columns }"
                    />
                </tr>
            </tbody>
        </table>
    </div>

    <p v-else class="px-4 py-5 text-sm text-[#6C6C66] dark:text-[#A1A19A]">
        {{ props.emptyText }}
    </p>
</template>
