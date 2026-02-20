<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

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

interface Calendario {
    id: number;
    tipo_programacion: 'CITA_LIBRE' | 'PAQUETE_FIJO';
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    cupos_maximos: number;
    tutor?: Tutor;
    disponibilidades: Disponibilidad[];
}

interface Servicio {
    id: number;
    nombre: string;
    descripcion: string | null;
    modalidad: string;
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    id_tutor?: string;
    tipo_programacion?: string;
}

const props = defineProps<{
    servicio: Servicio;
    calendarios: Pagination<Calendario>;
    tutores: Tutor[];
    filters?: Filters;
}>();

const selectedTutor = ref(props.filters?.id_tutor ?? '');
const selectedTipo = ref(props.filters?.tipo_programacion ?? '');

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Catálogo', href: '/catalogo' },
    { title: props.servicio.nombre, href: `/catalogo/servicios/${props.servicio.id}` },
];

const aplicar = () => {
    router.get(
        route('catalogo.servicio.show', props.servicio.id),
        {
            id_tutor: selectedTutor.value,
            tipo_programacion: selectedTipo.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const limpiar = () => {
    selectedTutor.value = '';
    selectedTipo.value = '';
    aplicar();
};

const tutorLabel = (tutor?: Tutor) => tutor?.usuario?.name ?? 'Tutor sin nombre';

const disponibilidadTexto = (items: Disponibilidad[]) =>
    items.length
        ? items
              .map((item) => `${item.dia_semana} ${item.hora_apertura.slice(0, 5)}-${item.hora_cierre.slice(0, 5)}`)
              .join(' | ')
        : 'Sin disponibilidad';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Catálogo - ${servicio.nombre}`" />
        <UserLayout>
            <div class="space-y-5">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">{{ servicio.nombre }}</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ servicio.descripcion || 'Sin descripción.' }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <select v-model="selectedTutor" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todos los tutores</option>
                        <option v-for="tutor in tutores" :key="tutor.id" :value="String(tutor.id)">
                            {{ tutorLabel(tutor) }}
                        </option>
                    </select>

                    <select v-model="selectedTipo" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todos los tipos</option>
                        <option value="CITA_LIBRE">Cita única</option>
                        <option value="PAQUETE_FIJO">Paquete</option>
                    </select>

                    <button class="px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="aplicar">Filtrar</button>
                    <button class="px-3 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="limpiar">Limpiar</button>
                </div>

                <div class="space-y-3">
                    <article v-for="calendario in calendarios.data" :key="calendario.id" class="bg-card border border-border rounded-xl p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <p><strong>Tutor:</strong> {{ tutorLabel(calendario.tutor) }}</p>
                            <p><strong>Tipo:</strong> {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? 'Paquete' : 'Cita única' }}</p>
                            <p><strong>Duración:</strong> {{ calendario.duracion_sesion_minutos }} min</p>
                            <p><strong>Costo:</strong> ${{ calendario.costo_total }}</p>
                            <p><strong>Cupos:</strong> {{ calendario.cupos_maximos }}</p>
                            <p>
                                <strong>Sesiones:</strong>
                                {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? (calendario.numero_sesiones ?? 1) : 1 }}
                            </p>
                        </div>

                        <p class="text-sm text-muted-foreground mt-3">
                            <strong>Disponibilidad:</strong> {{ disponibilidadTexto(calendario.disponibilidades) }}
                        </p>

                        <div class="mt-4">
                            <Link
                                :href="route('catalogo.inscripcion.preview', calendario.id)"
                                class="inline-flex px-3 py-2 bg-primary text-primary-foreground rounded-lg"
                            >
                                Ver preinscripción y sesiones
                            </Link>
                        </div>
                    </article>
                </div>

                <div v-if="calendarios.links?.length" class="bg-card border border-border rounded-xl p-4 flex flex-wrap gap-2">
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
