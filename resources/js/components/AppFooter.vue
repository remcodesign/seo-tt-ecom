<script setup lang="ts">
import Button from '@/components/common/Button.vue';
import { useTheme } from '@/composable/common/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <footer class="border-t border-[#19140035]  dark:border-[#3E3E3A] ">
        <div class="mx-auto max-w-5xl  px-4 py-6 text-center text-xs 
            text-[#6C6C66] dark:text-[#A1A19A] sm:px-6 lg:px-8">
            <div class="flex items-center justify-center gap-2">
                <span>&copy; {{ new Date().getFullYear() }} SEO TT Ecom. All rights reserved.</span>

                <!-- Theme toggle -->
                <Button variant="text" size="xs" @click="toggleTheme"
                    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <!-- Sun icon for light mode -->
                    <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="h-3.5 w-3.5">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2" />
                        <path d="M12 20v2" />
                        <path d="m4.93 4.93 1.41 1.41" />
                        <path d="m17.66 17.66 1.41 1.41" />
                        <path d="M2 12h2" />
                        <path d="M20 12h2" />
                        <path d="m6.34 17.66-1.41 1.41" />
                        <path d="m19.07 4.93-1.41 1.41" />
                    </svg>

                    <!-- Moon icon for dark mode -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg>
                </Button>
            </div>
        </div>
    </footer>
</template>