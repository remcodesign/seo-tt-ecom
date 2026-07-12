<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/common/Button.vue';
import { useAuth } from '@/composable/account/useAuth';

const auth = useAuth();
const showDropdown = ref(false);

const toggleDropdown = (): void => {
    showDropdown.value = !showDropdown.value;
};

const closeDropdown = (): void => {
    showDropdown.value = false;
};

const handleLogout = async (): Promise<void> => {
    await auth.logout();
    showDropdown.value = false;
};
</script>

<template>
    <div class="relative">
        <div v-if="showDropdown">
            <div class="fixed inset-0 z-20 bg-black/45 backdrop-blur-xs" @click="closeDropdown"></div>

            <div class="absolute right-0 z-30 mt-2 w-56 rounded-2xl border border-[#d6d6d1] bg-white shadow-lg ring-1 ring-black/5 dark:border-[#3E3E3A] dark:bg-[#161615]"
                @click.stop>
                <div
                    class="border-b border-[#e6e6e2] px-4 py-3 text-sm text-[#6C6C66] dark:border-[#3E3E3A] dark:text-[#A1A19A]">
                    <p class="font-semibold text-[#111113] dark:text-[#EDEDEC]">{{ auth.user.value?.name }}</p>
                    <p class="mt-1 truncate"><strong>Role:</strong> {{ auth.user.value?.role_label }}</p>
                </div>

                <div class="border-b border-[#e6e6e2] space-y-1 px-3 py-3">
                    <h4>Admin</h4>
                    <ul>
                        <li>
                            <!-- convert to Button -->
                            <router-link to="/blog/posts"
                                class="block rounded-lg px-3 py-2 text-sm text-[#111113] transition-colors hover:bg-[#f53003] hover:text-white dark:text-[#EDEDEC] dark:hover:bg-[#FF4433]">
                                Dashboard (posts)
                            </router-link>
                        </li>
                        <li>
                            <!-- convert to Button -->
                            <router-link to="/blog/comments"
                                class="block rounded-lg px-3 py-2 text-sm text-[#111113] transition-colors hover:bg-[#f53003] hover:text-white dark:text-[#EDEDEC] dark:hover:bg-[#FF4433]">
                                Dashboard (comments)
                            </router-link>
                        </li>
                    </ul>
                </div>

                <div class="space-y-1 px-3 py-3">
                    <Button variant="text" size="sm" class="w-full text-left" @click="handleLogout">
                        Logout
                    </Button>
                </div>
            </div>
        </div>

        <Button variant="bordered_normal" size="md" class="relative z-30 px-4 py-2" @click="toggleDropdown" data-test="account-menu-button">
            {{ auth.user.value?.name ?? 'Account' }}
        </Button>
    </div>
</template>
