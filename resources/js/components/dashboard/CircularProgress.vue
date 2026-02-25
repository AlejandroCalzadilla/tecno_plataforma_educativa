<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number; // 0-100
        size?: number;
        strokeWidth?: number;
        color?: string;
        label?: string;
    }>(),
    {
        size: 80,
        strokeWidth: 8,
        color: 'stroke-blue-500',
        label: '',
    },
);

const r = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * r.value);
const dashOffset = computed(() => circumference.value - (props.value / 100) * circumference.value);
const center = computed(() => props.size / 2);
</script>

<template>
    <div class="flex flex-col items-center gap-1">
        <svg :width="size" :height="size" class="-rotate-90">
            <!-- Pista de fondo -->
            <circle
                :cx="center"
                :cy="center"
                :r="r"
                fill="none"
                class="stroke-muted"
                :stroke-width="strokeWidth"
            />
            <!-- Arco de progreso -->
            <circle
                :cx="center"
                :cy="center"
                :r="r"
                fill="none"
                :class="color"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
                class="transition-all duration-700"
            />
        </svg>
        <div class="text-center">
            <div class="text-xl font-bold text-foreground">{{ value }}%</div>
            <div v-if="label" class="text-xs text-muted-foreground">{{ label }}</div>
        </div>
    </div>
</template>
