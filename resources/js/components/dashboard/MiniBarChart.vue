<script setup lang="ts">
import { computed } from 'vue';

export interface BarItem {
    label: string;
    value: number;
    badge?: string;
}

const props = withDefaults(
    defineProps<{
        items: BarItem[];
        color?: string;
        formatValue?: (v: number) => string;
    }>(),
    {
        color: 'bg-blue-500',
    },
);

const max = computed(() => Math.max(...props.items.map((i) => i.value), 1));
const pct = (v: number) => ((v / max.value) * 100).toFixed(1);
const fmt = (v: number) => (props.formatValue ? props.formatValue(v) : String(v));
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-for="item in items" :key="item.label" class="flex items-center gap-3">
            <div class="w-32 min-w-0 shrink-0 truncate text-xs text-muted-foreground" :title="item.label">
                {{ item.label }}
            </div>
            <div class="relative flex-1">
                <div class="h-5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        :class="[color, 'h-full rounded-full transition-all duration-500']"
                        :style="{ width: pct(item.value) + '%' }"
                    />
                </div>
            </div>
            <div class="flex w-16 items-center justify-end gap-1 text-xs font-semibold text-foreground">
                {{ fmt(item.value) }}
                <span v-if="item.badge" class="rounded bg-muted px-1 text-muted-foreground">{{ item.badge }}</span>
            </div>
        </div>
    </div>
</template>
