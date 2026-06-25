<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

type Direction = 'up' | 'down' | 'left' | 'right' | 'fade' | 'scale';
type Distance = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        as?: string;
        delay?: number;
        direction?: Direction;
        distance?: Distance;
        duration?: number;
        once?: boolean;
        rootMargin?: string;
        threshold?: number;
    }>(),
    {
        as: 'div',
        delay: 0,
        direction: 'up',
        distance: 'md',
        duration: 650,
        once: true,
        rootMargin: '0px 0px 10% 0px',
        threshold: 0.08,
    },
);

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();

const { isVisible, target } = useScrollReveal({
    once: props.once,
    rootMargin: props.rootMargin,
    threshold: props.threshold,
});

const hiddenDistanceClasses: Record<Distance, Record<Direction, string>> = {
    sm: {
        down: '-translate-y-4',
        fade: '',
        left: 'translate-y-4 sm:-translate-x-4 sm:translate-y-0',
        right: 'translate-y-4 sm:translate-x-4 sm:translate-y-0',
        scale: 'scale-[0.98]',
        up: 'translate-y-4',
    },
    md: {
        down: '-translate-y-8',
        fade: '',
        left: 'translate-y-8 sm:-translate-x-8 sm:translate-y-0',
        right: 'translate-y-8 sm:translate-x-8 sm:translate-y-0',
        scale: 'scale-[0.96]',
        up: 'translate-y-8',
    },
    lg: {
        down: '-translate-y-12',
        fade: '',
        left: 'translate-y-12 sm:-translate-x-12 sm:translate-y-0',
        right: 'translate-y-12 sm:translate-x-12 sm:translate-y-0',
        scale: 'scale-[0.94]',
        up: 'translate-y-12',
    },
};

const revealClass = computed(() =>
    isVisible.value
        ? 'translate-x-0 translate-y-0 scale-100 opacity-100 blur-0'
        : `opacity-0 blur-[2px] ${hiddenDistanceClasses[props.distance][props.direction]}`,
);

const motionStyle = computed(() => ({
    transitionDelay: isVisible.value ? `${props.delay}ms` : '0ms',
    transitionDuration: `${props.duration}ms`,
}));
</script>

<template>
    <component
        :is="as"
        ref="target"
        v-bind="attrs"
        :class="[
            'motion-reduce:blur-0 box-border max-w-full will-change-transform motion-safe:transition-[opacity,transform,filter] motion-safe:ease-out motion-reduce:transform-none motion-reduce:opacity-100 motion-reduce:transition-none',
            revealClass,
        ]"
        :style="motionStyle"
    >
        <slot />
    </component>
</template>
