<script setup lang="ts">
defineProps<{ label: string; value: string; detail: string }>();
</script>
<template>
    <article
        class="group rounded-2xl border border-border bg-muted/70 p-5 shadow-[0_20px_50px_rgba(0,0,0,0.22)] hover:-translate-y-0.5 hover:border-powerx-yellow/60 hover:shadow-[0_24px_70px_rgba(255,193,7,0.14)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0 dark:border-white/10 dark:bg-white/[0.05]"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p
                    class="text-xs font-bold tracking-[0.22em] text-powerx-yellow uppercase"
                >
                    {{ label }}
                </p>
                <p
                    class="mt-3 text-3xl font-black text-foreground dark:text-white"
                >
                    {{ value }}
                </p>
            </div>
            <div
                class="flex size-11 items-center justify-center rounded-xl border border-powerx-yellow/25 bg-powerx-yellow/15 text-powerx-yellow group-hover:scale-110 motion-safe:transition-transform motion-safe:duration-300"
            >
                <slot name="icon" />
            </div>
        </div>
        <p
            class="mt-4 text-sm leading-6 text-muted-foreground dark:text-white/60"
        >
            {{ detail }}
        </p>
    </article>
</template>
