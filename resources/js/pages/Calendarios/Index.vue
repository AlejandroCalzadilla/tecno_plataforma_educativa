<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

interface Calendario {
    id: number;
    tipo_programacion: 'CITA_LIBRE' | 'PAQUETE_FIJO';
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    cupos_maximos: number;
    servicio?: Servicio;
    tutor?: Tutor;
    disponibilidades?: Disponibilidad[];
}

interface Servicio {
    id: number;
    nombre: string;
}

interface Tutor {
    id: number;
    usuario?: {
        id: number;
        name: string;
    };
}

interface Disponibilidad {
    id: number;
    dia_semana: string;
    hora_apertura: string;
    hora_cierre: string;
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    search?: string;
    id_servicio?: string;
    id_tutor?: string;
    tipo_programacion?: string;
}

const props = defineProps<{
    calendarios: Pagination<Calendario>;
    servicios: Servicio[];
    tutores: Tutor[];
    filters?: Filters;
}>();

const searchQuery = ref(props.filters?.search ?? '');
const selectedServicio = ref(props.filters?.id_servicio ?? '');
const selectedTutor = ref(props.filters?.id_tutor ?? '');
const selectedTipoProgramacion = ref(props.filters?.tipo_programacion ?? '');

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Calendarios', href: '/calendarios' }];

const deleteCalendario = (id: number) => {
    if (window.confirm('¿Eliminar este calendario?')) {
        router.delete(route('calendarios.destroy', id));
    }
};

const applyFilters = () => {
    router.get(
        route('calendarios.index'),
        {
            search: searchQuery.value,
            id_servicio: selectedServicio.value,
            id_tutor: selectedTutor.value,
            tipo_programacion: selectedTipoProgramacion.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedServicio.value = '';
    selectedTutor.value = '';
    selectedTipoProgramacion.value = '';
    applyFilters();
};

const tutorLabel = (tutor: Tutor | undefined) => tutor?.usuario?.name ?? 'Sin nombre';

const disponibilidadTexto = (items: Disponibilidad[] = []) => {
    if (!items.length) return 'Sin disponibilidad';

    return items
        .map((item) => `${item.dia_semana} ${item.hora_apertura.substring(0, 5)}-${item.hora_cierre.substring(0, 5)}`)
        .join(' | ');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Calendarios" />

        <UserLayout>
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h1 class="text-lg font-bold text-foreground">Calendarios</h1>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 p-6">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Buscar servicio"
                        class="px-4 py-2 border border-border rounded-lg bg-card text-foreground"
                    />

                    <select v-model="selectedServicio" class="px-4 py-2 border border-border rounded-lg bg-card text-foreground">
                        <option value="">Todos los servicios</option>
                        <option v-for="servicio in servicios" :key="servicio.id" :value="String(servicio.id)">
                            {{ servicio.nombre }}
                        </option>
                    </select>

                    <select v-model="selectedTutor" class="px-4 py-2 border border-border rounded-lg bg-card text-foreground">
                        <option value="">Todos los tutores</option>
                        <option v-for="tutor in tutores" :key="tutor.id" :value="String(tutor.id)">
                            {{ tutorLabel(tutor) }}
                        </option>
                    </select>

                    <select v-model="selectedTipoProgramacion" class="px-4 py-2 border border-border rounded-lg bg-card text-foreground">
                        <option value="">Todos los tipos</option>
                        <option value="CITA_LIBRE">Cita libre</option>
                        <option value="PAQUETE_FIJO">Paquete fijo</option>
                    </select>

                    <div class="flex gap-2">
                        <button @click="applyFilters" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg">Buscar</button>
                        <button @click="clearFilters" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Limpiar</button>
                    </div>
                </div>

                <div class="px-6 pb-4">
                    <Link :href="route('calendarios.create')" class="inline-flex px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Crear calendario
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-muted-foreground">
                        <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                            <tr>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3">Tutor</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Duración</th>
                                <th class="px-4 py-3">Costo</th>
                                <th class="px-4 py-3">Cupos</th>
                                <!-- <th class="px-4 py-3">Disponibilidad</th> -->
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="calendarios.data.length === 0">
                                <td colspan="8" class="px-4 py-6 text-center">No hay calendarios registrados.</td>
                            </tr>

                            <tr v-for="calendario in calendarios.data" :key="calendario.id" class="border-b border-border">
                                <td class="px-4 py-3">{{ calendario.servicio?.nombre ?? '-' }}</td>
                                <td class="px-4 py-3">{{ tutorLabel(calendario.tutor) }}</td>
                                <td class="px-4 py-3">
                                    {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? 'Paquete fijo' : 'Cita libre' }}
                                    <span v-if="calendario.tipo_programacion === 'PAQUETE_FIJO' && calendario.numero_sesiones">
                                        ({{ calendario.numero_sesiones }} sesiones)
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ calendario.duracion_sesion_minutos }} min</td>
                                <td class="px-4 py-3">${{ calendario.costo_total }}</td>
                                <td class="px-4 py-3">{{ calendario.cupos_maximos }}</td>
                               <!--  <td class="px-4 py-3">{{ disponibilidadTexto(calendario.disponibilidades) }}</td> -->
                                <td class="px-4 py-3 text-right space-x-2">
                                    <Link :href="route('calendarios.edit', calendario.id)" class="inline-flex px-3 py-1.5 rounded bg-primary text-primary-foreground">
                                        Editar
                                    </Link>
                                    <button @click="deleteCalendario(calendario.id)" class="inline-flex px-3 py-1.5 rounded bg-destructive text-destructive-foreground">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="calendarios.links?.length" class="px-6 py-4 border-t border-border bg-muted flex flex-wrap gap-2">
                    <Link
                        v-for="link in calendarios.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-2 rounded border border-border"
                        :class="{ 'bg-primary text-primary-foreground': link.active, 'pointer-events-none opacity-50': !link.url }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
