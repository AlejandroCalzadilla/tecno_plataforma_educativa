<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        subtitle?: string;
        icon?: string;
        color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'indigo';
        trend?: number | null; // positivo = bueno, negativo = malo
        trendLabel?: string;
    }>(),
    {
        color: 'blue',
        trend: null,
    },
);

const colorClasses = computed(() => {
    const map: Record<string, { bg: string; text: string; light: string }> = {
        blue:   { bg: 'bg-blue-500',   text: 'text-blue-600',   light: 'bg-blue-50 dark:bg-blue-950/40' },
        green:  { bg: 'bg-emerald-500', text: 'text-emerald-600', light: 'bg-emerald-50 dark:bg-emerald-950/40' },
        yellow: { bg: 'bg-amber-500',  text: 'text-amber-600',  light: 'bg-amber-50 dark:bg-amber-950/40' },
        red:    { bg: 'bg-red-500',    text: 'text-red-600',    light: 'bg-red-50 dark:bg-red-950/40' },
        purple: { bg: 'bg-purple-500', text: 'text-purple-600', light: 'bg-purple-50 dark:bg-purple-950/40' },
        indigo: { bg: 'bg-indigo-500', text: 'text-indigo-600', light: 'bg-indigo-50 dark:bg-indigo-950/40' },
    };
    return map[props.color] ?? map.blue;
});
</script>

<template>
    <div
        class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
    >
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {{ title }}
                </p>
                <p :class="['mt-1 text-3xl font-bold', colorClasses.text]">{{ value }}</p>
                <p v-if="subtitle" class="mt-1 text-xs text-muted-foreground">{{ subtitle }}</p>
            </div>
            <div v-if="icon" :class="['rounded-xl p-3 text-xl', colorClasses.light]">
                {{ icon }}
            </div>
        </div>

        <div v-if="trend !== null" class="mt-3 flex items-center gap-1 text-xs">
            <span
                :class="[
                    'flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold',
                    trend >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                ]"
            >
                {{ trend >= 0 ? '▲' : '▼' }} {{ Math.abs(trend) }}%
            </span>
            <span class="text-muted-foreground">{{ trendLabel }}</span>
        </div>
    </div>
</template>
