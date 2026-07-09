<script setup lang="ts">
import Button from '@/components/common/Button.vue';

const links: Array<{ label: string; route: string }> = [
    { label: 'Home', route: 'home' },
    { label: 'Blog', route: 'posts.index' },
    { label: 'Comments', route: 'comments.index' },
    // { label: 'Admin', route: 'posts.index' },
];
</script>

<template>
    <nav>
        <ul class="flex items-center gap-2">
            <li v-for="link in links" :key="link.route">
                <router-link :to="{ name: link.route }" custom>
                    <template #default="{ navigate, isActive }">
                        <Button
                            variant="nav"
                            size="md"
                            :active="isActive"
                            class="px-4 py-2"
                            @click="navigate"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </router-link>
            </li>
        </ul>
    </nav>
</template>