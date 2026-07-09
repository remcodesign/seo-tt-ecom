import { computed, onMounted, ref } from 'vue';

export type Theme = 'light' | 'dark';

export function useTheme() {
    const theme = ref<Theme>('light');
    const isDark = computed(() => theme.value === 'dark');

    const applyTheme = (): void => {
        document.documentElement.classList.toggle('dark', isDark.value);

        try {
            localStorage.setItem('theme', theme.value);
        } catch {
            // Ignore storage failures.
        }
    };

    const toggleTheme = (): void => {
        theme.value = isDark.value ? 'light' : 'dark';
        applyTheme();
    };

    onMounted(() => {
        let storedTheme: Theme | null = null;

        try {
            storedTheme = localStorage.getItem('theme') as Theme | null;
        } catch {
            storedTheme = null;
        }

        if (storedTheme === 'light' || storedTheme === 'dark') {
            theme.value = storedTheme;
        } else if (document.documentElement.classList.contains('dark')) {
            theme.value = 'dark';
        }

        applyTheme();
    });

    return { theme, isDark, toggleTheme };
}