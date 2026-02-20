<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';

const isModalOpen = ref(false);
const isFormModalOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const selectedServicio = ref<Servicio | null>(null);
const searchQuery = ref('');
const selectedCategoria = ref('');
const formData = ref({
    nombre: '',
    id_categoria: '',
    modalidad: 'VIRTUAL',
    descripcion: '',
  
});

interface Servicio {
    id: number;
    nombre: string;
    id_categoria: number;
    costo_base: number;
    modalidad: string;
    descripcion: string;
    duracion_semanas: number | null;
    duracion_horas: number | null;
    estado_activo: boolean;
    created_at: string;
}

interface Categoria {
    id: number;
    nombre: string;
}

interface Pagination<T> {
    data: T[];
    links: any[];
    current_page: number;
}

defineProps<{
    servicios: Pagination<Servicio>;
    categorias: Categoria[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Servicios',
        href: 'servicios',
    },
];

const page = usePage();
const user = page.props.auth.user;

// Modal para ver detalles
const openModal = (servicio: Servicio) => {
    selectedServicio.value = servicio;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedServicio.value = null;
};

// Modal para crear
const openCreateModal = () => {
    formMode.value = 'create';
    formData.value = {
        nombre: '',
        id_categoria: '',
        modalidad: 'VIRTUAL',
        descripcion: '',
    };
    isFormModalOpen.value = true;
};

// Modal para editar
const openEditModal = (servicio: Servicio) => {
    formMode.value = 'edit';
    formData.value = {
        nombre: servicio.nombre,
        id_categoria: servicio.id_categoria.toString(),
        
        modalidad: servicio.modalidad,
        descripcion: servicio.descripcion,
       
    };
    selectedServicio.value = servicio;
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    selectedServicio.value = null;
};

// Enviar formulario
const submitForm = () => {
    if (formMode.value === 'create') {
        router.post(route('servicios.store'), formData.value, {
            onSuccess: () => {
                closeFormModal();
            },
        });
    } else if (formMode.value === 'edit' && selectedServicio.value) {
        router.put(route('servicios.update', selectedServicio.value.id), formData.value, {
            onSuccess: () => {
                closeFormModal();
            },
        });
    }
};

// Eliminar servicio
const deleteServicio = (id: number) => {
    if (window.confirm('¿Estás seguro de que deseas eliminar este servicio?')) {
        router.delete(route('servicios.destroy', id));
    }
};

