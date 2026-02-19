<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { watch } from 'vue';
import { route } from 'ziggy-js';



interface Categoria {
    id?: number;
    nombre: string;
    id_categoria_padre: number | string | null;
}
const props = defineProps<{
    show: boolean;
    categoria: any | null;
    categorias: any| null;
    formMode: 'create' | 'edit';
    
}>();
const emit = defineEmits(['close']);

const form = useForm({
    nombre: '',
    id_categoria_padre: '' as string | number,
});

// Sincronizar el formulario cuando cambia la categoría (para editar)
watch(() => props.categoria, (newVal) => {
    if (newVal) {
        form.nombre = newVal.nombre;
        form.id_categoria_padre = newVal.id_categoria_padre || '';
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.formMode === 'create') {
        form.post(route('categorias.store'), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.put(route('categorias.update', props.categoria?.id), {
            onSuccess: () => emit('close'),
        });
    }
};
console.log('Props recibidas:', props.categorias);
</script>




<template>
     <div v-if="show "
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">

                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">{{ formMode === 'create' ? 'Crear Categoría' : 'Editar Categoría' }}</h3>
                    <button @click="$emit('close')"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Nombre de la Categoría
                            </label>
                            <input v-model="form.nombre" type="text" placeholder="Ingrese el nombre..."
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Categoría Padre
                            </label>
                            <select v-model="form.id_categoria_padre"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Sin categoría padre</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id"
                                    v-show="cat.id !== categoria?.id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-2 justify-end pt-4">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-4 py-2 cursor-pointer bg-primary text-primary-foreground rounded-lg text-sm font-bold hover:bg-primary/80 transition">
                                {{ formMode === 'create' ? 'Crear' : 'Actualizar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>   
</template>