<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Button from '@/components/common/Button.vue';
import LoginModal from '@/components/account/LoginModal.vue';
import AccountMenu from '@/components/account/AccountMenu.vue';
import { useAuth } from '@/composable/account/useAuth';

const links: Array<{ label: string; route: string }> = [
    { label: 'Home', route: 'home' },
    { label: 'Blog', route: 'posts.index' },
    { label: 'Comments', route: 'comments.index' },
];

const auth = useAuth();

const showLoginModal = ref(false);
const showMobileMenu = ref(false);

const handleLoginSuccess = (): void => {
    showLoginModal.value = false;
};

const toggleMobileMenu = (): void => {
    showMobileMenu.value = !showMobileMenu.value;
};

const closeMobileMenu = (): void => {
    showMobileMenu.value = false;
};

const handleMobileNavClick = (navigate: () => void): void => {
    navigate();
    closeMobileMenu();
};

const handleLogout = async (): Promise<void> => {
    await auth.logout();
    closeMobileMenu();
};

onMounted(async () => {
    await auth.initializeAuth();
});
</script>

<template>
    <!-- Desktop nav -->
    <nav class="relative hidden lg:block">
        <ul class="flex items-center gap-2">
            <li v-for="link in links" :key="link.route">
                <router-link :to="{ name: link.route }" custom>
                    <template #default="{ navigate, isActive }">
                        <Button variant="nav" size="md" :active="isActive" class="px-4 py-2" @click="navigate">
                            {{ link.label }}
                        </Button>
                    </template>
                </router-link>
            </li>

            <li class="ml-4">
                <template v-if="auth.isAuthenticated.value">
                    <AccountMenu />
                </template>

                <template v-else>
                    <Button data-test="nav-login-button" variant="bordered_normal" size="md" class="px-4 py-2"
                        @click="showLoginModal = true">
                        Login
                    </Button>
                </template>
            </li>
        </ul>

        <Transition name="fade">
            <LoginModal :show="showLoginModal" @close="showLoginModal = false" @login-success="handleLoginSuccess" />
        </Transition>
    </nav>

    <!-- Mobile hamburger button and transition from closed to open -->
    <button
        class="lg:hidden relative z-50 flex size-10 items-center justify-center 
        rounded-md border border-[#8a7f4f40] dark:border-[#3E3E3A]  transition-all duration-300 
        cursor-pointer hover:shadow-lg hover:shadow-[#f53003]/20 dark:hover:shadow-[#FF4433]/30"
        :class="{ 'border-[#f53003] text-[#f53003] dark:border-[#FF4433] dark:text-[#FF4433]': showMobileMenu }"
        @click="toggleMobileMenu" aria-label="Toggle navigation menu" data-test="mobile-menu-button">

        <span class="flex flex-col gap-1.5">
            <span class="block h-0.5 w-5 rounded-full bg-current transition-all duration-200"
                :class="{ 'translate-y-2 rotate-45': showMobileMenu }"></span>
            <span class="block h-0.5 w-5 rounded-full bg-current transition-all duration-200"
                :class="{ 'opacity-0': showMobileMenu }"></span>
            <span class="block h-0.5 w-5 rounded-full bg-current transition-all duration-200"
                :class="{ '-translate-y-2 -rotate-45': showMobileMenu }"></span>
        </span>

    </button>

    <!-- Mobile menu backdrop -->
    <Transition name="fade">
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-black/45 backdrop-blur-xs lg:hidden"
            @click="closeMobileMenu"></div>
    </Transition>

    <!-- Mobile menu panel -->
    <Transition name="slide">
        <div v-if="showMobileMenu"
            class="fixed inset-y-0 right-0 z-40 flex w-72 flex-col border-l border-[#e6e6e2] bg-white shadow-xl dark:border-[#3E3E3A] dark:bg-[#161615] lg:hidden">

            <!-- Mobile nav links -->
            <nav class="flex-1 overflow-y-auto px-4 pt-20">
                <ul class="space-y-1">
                    <li v-for="link in links" :key="`mobile-${link.route}`">
                        <router-link :to="{ name: link.route }" custom>
                            <template #default="{ navigate, isActive }">
                               <Button variant="nav" size="md" :active="isActive"
                                    class="w-full rounded-lg px-4 py-3 text-left text-sm font-medium transition-colors"
                                    @click="handleMobileNavClick(navigate)">
                                    {{ link.label }}
                                </button>
                            </template>
                        </router-link>
                    </li>
                </ul>
            </nav>

            <!-- Mobile account section -->
            <div class="border-t border-[#e6e6e2] px-4 py-4 dark:border-[#3E3E3A]">
                <template v-if="auth.isAuthenticated.value">
                    <div class="mb-3 border-b border-[#e6e6e2] pb-3 dark:border-[#3E3E3A]">
                        <p class="text-sm font-semibold text-[#111113] dark:text-[#EDEDEC]">{{ auth.user.value?.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-[#6C6C66] dark:text-[#A1A19A]">Role: {{
                            auth.user.value?.role_label }}</p>
                    </div>

                    <ul class="mb-3 space-y-1">
                        <li>
                            <router-link to="/blog/posts"
                                class="block rounded-lg px-4 py-2 text-sm text-[#111113] transition-colors hover:bg-[#f53003] hover:text-white dark:text-[#EDEDEC] dark:hover:bg-[#FF4433]"
                                @click="closeMobileMenu">
                                Dashboard (posts)
                            </router-link>
                        </li>
                        <li>
                            <router-link to="/blog/comments"
                                class="block rounded-lg px-4 py-2 text-sm text-[#111113] transition-colors hover:bg-[#f53003] hover:text-white dark:text-[#EDEDEC] dark:hover:bg-[#FF4433]"
                                @click="closeMobileMenu">
                                Dashboard (comments)
                            </router-link>
                        </li>
                    </ul>

                    <Button variant="text" size="sm" class="w-full text-left" @click="handleLogout">
                        Logout
                    </Button>
                </template>

                <template v-else>
                    <Button variant="bordered_normal" size="md" class="w-full px-4 py-2"
                        @click="showLoginModal = true; closeMobileMenu()">
                        Login
                    </Button>
                </template>
            </div>
            
        </div>
    </Transition>

    <LoginModal :show="showLoginModal" @close="showLoginModal = false" @login-success="handleLoginSuccess"  />
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.25s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>