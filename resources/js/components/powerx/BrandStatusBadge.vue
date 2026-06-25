<script setup lang="ts">
import { CheckCircle2, Clock3, RadioTower, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

type Tone = 'success' | 'warning' | 'info' | 'neutral';

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: Tone;
    }>(),
    {
        tone: 'neutral',
    },
);

const toneClasses: Record<Tone, string> = {
    success:
        'border-powerx-success/35 bg-powerx-success/10 text-green-700 shadow-[0_0_18px_rgba(45,185,33,0.10)] dark:border-powerx-success/50 dark:bg-powerx-success/15 dark:text-green-300 dark:shadow-[0_0_22px_rgba(45,185,33,0.16)]',
    warning:
        'border-powerx-gold/45 bg-powerx-yellow/20 text-powerx-navy shadow-[0_0_20px_rgba(255,193,7,0.16)] dark:border-powerx-yellow dark:bg-powerx-yellow dark:text-powerx-navy dark:shadow-[0_0_24px_rgba(255,193,7,0.24)]',
    info: 'border-powerx-blue/30 bg-powerx-blue/10 text-powerx-blue shadow-[0_0_18px_rgba(0,107,255,0.10)] dark:border-powerx-cyan/55 dark:bg-powerx-blue/20 dark:text-powerx-cyan dark:shadow-[0_0_22px_rgba(13,202,240,0.14)]',
    neutral:
        'border-powerx-navy/25 bg-powerx-navy/10 text-powerx-navy shadow-[0_0_18px_rgba(7,21,35,0.10)] dark:border-white/25 dark:bg-white/10 dark:text-white dark:shadow-[0_0_18px_rgba(255,255,255,0.08)]',
};

const icon = computed(() => {
    const icons = {
        success: CheckCircle2,
        warning: Clock3,
        info: RadioTower,
        neutral: ShieldCheck,
    };

    return icons[props.tone];
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[0.72rem] leading-none font-black tracking-[0.14em] uppercase backdrop-blur hover:-translate-y-0.5 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:hover:translate-y-0',
            toneClasses[props.tone],
        ]"
    >
        <component :is="icon" class="size-3.5" />
        {{ label }}
    </span>
</template>
