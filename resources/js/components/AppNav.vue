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

const handleLoginSuccess = (): void => {
    showLoginModal.value = false;
};

onMounted(async () => {
    await auth.initializeAuth();
});
</script>

<template>
    <nav class="relative">
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

            <li class="ml-4">
                <template v-if="auth.isAuthenticated.value">
                    <AccountMenu />
                </template>

                <template v-else>
                    <!-- Login Button -->
                    <Button variant="bordered_normal" size="md" class="px-4 py-2" @click="showLoginModal = true">
                        Login
                    </Button>
                </template>
            </li>
        </ul>

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" @login-success="handleLoginSuccess" />
    </nav>
</template>