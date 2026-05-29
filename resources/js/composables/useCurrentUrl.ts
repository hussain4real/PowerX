import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, onMounted, onUnmounted, readonly, ref } from 'vue';
import { toUrl } from '@/lib/utils';

export type UseCurrentUrlReturn = {
    currentUrl: DeepReadonly<ComputedRef<string>>;
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
        startsWith?: boolean,
    ) => boolean;
    isCurrentOrParentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) => boolean;
    whenCurrentUrl: <T, F = null>(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: T,
        ifFalse?: F,
    ) => T | F;
};

const page = usePage();
const currentHash = ref(
    typeof window !== 'undefined' ? window.location.hash : '',
);

const browserOrigin =
    typeof window !== 'undefined' ? window.location.origin : 'http://localhost';

function syncCurrentHash(): void {
    currentHash.value =
        typeof window !== 'undefined' ? window.location.hash : '';
}

const currentUrlReactive = computed(() => {
    const url = new URL(page.url, browserOrigin);

    return `${url.pathname}${currentHash.value || url.hash}`;
});

export function useCurrentUrl(): UseCurrentUrlReturn {
    onMounted(() => {
        syncCurrentHash();
        window.addEventListener('hashchange', syncCurrentHash);
    });

    onUnmounted(() => {
        window.removeEventListener('hashchange', syncCurrentHash);
    });

    function isCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
        startsWith: boolean = false,
    ) {
        const urlString = toUrl(urlToCheck);
        const current = parseUrl(currentUrl ?? currentUrlReactive.value);
        const target = urlString ? parseUrl(urlString) : null;

        if (!current || !target) {
            return false;
        }

        const pathMatches = startsWith
            ? current.pathname.startsWith(target.pathname)
            : current.pathname === target.pathname;

        if (!pathMatches) {
            return false;
        }

        if (target.hash) {
            return current.hash === target.hash;
        }

        return startsWith || !current.hash;
    }

    function isCurrentOrParentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        return isCurrentUrl(urlToCheck, currentUrl, true);
    }

    function whenCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: any,
        ifFalse: any = null,
    ) {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        isCurrentUrl,
        isCurrentOrParentUrl,
        whenCurrentUrl,
    };
}

function parseUrl(url: string): Pick<URL, 'hash' | 'pathname'> | null {
    try {
        const parsedUrl = new URL(url, browserOrigin);

        return {
            hash: parsedUrl.hash,
            pathname: parsedUrl.pathname,
        };
    } catch {
        return null;
    }
}
