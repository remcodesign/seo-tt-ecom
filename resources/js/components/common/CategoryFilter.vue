<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import type { CategoryDataResponse, CategoryIdsData } from '@types';
import api from '@/api';
import Button from '@/components/common/Button.vue';

const props = withDefaults(defineProps<{
    type: string;
    selectedIds?: CategoryIdsData['category_ids'];
}>(), {
    selectedIds: () => [],
});

const emit = defineEmits<{
    change: [ids: CategoryIdsData['category_ids']];
}>();

const categories = ref<CategoryDataResponse[]>([]);
const loading = ref(false);
const selectedIds = ref<Set<number>>(new Set(props.selectedIds));

const fetchCategories = async (): Promise<void> => {
    if (!props.type) {
        return;
    }

    loading.value = true;

    try {
        const response = await api.get<{ data: CategoryDataResponse[] }>('/poly/categories', {
            params: { type: props.type, per_page: 100 },
        });

        categories.value = response.data.data;

        // If no initial selection was provided, select all
        if (props.selectedIds.length === 0) {
            selectedIds.value = new Set(categories.value.map((c) => c.id));
        }
    } catch {
        categories.value = [];
    } finally {
        loading.value = false;
    }
};

const toggleCategory = (id: number): void => {
    const next = new Set(selectedIds.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    selectedIds.value = next;
    emitChange();
};

const selectAll = (): void => {
    selectedIds.value = new Set(categories.value.map((c) => c.id));
    emitChange();
};

const selectNone = (): void => {
    selectedIds.value = new Set();
    emitChange();
};

const emitChange = (): void => {
    emit('change', [...selectedIds.value] as CategoryIdsData['category_ids']);
};

watch(() => props.selectedIds, (ids) => {
    selectedIds.value = new Set(ids);
}, { deep: true });

watch(() => props.type, () => {
    void fetchCategories();
});

onMounted(() => {
    void fetchCategories();
});
</script>

<template>
    <div v-if="categories.length > 0" class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
            Categories:
        </span>

        <Button
            variant="bordered_normal"
            size="sm"
            :active="selectedIds.size === categories.length"
            @click="selectAll"
        >
            All
        </Button>

        <Button
            variant="bordered_normal"
            size="sm"
            @click="selectNone"
        >
            Clear
        </Button>

        ::

        <Button
            v-for="category in categories"
            :key="category.id"
            variant="bordered_normal"
            size="sm"
            :active="selectedIds.has(category.id)"
            @click="toggleCategory(category.id)"
        >
            {{ category.name }}
        </Button>
    </div>
</template>