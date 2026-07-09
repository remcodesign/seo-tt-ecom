<script setup lang="ts">
import { computed } from 'vue';
import Card from '@/components/common/Card.vue';

type OrderOption = {
    label: string;
    value: string;
};

const props = withDefaults(defineProps<{
    title: string;
    description?: string;
    orderOptions?: ReadonlyArray<OrderOption>;
    orderBy?: string;
    itemsOptions?: ReadonlyArray<number>;
    perPage?: number;
    total: number;
    showOrder?: boolean;
    showItems?: boolean;
}>(), {
    showOrder: false,
    showItems: false,
    orderOptions: () => [] as OrderOption[],
    itemsOptions: () => [] as number[],
});

const emit = defineEmits<{
    (event: 'update:orderBy', value: string): void;
    (event: 'update:perPage', value: number): void;
}>();

const showOrder = props.showOrder;
const showItems = props.showItems;

const orderOptions = props.orderOptions;
const itemsOptions = props.itemsOptions;

const orderByValue = computed({
    get: () => props.orderBy ?? '',
    set: (value: string) => emit('update:orderBy', value),
});

const perPageValue = computed({
    get: () => props.perPage ?? 0,
    set: (value: number) => emit('update:perPage', value),
});

const totalLabel = computed(() => {
    if (props.total === 0) {
        return 'No posts available.';
    }

    return `${props.total} post${props.total === 1 ? '' : 's'} total`;
});
</script>

<template>
    <Card class="mb-8">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <!-- Heading -->
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ props.title }}</h1>
                    <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                        {{ props.description }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:items-end">
                    <div class="flex flex-wrap items-center gap-3">

                        <!-- Order By -->
                        <template v-if="showOrder && orderOptions.length">
                            <label class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]" for="orderby">
                                Order
                            </label>

                            <select id="orderby" v-model="orderByValue"
                                class="rounded-md border border-[#19140035] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition-colors focus:border-[#f53003] dark:border-[#3E3E3A] dark:bg-[#1c1c1a] dark:text-[#EDEDEC]">
                                <option v-for="option in orderOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </template>

                        <!-- Items Per Page -->
                        <template v-if="showItems && itemsOptions.length">
                            <label class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]" for="per-page">
                                Items
                            </label>

                            <select id="per-page" v-model.number="perPageValue"
                                class="rounded-md border border-[#19140035] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition-colors focus:border-[#f53003] dark:border-[#3E3E3A] dark:bg-[#1c1c1a] dark:text-[#EDEDEC]">
                                <option v-for="size in itemsOptions" :key="size" :value="size">
                                    {{ size }} per page
                                </option>
                            </select>
                        </template>

                    </div>

                </div>

                <!-- Total Label -->
                <p class="text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                    {{ totalLabel }}
                </p>
            </div>
        </div>
    </Card>
</template>
