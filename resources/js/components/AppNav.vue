<script setup lang="ts">
const links: Array<{ label: string; route: string }> = [
    { label: 'Home', route: 'home' },
];
</script>

<template>
    <nav>
        <ul class="flex items-center gap-6">
            <li v-for="link in links" :key="link.route">
                <router-link
                    :to="{ name: link.route }"
                    class="text-sm transition-colors hover:text-[#6C6C66] dark:hover:text-[#A1A19A]"
                    active-class="font-medium"
                >
                    {{ link.label }}
                </router-link>
            </li>
        </ul>
    </nav>
</template>