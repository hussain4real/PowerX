import { onMounted, onUnmounted, ref } from 'vue';

interface ScrollRevealOptions {
    once?: boolean;
    rootMargin?: string;
    threshold?: number;
}

export function useScrollReveal(options: ScrollRevealOptions = {}) {
    const target = ref<HTMLElement | null>(null);
    const isVisible = ref(true);
    const prefersReducedMotion = ref(false);

    let observer: IntersectionObserver | null = null;
    let fallbackFrame: number | null = null;
    let motionQuery: MediaQueryList | null = null;
    let removeFallbackListeners: (() => void) | null = null;

    const stopFallback = (): void => {
        if (fallbackFrame !== null && typeof window !== 'undefined') {
            window.cancelAnimationFrame(fallbackFrame);
        }

        fallbackFrame = null;
        removeFallbackListeners?.();
        removeFallbackListeners = null;
    };

    const showImmediately = (): void => {
        isVisible.value = true;
        observer?.disconnect();
        observer = null;
        stopFallback();
    };

    const handleMotionPreference = (event: MediaQueryListEvent): void => {
        prefersReducedMotion.value = event.matches;

        if (event.matches) {
            showImmediately();
        }
    };

    onMounted(() => {
        if (typeof window === 'undefined') {
            showImmediately();

            return;
        }

        motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        prefersReducedMotion.value = motionQuery.matches;

        if (motionQuery.matches || !('IntersectionObserver' in window)) {
            showImmediately();

            return;
        }

        if (!target.value) {
            showImmediately();

            return;
        }

        const syncFallbackVisibility = (): void => {
            if (!target.value) {
                showImmediately();

                return;
            }

            const verticalBuffer = window.innerHeight * 0.1;
            const rect = target.value.getBoundingClientRect();
            const isNearViewport =
                rect.top <= window.innerHeight + verticalBuffer &&
                rect.bottom >= -verticalBuffer;

            if (isNearViewport) {
                showImmediately();

                return;
            }

            isVisible.value = false;
        };

        const queueFallbackVisibility = (): void => {
            if (fallbackFrame !== null) {
                return;
            }

            fallbackFrame = window.requestAnimationFrame(() => {
                fallbackFrame = null;
                syncFallbackVisibility();
            });
        };

        window.addEventListener('scroll', queueFallbackVisibility, {
            passive: true,
        });
        window.addEventListener('resize', queueFallbackVisibility);
        removeFallbackListeners = (): void => {
            window.removeEventListener('scroll', queueFallbackVisibility);
            window.removeEventListener('resize', queueFallbackVisibility);
        };

        observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    isVisible.value = true;

                    if (options.once ?? true) {
                        observer?.disconnect();
                        observer = null;
                    }

                    return;
                }

                if (!(options.once ?? true)) {
                    isVisible.value = false;
                }
            },
            {
                rootMargin: options.rootMargin ?? '0px 0px 10% 0px',
                threshold: options.threshold ?? 0.08,
            },
        );

        observer.observe(target.value);
        motionQuery.addEventListener('change', handleMotionPreference);
        syncFallbackVisibility();
    });

    onUnmounted(() => {
        observer?.disconnect();
        stopFallback();
        motionQuery?.removeEventListener('change', handleMotionPreference);
    });

    return {
        isVisible,
        prefersReducedMotion,
        target,
    };
}
