<script setup lang="ts">
import { Head, Link, router} from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';

const isModalOpen = ref(false);
const isFormModalOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const selectedCalendario = ref<Calendario | null>(null);
const searchQuery = ref('');
const selectedServicio = ref('');
const selectedTutor = ref('');
const horariosList = ref<Horario[]>([]);

const formData = ref({
    id_servicio: '',
    id_tutor: '',
    costo_base: '',
    cupos_MAX: '1',
    horarios: [] as number[],
});

interface Calendario {
    id: number;
    id_servicio: number;
    id_tutor: number;
    costo_base: number;
    cupos_MAX: number;
    cupos_actual: number;
    created_at: string;
    servicio?: Servicio;
    tutor?: Tutor;
    horarios?: Horario[];
}

interface Servicio {
    id: number;
    nombre: string;
    modalidad: string;
}

interface Tutor {
    id: number;
    id_usuario: number;
    nombre?: string;
}

interface Horario {
    id: number;
    dia_semana: string;
    hora_inicio: string;
    hora_fin: string;
    estado_disponibilidad: boolean;
}

interface Pagination<T> {
    data: T[];
    links: any[];
    current_page: number;
}

const props = defineProps<{
    calendarios: Pagination<Calendario>;
    servicios: Servicio[];
    tutores: Tutor[];
    horarios: Horario[];
}>();

// Sincronizar horarios con los props
horariosList.value = props.horarios;
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Calendarios',
        href: 'calendarios',
    },
];

// Modal para ver detalles
const openModal = (calendario: Calendario) => {
    selectedCalendario.value = calendario;
    isModalOpen.value = true;
};

/* const closeModal = () => {
    isModalOpen.value = false;
    selectedCalendario.value = null;
}; */

// Modal para editar
const openEditModal = (calendario: Calendario) => {
    formMode.value = 'edit';
    formData.value = {
        id_servicio: calendario.id_servicio.toString(),
        id_tutor: calendario.id_tutor.toString(),
        costo_base: calendario.costo_base.toString(),
        cupos_MAX: calendario.cupos_MAX.toString(),
        horarios: calendario.horarios?.map(h => h.id) || [],
    };
    selectedCalendario.value = calendario;
    isFormModalOpen.value = true;
};

// Eliminar calendario
const deleteCalendario = (id: number) => {
    if (window.confirm('¿Estás seguro de que deseas eliminar este calendario?')) {
        router.delete(route('calendarios.destroy', id));
    }
};
// Filtros
const applyFilters = () => {
    router.visit('/calendarios', {
        data: {
            search: searchQuery.value,
            id_servicio: selectedServicio.value,
            id_tutor: selectedTutor.value,
        },
        method: 'get',
        preserveScroll: true,
    });
};
const clearFilters = () => {
    searchQuery.value = '';
    selectedServicio.value = '';
    selectedTutor.value = '';
    applyFilters();
};
// Helpers
const getServicioNombre = (id: number, servicios: Servicio[]) => {
    return servicios.find(s => s.id === id)?.nombre || 'N/A';
};
const getTutorNombre = (id: number, tutores: Tutor[]) => {
    return tutores.find(t => t.id === id)?.nombre || 'Tutor ' + id;
};
/* const getHorarioTexto = (horario: Horario) => {
    return `${horario.dia_semana} ${horario.hora_inicio} - ${horario.hora_fin}`;
}; */

