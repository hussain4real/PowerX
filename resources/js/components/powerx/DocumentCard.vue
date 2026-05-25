<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
    meta: string;
}>();
</script>

<template>
    <article
        class="rounded-2xl border border-powerx-yellow/25 bg-gradient-to-br from-powerx-yellow/15 via-white/[0.04] to-white/[0.02] p-5"
    >
        <div class="flex items-start gap-4">
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-powerx-yellow/30 bg-powerx-yellow text-xs font-black text-powerx-navy"
            >
                PDF
            </div>
            <div>
                <p
                    class="text-xs font-bold tracking-[0.24em] text-powerx-yellow uppercase"
                >
                    {{ meta }}
                </p>
                <h3 class="mt-2 text-lg font-black text-white">{{ title }}</h3>
                <p class="mt-2 text-sm leading-6 text-white/65">
                    {{ description }}
                </p>
            </div>
        </div>
    </article>
</template>
