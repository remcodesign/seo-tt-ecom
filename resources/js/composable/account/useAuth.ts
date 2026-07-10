import { computed, ref } from 'vue';
import type { CreateTokenData, RevokeTokenDataResponse, UserDataResponse, TokenDataResponse } from '@/generated/types';
import api from '@/api';

const user = ref<UserDataResponse | null>(null);
const initialized = ref(false);
const tokenCookieName = 'sanctum_token';
const tokenMaxAge = 60 * 60 * 24 * 30; // 30 days

const getToken = (): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const name = `${tokenCookieName}=`;
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith(name));

    if (!cookie) {
        return null;
    }

    return decodeURIComponent(cookie.substring(name.length));
};

const setToken = (token: string | null): void => {
    if (typeof document === 'undefined') {
        return;
    }

    if (token) {
        document.cookie = `${tokenCookieName}=${encodeURIComponent(token)}; Path=/; Max-Age=${tokenMaxAge}; SameSite=Lax`;
    } else {
        document.cookie = `${tokenCookieName}=; Path=/; Max-Age=0; SameSite=Lax`;
    }
};

const isAuthenticated = computed((): boolean => user.value !== null);

const initializeAuth = async (): Promise<void> => {
    if (initialized.value) {
        return;
    }

    initialized.value = true;

    const token = getToken();

    if (!token) {
        user.value = null;
        return;
    }

    try {
        const response = await api.get<UserDataResponse>('/user');
        user.value = response.data;
    } catch {
        setToken(null);
        user.value = null;
    }
};

const login = async (createTokenData: CreateTokenData): Promise<UserDataResponse> => {
    const response = await api.post<TokenDataResponse>('/sanctum/token', createTokenData);

    setToken(response.data.token);

    const userResponse = await api.get<UserDataResponse>('/user');
    user.value = userResponse.data;

    return user.value;
};

const logout = async (): Promise<void> => {
    try {
        await api.delete<RevokeTokenDataResponse>('/sanctum/tokens/current');
    } catch {
        // Ignore failures when revoking; clear local auth state regardless.
    }

    setToken(null);
    user.value = null;
};

export function useAuth() {
    return {
        user,
        isAuthenticated,
        initializeAuth,
        login,
        logout,
    };
}
