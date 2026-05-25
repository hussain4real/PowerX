<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground shadow-[0_0_24px_rgba(255,193,7,0.35)]"
    >
        <AppLogoIcon class="size-6 text-current" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="truncate leading-tight font-bold tracking-wide"
            >Power<span class="text-powerx-yellow">X</span></span
        >
        <span
            class="truncate text-[10px] leading-tight font-semibold tracking-[0.18em] text-sidebar-foreground/60 uppercase"
            >Training Center</span
        >
    </div>
</template>
