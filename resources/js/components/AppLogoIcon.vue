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
    <img
        src="/images/brand/powerx-logo.png"
        alt="PowerX logo"
        class="object-contain"
        :class="className"
        v-bind="$attrs"
    />
</template>
