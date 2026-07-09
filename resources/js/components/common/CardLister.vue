<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';
import Card from '@/components/common/Card.vue';

const props = withDefaults(defineProps<{
    items: unknown[];
    cardPropName: string;
    cardComponent: Component;
    maxItems?: number;
    emptyText?: string;
}>(), {
    maxItems: 6,
    emptyText: 'No items available.',
});

const renderedItems = computed(() => {
    return props.items.slice(0, props.maxItems);
});
</script>

<template>
    <div v-if="renderedItems.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Card v-for="(item, index) in renderedItems" :key="index" class="flex flex-col">
            <div class="flex flex-1 flex-col p-5">
                <component :is="props.cardComponent" v-bind="{ [props.cardPropName]: item }" />
            </div>
        </Card>
    </div>

    <p v-else class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
        {{ props.emptyText }}
    </p>
</template>