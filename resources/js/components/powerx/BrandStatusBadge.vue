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
        'border-powerx-success/50 bg-powerx-success/15 text-green-300 shadow-[0_0_22px_rgba(45,185,33,0.16)]',
    warning:
        'border-powerx-yellow bg-powerx-yellow text-powerx-navy shadow-[0_0_24px_rgba(255,193,7,0.24)]',
    info: 'border-powerx-cyan/55 bg-powerx-blue/20 text-powerx-cyan shadow-[0_0_22px_rgba(13,202,240,0.14)]',
    neutral:
        'border-white/25 bg-white/10 text-white shadow-[0_0_18px_rgba(255,255,255,0.08)]',
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
            'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[0.72rem] leading-none font-black tracking-[0.14em] uppercase backdrop-blur',
            toneClasses[props.tone],
        ]"
    >
        <component :is="icon" class="size-3.5" />
        {{ label }}
    </span>
</template>
