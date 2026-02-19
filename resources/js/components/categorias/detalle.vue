<script setup lang="ts">
defineProps<{
    show: boolean;
    categoria: any | null;
    categorias: any| null;
}>();
defineEmits(['close']);
</script>
<template>
<div v-if="show && categoria"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">

                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">Detalle de la Categoría   </h3>
                    <button @click="$emit('close')"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-muted-foreground">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Nombre:</span>
                            <p class="font-medium text-foreground">{{ categoria?.nombre }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Categoria Padre:</span>
                           <!--  <p class="font-medium text-foreground">{{ categoria?.id_categoria_padre }}</p> -->
                               
                            <div v-for ="cat in categorias" :key="cat.id">
                                <p v-if="cat.id === categoria?.id_categoria_padre" class="font-medium text-foreground">{{ cat.nombre }}</p>
                                 
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-border">
                        <p class="text-xs text-muted-foreground italic">ID de Categoría: {{ categoria?.id }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
                    <button @click="$emit('close')"
                        class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
                </div>
            </div>
        </div>
       </template> 
