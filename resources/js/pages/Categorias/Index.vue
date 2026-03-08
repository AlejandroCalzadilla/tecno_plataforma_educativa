<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';
import CategoriaDetailModal from '@/components/categorias/detalle.vue';
import CategoriaDCreateEditModal from '@/components/categorias/crear_edit.vue';
/* type Props = {
    mustVerifyEmail: boolean;
    status?: string;
}; */
const isModalOpen = ref(false);
const isFormModalOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const selectedCategoria = ref<Categoria | null>(null);
const searchQuery = ref('');
const selectedCategoriaPadre = ref('');
const formData = ref({
    nombre: '',
    id_categoria_padre: '',
});
// Función para abrir el modal con un usuario
const openModalDetalle = (categoria: Categoria) => {
    selectedCategoria.value = categoria;
    isModalOpen.value = true;
};
// Función para abrir modal de crear
const openCreateModal = () => {
    console.log('Abriendo modal de creación');
    formMode.value = 'create';
    formData.value = {
        nombre: '',
        id_categoria_padre: '',
    };
    selectedCategoria.value = null;
    isFormModalOpen.value = true;
};

// Función para abrir modal de editar
const openEditModal = (categoria: Categoria) => {
    formMode.value = 'edit';
    formData.value = {
        nombre: categoria.nombre,
        id_categoria_padre: categoria.id_categoria_padre?.toString() || '',
    };
    selectedCategoria.value = categoria;
    isFormModalOpen.value = true;
};

// Función para cerrar modal del formulario
const closeFormModal = () => {
    isFormModalOpen.value = false;
    selectedCategoria.value = null;
};
//defineProps<Props>();
interface Categoria {
    id: number;
    nombre: string;
    id_categoria_padre: number | null;
    created_at: string;
    estado: boolean;
    // Otros campos que puedas tener en tu modelo de usuario

}
interface Pagination<T> {
    data: T[];
    links: any[];
    current_page: number;
    // ... otros campos opcionales
}

// Recibimos los usuarios desde el controlador
defineProps<{
    categoriasp: Pagination<Categoria>;
    categorias: Categoria[];    
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Categorías',
        href: 'categorias',
    },
];

const page = usePage();
const applyFilters = () => {
    router.visit(route('categorias.index'), {
        data: {
            search: searchQuery.value,
            id_categoria_padre: selectedCategoriaPadre.value,
        },
        method: 'get',
        preserveScroll: true,
    });
};
const clearFilters = () => {
    searchQuery.value = '';
    selectedCategoriaPadre.value = '';
    applyFilters();
};

// Función para eliminar una categoría
const deleteCategoria = (id: number) => {
    if (window.confirm('¿Estás seguro de que deseas eliminar esta categoría?')) {
        router.delete(route('categorias.destroy', id));
    }
};
</script>
<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Categorías" />
        <h1 class="sr-only">Categorías</h1>
        <UserLayout>
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <h2 class="p-6 text-lg font-bold text-foreground mb-4">Filtrar Categorías</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4">
                    <!-- Input de búsqueda -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Buscar por nombre 
                        </label>
                        <input v-model="searchQuery" type="text" placeholder="Escriba un nombre..."
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <!-- Select de rol -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Filtrar por categoria padre
                        </label>
                        <select v-model="selectedCategoriaPadre"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        
                            <option value="">Todas las categorías</option>
                            <option v-for="categoria in categoriasp.data" :key="categoria.id" :value="categoria.id">
                                {{ categoria.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters"
                            class="flex-1  cursor-pointer px-4 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 transition">
                            🔍 Buscar
                        </button>
                        <button @click="clearFilters"
                            class="flex-1 px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg font-medium hover:bg-secondary/60 transition">
                            ✕ Limpiar
                        </button>
                        <button @click="openCreateModal"
                            class="flex-1 px-4 py-2 cursor-pointer bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                            ➕ Crear
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-muted-foreground">
                        <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                            <tr>
                                <th class="px-6 py-4 font-bold">Nombre</th>
                                <th class="px-6 py-4 font-bold">Categoria Padre</th>
                                <th class="px-6 py-4 font-bold">Estado</th>
                                <th class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-if="categoriasp.data.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-muted-foreground">
                                    No se encontraron usuarios
                                </td>
                            </tr>
                            <tr v-for="user in categoriasp.data" :key="user.id" class="hover:bg-muted transition">

                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ user.nombre }}
                                </td>

                                <td class="px-6 py-4">  
                                    <div v-for="categoria in categorias" :key="categoria.id" >                    
                                        <span v-if="categoria.id === user.id_categoria_padre" class="text-sm" >
                                            {{ categoria.nombre }}
                                        </span>
                                    </div>
                                </td>                
                                <td class="px-6 py-4">
                                    <span v-if="user.estado === true"
                                        class="text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded px-2 py-1">Activo</span>
                                    <span v-else-if="user.estado === false"
                                        class="text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded px-2 py-1">Inactivo</span>
                                    <span v-else
                                        class="text-sm bg-gray-100 text-gray-800 dark:bg-gray-700/30 dark:text-gray-400 rounded px-2 py-1">Desconocido</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Button class="cursor-pointer hover:bg-muted/50" @click="openModalDetalle(user)">
                                        Ver Detalle
                                    </Button>
                                    <button
                                        class="cursor-pointer bg-primary hover:bg-primary/80 text-primary-foreground px-4 py-2 rounded-lg transition"
                                        @click="openEditModal(user)">
                                        Editar
                                    </button>
                                    <button
                                        @click="deleteCategoria(user.id)"
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/60 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2">
                                        Activar/Desactivar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="categoriasp.links && categoriasp.links.length > 0"
                    class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
                    <Link v-for="link in categoriasp.links" :key="link.url" :href="link.url || '#'" :class="[
                        'px-3 py-2 rounded-lg transition text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-card border border-border text-foreground hover:bg-muted',
                    ]" v-html="link.label" />
                </div>
            </div>

        </UserLayout>
       <!-- modal de ver detalles-->
        <CategoriaDetailModal 
            :show="isModalOpen" 
            :categoria="selectedCategoria" 
            :categorias="categorias"
            @close="isModalOpen = false" 
        />
        
        <!-- Modal para CREATE/EDIT -->
        <CategoriaDCreateEditModal 
            :show="isFormModalOpen" 
            :categoria="selectedCategoria" 
            :categorias="categorias"
            :formMode="formMode"
            @close="closeFormModal"
        />  
         </AppLayout>
</template>
