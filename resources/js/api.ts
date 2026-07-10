import axios from 'axios';

const tokenCookieName = 'sanctum_token';

// Create an Axios instance with a base URL and default headers
const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = getToken();

    if (token) {
        config.headers = config.headers ?? {};
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

const getToken = (): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const record = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith(`${tokenCookieName}=`));

    return record ? decodeURIComponent(record.substring(tokenCookieName.length + 1)) : null;
};

export default api;