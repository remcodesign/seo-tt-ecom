<script setup lang="ts">
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
                        <button
                            type="button"
                            @click="navigate"
                            class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm transition-all duration-150"
                            :class="isActive
                                ? 'bg-[#f53003] text-white shadow-sm dark:bg-[#FF4433] font-bold'
                                : 'text-[#1b1b18] hover:text-[#6C6C66] dark:text-[#EDEDEC] dark:hover:text-[#A1A19A] cursor-pointer'"
                        >
                            {{ link.label }}
                        </button>
                    </template>
                </router-link>
            </li>
        </ul>
    </nav>
</template>