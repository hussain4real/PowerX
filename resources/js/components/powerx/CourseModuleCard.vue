<script setup lang="ts">
import { CheckCircle2, LockKeyhole, PlayCircle } from 'lucide-vue-next';
import { computed } from 'vue';

type Status = 'complete' | 'current' | 'locked';

const props = withDefaults(
    defineProps<{
        index: string;
        title: string;
        description: string;
        status?: Status;
    }>(),
    {
        status: 'current',
    },
);

const statusIcon = computed(() => {
    const icons = {
        complete: CheckCircle2,
        current: PlayCircle,
        locked: LockKeyhole,
    };

    return icons[props.status];
});

const statusClasses = computed(() => {
    const classes = {
        complete: 'text-powerx-success',
        current: 'text-powerx-yellow',
        locked: 'text-white/45',
    };

    return classes[props.status];
});
</script>

<template>
    <article
        class="group rounded-2xl border border-white/10 bg-white/[0.04] p-4 transition hover:-translate-y-0.5 hover:border-powerx-yellow/50 hover:bg-white/[0.07]"
    >
        <div class="flex items-start gap-4">
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-powerx-yellow text-sm font-black text-powerx-navy shadow-[0_0_22px_rgba(255,193,7,0.28)]"
            >
                {{ index }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-bold text-white">{{ title }}</h3>
                    <component
                        :is="statusIcon"
                        :class="['size-5 shrink-0', statusClasses]"
                    />
                </div>
                <p class="mt-2 text-sm leading-6 text-white/65">
                    {{ description }}
                </p>
            </div>
        </div>
    </article>
</template>
