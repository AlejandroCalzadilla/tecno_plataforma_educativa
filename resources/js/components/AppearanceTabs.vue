<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
    
] as const;

const colorThemes = [
    { value: 'blue', label: 'Azul', color: 'bg-blue-500' },
    { value: 'green', label: 'Verde', color: 'bg-green-500' },
    { value: 'purple', label: 'Morado', color: 'bg-purple-500' },
    { value: 'kids', label: 'Kids', color: 'bg-pink-500' },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                appearance === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ label }}</span>
        </button>
    </div>

    <div>
            <h3 class="text-sm font-semibold text-foreground mb-2">Paleta de Colores</h3>
            <div class="flex gap-2">
                <button
                    v-for="theme in colorThemes"
                    :key="theme.value"
                    @click="updateAppearance(theme.value)"
                    :class="[
                        'flex items-center gap-2 px-3 py-2 rounded-md transition-all border-2',
                        appearance === theme.value
                            ? 'border-foreground'
                            : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600',
                    ]"
                >
                    <div :class="[theme.color, 'h-4 w-4 rounded']" />
                    <span class="text-sm">{{ theme.label }}</span>
                </button>
            </div>
        </div>
</template>
