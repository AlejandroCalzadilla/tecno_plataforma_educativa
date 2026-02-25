<script setup lang="ts">
import { computed } from 'vue';

export interface DistSegment {
    label: string;
    value: number;
    color: string; // tailwind bg class
}

const props = defineProps<{
    segments: DistSegment[];
    showLegend?: boolean;
    formatValue?: (v: number) => string;
}>();

const total = computed(() => props.segments.reduce((s, seg) => s + seg.value, 0));

const pct = (v: number) => (total.value > 0 ? ((v / total.value) * 100).toFixed(1) : '0.0');

const fmt = (v: number) => (props.formatValue ? props.formatValue(v) : String(v));
</script>

<template>
    <div class="w-full">
        <!-- Barra apilada -->
        <div class="flex h-5 w-full overflow-hidden rounded-full bg-muted">
            <div
                v-for="seg in segments"
                :key="seg.label"
                :class="[seg.color, 'transition-all duration-500']"
                :style="{ width: pct(seg.value) + '%' }"
                :title="`${seg.label}: ${fmt(seg.value)} (${pct(seg.value)}%)`"
            />
        </div>

        <!-- Leyenda -->
        <div v-if="showLegend !== false" class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
            <div v-for="seg in segments" :key="seg.label" class="flex items-center gap-1.5 text-xs">
                <span :class="[seg.color, 'inline-block h-2.5 w-2.5 rounded-sm']" />
                <span class="text-muted-foreground">{{ seg.label }}</span>
                <span class="font-semibold text-foreground">{{ fmt(seg.value) }}</span>
                <span class="text-muted-foreground">({{ pct(seg.value) }}%)</span>
            </div>
        </div>
    </div>
</template>
