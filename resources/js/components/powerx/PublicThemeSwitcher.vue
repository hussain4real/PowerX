<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', Icon: Sun, label: 'Light theme' },
    { value: 'dark', Icon: Moon, label: 'Dark theme' },
    { value: 'system', Icon: Monitor, label: 'System theme' },
] as const;
</script>

<template>
    <div
        class="inline-flex items-center gap-1 rounded-full border border-border bg-card/85 p-1 text-card-foreground shadow-sm backdrop-blur"
        aria-label="Theme switcher"
    >
        <button
            v-for="{ value, Icon, label } in options"
            :key="value"
            type="button"
            :title="label"
            :aria-label="label"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
            :class="[
                'inline-flex size-8 items-center justify-center rounded-full transition',
                appearance === value
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="size-4" />
        </button>
    </div>
</template>