// Filtros
const applyFilters = () => {
    router.visit('/servicios', {
        data: {
            search: searchQuery.value,
            id_categoria: selectedCategoria.value,
        },
        method: 'get',
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategoria.value = '';
    applyFilters();
};

// Obtener nombre de categoría
const getCategoriaNombre = (id: number) => {
    const categoria = (page.props as any).categorias?.find((c: Categoria) => c.id === id);
    return categoria?.nombre || 'N/A';
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Servicios" />
        <h1 class="sr-only">Servicios</h1>

        <UserLayout>
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <h2 class="p-6 text-lg font-bold text-foreground mb-4">Filtrar Servicios</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4">
                    <!-- Input de búsqueda -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Buscar por nombre
                        </label>
                        <input v-model="searchQuery" type="text" placeholder="Escriba un nombre..."
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <!-- Select de categoría -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Filtrar por categoría
                        </label>
                        <select v-model="selectedCategoria"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todas las categorías</option>
                            <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                                {{ categoria.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters"
                            class="flex-1 cursor-pointer px-4 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 transition">
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
                                <th class="px-6 py-4 font-bold">Categoría</th>
                                <th class="px-6 py-4 font-bold">Modalidad</th>
                                <th class="px-6 py-4 font-bold">Estado</th>
                                <th class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-if="servicios.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                    No se encontraron servicios
                                </td>
                            </tr>
                            <tr v-for="servicio in servicios.data" :key="servicio.id" class="hover:bg-muted transition">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ servicio.nombre }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ getCategoriaNombre(servicio.id_categoria) }}
                                </td>
                               <!--  <td class="px-6 py-4">
                                    ${{ servicio.costo_base }}
                                </td> -->
                               <td class="px-6 py-4">
                                    <span :class="[
                                        'text-sm rounded px-2 py-1',
                                        servicio.modalidad === 'VIRTUAL' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                        servicio.modalidad === 'PRESENCIAL' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' :
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
                                    ]">
                                        {{ servicio.modalidad }}
                                    </span>
                                </td> 
                                <td class="px-6 py-4">
                                    <span v-if="servicio.estado_activo === true"
                                        class="text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded px-2 py-1">Activo</span>
                                    <span v-else
                                        class="text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded px-2 py-1">Inactivo</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Button class="cursor-pointer hover:bg-muted/50" @click="openModal(servicio)">
                                        Ver Detalle
                                    </Button>

                                    <button
                                        class="cursor-pointer bg-primary hover:bg-primary/80 text-primary-foreground px-4 py-2 rounded-lg transition"
                                        @click="openEditModal(servicio)">
                                        Editar
                                    </button>
                                    <button
                                        @click="deleteServicio(servicio.id)"
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/60 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="servicios.links && servicios.links.length > 0"
                    class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
                    <Link v-for="link in servicios.links" :key="link.url" :href="link.url || '#'" :class="[
                        'px-3 py-2 rounded-lg transition text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-card border border-border text-foreground hover:bg-muted',
                    ]" v-html="link.label" />
                </div>
            </div>
        </UserLayout>

        <!-- Modal para VER DETALLES -->
        <div v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">
                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">Detalle del Servicio</h3>
                    <button @click="closeModal"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-muted-foreground">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Nombre:</span>
                            <p class="font-medium text-foreground">{{ selectedServicio?.nombre }}</p>
                        </div>
                       <!--  <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Costo Base:</span>
                            <p class="font-medium text-foreground">${{ selectedServicio?.costo_base }}</p>
                        </div> -->
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Modalidad:</span>
                            <p class="font-medium text-foreground">{{ selectedServicio?.modalidad }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Categoría:</span>
                            <p class="font-medium text-foreground">{{ getCategoriaNombre(selectedServicio?.id_categoria || 0) }}</p>
                        </div>
                    </div>

                    <div v-if="selectedServicio?.descripcion">
                        <span class="text-xs uppercase font-bold text-muted-foreground">Descripción:</span>
                        <p class="font-medium text-foreground">{{ selectedServicio?.descripcion }}</p>
                    </div>

                    <!-- <div class="grid grid-cols-2 gap-4" v-if="selectedServicio?.duracion_semanas || selectedServicio?.duracion_horas">
                        <div v-if="selectedServicio?.duracion_semanas">
                            <span class="text-xs uppercase font-bold text-muted-foreground">Duración (Semanas):</span>
                            <p class="font-medium text-foreground">{{ selectedServicio?.duracion_semanas }}</p>
                        </div>
                        <div v-if="selectedServicio?.duracion_horas">
                            <span class="text-xs uppercase font-bold text-muted-foreground">Duración (Horas):</span>
                            <p class="font-medium text-foreground">{{ selectedServicio?.duracion_horas }}</p>
                        </div>
                    </div> -->

                    <div class="pt-4 border-t border-border">
                        <p class="text-xs text-muted-foreground italic">ID: {{ selectedServicio?.id }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal para CREATE/EDIT -->
        <div v-if="isFormModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">
                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">{{ formMode === 'create' ? 'Crear Servicio' : 'Editar Servicio' }}</h3>
                    <button @click="closeFormModal"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Nombre del Servicio *
                            </label>
                            <input v-model="formData.nombre" type="text" placeholder="Ingrese el nombre..."
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Categoría *
                            </label>
                            <select v-model="formData.id_categoria"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                                <option value="">Seleccione una categoría</option>
                                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                                    {{ categoria.nombre }}
                                </option>
                            </select>
                        </div>

                      <!--   <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Costo Base *
                            </label>
                            <input v-model="formData.costo_base" type="number" placeholder="0.00" step="0.01" min="0"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div> -->

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Modalidad *
                            </label>
                            <select v-model="formData.modalidad"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                                <option value="VIRTUAL">Virtual</option>
                                <option value="PRESENCIAL">Presencial</option>
                                <option value="HIBRIDO">Híbrido</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Descripción
                            </label>
                            <textarea v-model="formData.descripcion" placeholder="Ingrese una descripción..."
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                rows="3"></textarea>
                        </div>
                        <div class="flex gap-2 justify-end pt-4">
                            <button type="button" @click="closeFormModal"
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
    </AppLayout>
</template>
