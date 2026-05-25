<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M36.8 4 14.4 35.4h14.2L24.8 60 49.6 26.1H34.8L36.8 4Z"
        />
        <path
            fill="none"
            stroke="currentColor"
            stroke-width="4"
            d="M39.4 10.2a26 26 0 1 1-14.8 0"
        />
    </svg>
</template>
