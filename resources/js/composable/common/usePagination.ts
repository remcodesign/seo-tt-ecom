import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import type { LocationQueryRaw } from 'vue-router';

export interface UsePaginationOptions<TLink, TMeta> {
    defaultPage?: number;
    defaultPerPage?: number;
    initialMeta: TMeta;
    initialLinks?: TLink[];
    pageQueryKey?: string;
    perPageQueryKey?: string;
}

export function usePagination<TLink, TMeta>({
    defaultPage = 1,
    defaultPerPage = 10,
    initialMeta,
    initialLinks = [],
    pageQueryKey = 'page',
    perPageQueryKey = 'per_page',
}: UsePaginationOptions<TLink, TMeta>) {
    const route = useRoute();
    const router = useRouter();

    const page = ref<number>(defaultPage);
    const perPage = ref<number>(defaultPerPage);
    const links = ref<TLink[]>(initialLinks);
    const meta = ref<TMeta>(initialMeta);

    const parseQueryNumber = (value: unknown, fallback: number): number => {
        if (typeof value === 'string') {
            const numeric = Number(value);

            return Number.isNaN(numeric) ? fallback : numeric;
        }

        if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
            const numeric = Number(value[0]);

            return Number.isNaN(numeric) ? fallback : numeric;
        }

        return fallback;
    };

    const setPage = (pageNumber: number | null): void => {
        if (pageNumber === null || pageNumber === page.value) {
            return;
        }

        page.value = pageNumber;
    };

    const updateRoute = (query: LocationQueryRaw): void => {
        router.replace({
            query: {
                ...route.query,
                ...query,
            },
        });
    };

    watch(
        () => route.query,
        (query) => {
            const pageFromQuery = query[pageQueryKey];
            const perPageFromQuery = query[perPageQueryKey];

            page.value = parseQueryNumber(pageFromQuery, defaultPage);
            perPage.value = parseQueryNumber(perPageFromQuery, defaultPerPage);
        },
        { immediate: true },
    );

    return {
        page,
        perPage,
        links,
        meta,
        setPage,
        updateRoute,
    };
}