console.log('Props recibidas:', props.calendarios);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Calendarios" />
        <h1 class="sr-only">Calendarios</h1>
        <UserLayout>
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <h2 class="p-6 text-lg font-bold text-foreground mb-4">Filtrar Calendarios</h2>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4">
                    <!-- Input de búsqueda -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Buscar por servicio
                        </label>
                        <input v-model="searchQuery" type="text" placeholder="Nombre del servicio..."
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <!-- Select de servicio -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Filtrar por servicio
                        </label>
                        <select v-model="selectedServicio"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los servicios</option>
                            <option v-for="servicio in servicios" :key="servicio.id" :value="servicio.id">
                                {{ servicio.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Select de tutor -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Filtrar por tutor
                        </label>
                        <select v-model="selectedTutor"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Todos los tutores</option>
                            <option v-for="tutor in tutores" :key="tutor.id" :value="tutor.id">
                                {{ getTutorNombre(tutor.id, tutores) }}
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
                        <Link :href="route('calendarios.create')"
                            class="flex-1 px-4 py-2 cursor-pointer bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                            ➕ Crear
                        </Link>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-muted-foreground">
                        <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                            <tr>
                                <th class="px-6 py-4 font-bold">Servicio</th>
                                <th class="px-6 py-4 font-bold">Tutor</th>
                                <th class="px-6 py-4 font-bold">Costo Base</th>
                                <th class="px-6 py-4 font-bold">Cupos</th>
                                <th class="px-6 py-4 font-bold">Horarios</th>
                                <th class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-if="calendarios.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                    No se encontraron calendarios
                                </td>
                            </tr>
                            <tr v-for="calendario in calendarios.data" :key="calendario.id"
                                class="hover:bg-muted transition">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ getServicioNombre(calendario.id_servicio, servicios) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ getTutorNombre(calendario.id_tutor, tutores) }}
                                </td>
                                <td class="px-6 py-4">
                                    ${{ calendario.costo_base }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-sm bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded px-2 py-1">
                                        {{ calendario.cupos_actual }}/{{ calendario.cupos_MAX }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="calendario.horarios && calendario.horarios.length > 0" class="text-xs">
                                        {{ calendario.horarios.length }} horario(s)
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">Sin horarios</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Button class="cursor-pointer hover:bg-muted/50" @click="openModal(calendario)">
                                        Ver Detalle
                                    </Button>

                                    <button
                                        class="cursor-pointer bg-primary hover:bg-primary/80 text-primary-foreground px-4 py-2 rounded-lg transition"
                                        @click="openEditModal(calendario)">
                                        Editar
                                    </button>
                                    <button @click="deleteCalendario(calendario.id)"
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/60 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación -->
                <div v-if="calendarios.links && calendarios.links.length > 0"
                    class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
                    <Link v-for="link in calendarios.links" :key="link.url" :href="link.url || '#'" :class="[
                        'px-3 py-2 rounded-lg transition text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-card border border-border text-foreground hover:bg-muted',
                    ]" v-html="link.label" />
                </div>
            </div>
        </UserLayout>

        <!-- Modal para VER DETALLES -->
       <!--  <div v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">
                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">Detalle del Calendario</h3>
                    <button @click="closeModal"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-muted-foreground max-h-96 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Servicio:</span>
                            <p class="font-medium text-foreground">{{ getServicioNombre(selectedCalendario?.id_servicio
                                || 0, servicios) }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Tutor:</span>
                            <p class="font-medium text-foreground">{{ getTutorNombre(selectedCalendario?.id_tutor || 0,
                                tutores) }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Costo Base:</span>
                            <p class="font-medium text-foreground">${{ selectedCalendario?.costo_base.toFixed(2) }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Cupos:</span>
                            <p class="font-medium text-foreground">{{ selectedCalendario?.cupos_actual }} / {{
                                selectedCalendario?.cupos_MAX }}</p>
                        </div>
                    </div>

                    <div v-if="selectedCalendario?.horarios && selectedCalendario.horarios.length > 0">
                        <span class="text-xs uppercase font-bold text-muted-foreground">Horarios:</span>
                        <div class="mt-2 space-y-1">
                            <p v-for="horario in selectedCalendario.horarios" :key="horario.id"
                                class="text-sm text-foreground">
                                • {{ getHorarioTexto(horario) }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-border">
                        <p class="text-xs text-muted-foreground italic">ID: {{ selectedCalendario?.id }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
                </div>
            </div>
        </div> -->
        <!-- Modal para CREAR HORARIO dentro del modal de calendario -->
    </AppLayout>
</template>
