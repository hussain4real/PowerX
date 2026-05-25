<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
    detail: string;
}>();
</script>

<template>
    <article
        class="rounded-2xl border border-white/10 bg-white/[0.05] p-5 shadow-[0_20px_50px_rgba(0,0,0,0.22)]"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p
                    class="text-xs font-bold tracking-[0.22em] text-powerx-yellow uppercase"
                >
                    {{ label }}
                </p>
                <p class="mt-3 text-3xl font-black tracking-tight text-white">
                    {{ value }}
                </p>
            </div>
            <div
                class="flex size-11 items-center justify-center rounded-xl border border-powerx-yellow/25 bg-powerx-yellow/15 text-powerx-yellow"
            >
                <slot name="icon" />
            </div>
        </div>
        <p class="mt-4 text-sm leading-6 text-white/60">{{ detail }}</p>
    </article>
</template>
