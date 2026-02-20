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

interface Servicio {
    id: number;
    nombre: string;
}

interface Calendario {
    id: number;
    tipo_programacion: 'CITA_LIBRE' | 'PAQUETE_FIJO';
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    cupos_maximos: number;
    tutor?: Tutor;
    servicio?: Servicio;
}

interface InscripcionPreview {
    id_alumno: number | null;
    id_calendario: number;
    estado_academico: string;
    fecha_inscripcion: string;
    tipo_programacion: string;
    total_sesiones: number;
    costo_total: number;
    nota: string;
}

interface SesionPreview {
    numero_sesion: number;
    fecha_sesion: string;
    dia_semana: string;
    fecha_hora_inicio: string;
    fecha_hora_fin: string;
    estado_asistencia: string;
}

interface Params {
    id_alumno: string | number;
    fecha_inicio: string;
}

const props = defineProps<{
    calendario: Calendario;
    inscripcionPreview: InscripcionPreview;
    sesionesProgramadas: SesionPreview[];
    params: Params;
}>();

const idAlumno = ref(String(props.params.id_alumno ?? ''));
const fechaInicio = ref(props.params.fecha_inicio);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Catálogo', href: '/catalogo' },
    { title: props.calendario.servicio?.nombre ?? 'Servicio', href: `/catalogo/servicios/${props.calendario.servicio?.id}` },
    { title: 'Preinscripción', href: `/catalogo/calendarios/${props.calendario.id}/preinscripcion` },
];

const recalcular = () => {
    router.get(
        route('catalogo.inscripcion.preview', props.calendario.id),
        {
            id_alumno: idAlumno.value || undefined,
            fecha_inicio: fechaInicio.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const tutorLabel = () => props.calendario.tutor?.usuario?.name ?? 'Tutor sin nombre';

const irAPago = () => {
    router.get(route('catalogo.inscripcion.pago', props.calendario.id), {
        id_alumno: idAlumno.value || undefined,
        fecha_inicio: fechaInicio.value,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Preinscripción (preview)" />
        <UserLayout>
            <div class="space-y-5">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">Preinscripción (sin guardar)</h1>
                    <p class="text-sm text-muted-foreground mt-1">Se genera la inscripción y sesiones programadas en memoria para enviar luego a pasarela de pago.</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                   <!--  <div>
                        <label class="block text-sm mb-1">ID alumno (opcional)</label>
                        <input v-model="idAlumno" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div> -->
                    <div>
                        <label class="block text-sm mb-1">Fecha de inicio base</label>
                        <input v-model="fechaInicio" type="date" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="recalcular">Recalcular sesiones</button>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 text-sm space-y-1">
                    <p><strong>Servicio:</strong> {{ calendario.servicio?.nombre }}</p>
                    <p><strong>Tutor:</strong> {{ tutorLabel() }}</p>
                    <p><strong>Tipo:</strong> {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? 'Paquete' : 'Cita única' }}</p>
                    <p><strong>Costo:</strong> ${{ calendario.costo_total }}</p>
                    <p><strong>Estado inscripción:</strong> {{ inscripcionPreview.estado_academico }}</p>
                    <p class="text-muted-foreground">{{ inscripcionPreview.nota }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Sesiones programadas (preview)</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left">
                                    <th class="py-2 pr-4">#</th>
                                    <th class="py-2 pr-4">Fecha</th>
                                    <th class="py-2 pr-4">Día</th>
                                    <th class="py-2 pr-4">Inicio</th>
                                    <th class="py-2 pr-4">Fin</th>
                                    <th class="py-2 pr-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sesion in sesionesProgramadas" :key="sesion.numero_sesion" class="border-b border-border/60">
                                    <td class="py-2 pr-4">{{ sesion.numero_sesion }}</td>
                                    <td class="py-2 pr-4">{{ sesion.fecha_sesion }}</td>
                                    <td class="py-2 pr-4">{{ sesion.dia_semana }}</td>
                                    <td class="py-2 pr-4">{{ sesion.fecha_hora_inicio }}</td>
                                    <td class="py-2 pr-4">{{ sesion.fecha_hora_fin }}</td>
                                    <td class="py-2 pr-4">{{ sesion.estado_asistencia }}</td>
                                </tr>
                                <tr v-if="sesionesProgramadas.length === 0">
                                    <td colspan="6" class="py-4 text-center text-muted-foreground">
                                        No fue posible armar sesiones (revisa disponibilidad del calendario).
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg"
                        @click="irAPago"
                    >
                        Inscribirme y pasar al pago
                    </button>
                    <Link
                        :href="route('catalogo.servicio.show', calendario.servicio?.id)"
                        class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg"
                    >
                        Volver al servicio
                    </Link>
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
