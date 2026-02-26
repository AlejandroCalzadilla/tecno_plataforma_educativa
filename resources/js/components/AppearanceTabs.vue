<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    Monitor, 
    Moon, 
    Sun, 
    Palette, 
    ChevronDown, 
    Check 
} from 'lucide-vue-next';
import { Appearance, useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => (isOpen.value = !isOpen.value);

const closeDropdown = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => window.addEventListener('click', closeDropdown));
onUnmounted(() => window.removeEventListener('click', closeDropdown));

const modes = [
    { value: 'light', Icon: Sun, label: 'Claro' },
    { value: 'dark', Icon: Moon, label: 'Oscuro' },
    { value: 'system', Icon: Monitor, label: 'Sistema' },
] as const;

const colorThemes = [
    { value: 'blue', label: 'Azul', color: 'bg-blue-500' },
    { value: 'green', label: 'Verde', color: 'bg-green-500' },
    { value: 'purple', label: 'Morado', color: 'bg-purple-500' },
    { value: 'kids', label: 'Kids', color: 'bg-pink-500' },
] as const;

const handleSelect = (value: string) => {
    updateAppearance(value as Appearance);
    isOpen.value = false;
};

// Encontrar la etiqueta actual para mostrarla en el botón principal
const currentLabel = () => {
    const allOptions = [...modes, ...colorThemes];
    return allOptions.find(opt => opt.value === appearance.value)?.label || 'Apariencia';
};
</script>

<template>
    <div class="relative inline-block text-left" ref="dropdownRef">
        <!-- Botón del Dropdown -->
        <button
            @click="toggleDropdown"
            type="button"
            class="inline-flex items-center justify-between gap-x-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 shadow-sm ring-1 ring-inset ring-neutral-300 hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-200 dark:ring-neutral-700 dark:hover:bg-neutral-700 transition-all"
        >
            <div class="flex items-center gap-2">
                <Palette class="h-4 w-4 text-primary" />
                <span>{{ currentLabel() }}</span>
            </div>
            <ChevronDown 
                class="h-4 w-4 text-neutral-400 transition-transform duration-200" 
                :class="{ 'rotate-180': isOpen }" 
            />
        </button>

        <!-- Menú Desplegable -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 z-50 mt-2 w-56 origin-top-right divide-y divide-neutral-100 rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:divide-neutral-700 dark:bg-neutral-800 dark:ring-neutral-700"
            >
                <!-- Sección de Modos -->
                <div class="p-2">
                    <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-neutral-400">Modo</p>
                    <button
                        v-for="{ value, Icon, label } in modes"
                        :key="value"
                        @click="handleSelect(value)"
                        class="group flex w-full items-center justify-between rounded-md px-3 py-2 text-sm transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-700"
                        :class="appearance === value ? 'text-primary font-bold' : 'text-neutral-600 dark:text-neutral-400'"
                    >
                        <div class="flex items-center gap-3">
                            <component :is="Icon" class="h-4 w-4" />
                            <span>{{ label }}</span>
                        </div>
                        <Check v-if="appearance === value" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Sección de Paletas -->
                <div class="p-2">
                    <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-neutral-400">Paleta de Colores</p>
                    <button
                        v-for="theme in colorThemes"
                        :key="theme.value"
                        @click="handleSelect(theme.value)"
                        class="group flex w-full items-center justify-between rounded-md px-3 py-2 text-sm transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-700"
                        :class="appearance === theme.value ? 'text-primary font-bold' : 'text-neutral-600 dark:text-neutral-400'"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="[theme.color, 'h-3 w-3 rounded-full shadow-sm']" />
                            <span>{{ theme.label }}</span>
                        </div>
                        <Check v-if="appearance === theme.value" class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>