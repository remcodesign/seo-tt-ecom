<script setup lang="ts">
import { ref } from 'vue';
import type { AxiosError } from 'axios';
import Button from '@/components/common/Button.vue';
import Modal from '@/components/common/Modal.vue';
import { useAuth } from '@/composable/account/useAuth';
import type { UserDataResponse } from '@/generated/types';

const props = withDefaults(defineProps<{ show?: boolean }>(), {
    show: false,
});

const emit = defineEmits<{
    close: [];
    'login-success': [UserDataResponse];
}>();

const auth = useAuth();
const email = ref('');
const password = ref('');
const deviceName = ref('browser');
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

const resetForm = (): void => {
    email.value = '';
    password.value = '';
    deviceName.value = 'browser';
    errors.value = {};
};

const close = (): void => {
    resetForm();
    emit('close');
};

const handleSubmit = async (): Promise<void> => {
    errors.value = {};
    submitting.value = true;

    try {
        const user = await auth.login({
            email: email.value,
            password: password.value,
            device_name: deviceName.value,
        });

        // Emit the login-success event with the user data
        emit('login-success', user);
        close();
    } catch (error: unknown) {
        const axiosError = error as AxiosError<{
            errors?: Record<string, string[]>;
        }>;

        if (axiosError.response?.status === 422 && axiosError.response.data?.errors) {
            // Map the validation errors to a simpler format
            errors.value = Object.entries(axiosError.response.data.errors).reduce(
                (accumulator, [field, messages]) => {
                    accumulator[field] = Array.isArray(messages) ? messages[0] : String(messages);
                    return accumulator;
                },
                {} as Record<string, string>,
            );
        } else {
            errors.value.general = 'Unable to login. Check your credentials and try again.';
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>

    <Modal :show="props.show" @close="close">
        <div class="space-y-6 p-6" data-test="login-modal">
            <!-- Header -->
            <div>
                <h2 class="text-xl font-semibold text-[#111113] dark:text-[#EDEDEC]">Login</h2>
                <p class="mt-1 text-sm text-[#6C6C66] dark:text-[#A1A19A]">
                    Sign in with your email and password.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-[#111113] dark:text-[#EDEDEC]">
                        Email
                        <input type="email" data-test="login-email-input" v-model="email" required
                            class="mt-2 w-full rounded-xl border border-[#D6D6D1] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f5300350] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                    </label>
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-[#111113] dark:text-[#EDEDEC]">
                        Password
                        <input type="password" data-test="login-password-input" v-model="password" required
                            class="mt-2 w-full rounded-xl border border-[#D6D6D1] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f5300350] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                    </label>
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                </div>

                <!-- Device Name :: not shown -->
                <div v-show="false">
                    <label class="block text-sm font-medium text-[#111113] dark:text-[#EDEDEC]">
                        Device name
                        <input type="text" v-model="deviceName" required
                            class="mt-2 w-full rounded-xl border border-[#D6D6D1] bg-white px-3 py-2 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f5300350] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                    </label>
                    <p v-if="errors.device_name" class="mt-1 text-sm text-red-600">{{ errors.device_name }}</p>
                </div>

                <!-- Error -->
                <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                <div class="flex justify-end">
                    <Button data-test="login-submit-button" :disabled="submitting" type="submit">
                        {{ submitting ? 'Signing in...' : 'Sign in' }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

</template>
