<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

interface RangoCitaLibre {
    inicio: string;
    fin: string;
}

interface CalendarDay {
    key: string;
    date: Date;
    dateString: string;
    dayNumber: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    isAvailable: boolean;
    isInRange: boolean;
}

const props = defineProps<{
    calendario: Calendario;
    inscripcionPreview: InscripcionPreview;
    sesionesProgramadas: SesionPreview[];
    params: Params;
    fechasDisponiblesCitaLibre: string[];
    rangoCitaLibre: RangoCitaLibre;
}>();

const idAlumno = ref(String(props.params.id_alumno ?? ''));
const fechaInicio = ref(props.params.fecha_inicio);
const monthCursor = ref(new Date(`${props.rangoCitaLibre.inicio}T00:00:00`));

const availableDateSet = computed(() => new Set(props.fechasDisponiblesCitaLibre));
const isCitaLibre = computed(() => props.calendario.tipo_programacion === 'CITA_LIBRE');

const parseLocalDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const formatDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const minDate = computed(() => parseLocalDate(props.rangoCitaLibre.inicio));
const maxDate = computed(() => parseLocalDate(props.rangoCitaLibre.fin));

const canGoPrevMonth = computed(() => {
    const prevMonth = new Date(monthCursor.value.getFullYear(), monthCursor.value.getMonth() - 1, 1);
    return prevMonth >= new Date(minDate.value.getFullYear(), minDate.value.getMonth(), 1);
});

const canGoNextMonth = computed(() => {
    const nextMonth = new Date(monthCursor.value.getFullYear(), monthCursor.value.getMonth() + 1, 1);
    return nextMonth <= new Date(maxDate.value.getFullYear(), maxDate.value.getMonth(), 1);
});

const currentMonthLabel = computed(() =>
    monthCursor.value.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
);

const calendarDays = computed((): CalendarDay[] => {
    const year = monthCursor.value.getFullYear();
    const month = monthCursor.value.getMonth();
    const firstDayOfMonth = new Date(year, month, 1);
    const startDate = new Date(firstDayOfMonth);
    startDate.setDate(startDate.getDate() - firstDayOfMonth.getDay());

    const days: CalendarDay[] = [];
    const cursor = new Date(startDate);
    const todayString = formatDate(new Date());

    while (days.length < 42) {
        const dateString = formatDate(cursor);
        const inRange = cursor >= minDate.value && cursor <= maxDate.value;
        const isAvailable = inRange && availableDateSet.value.has(dateString);

        days.push({
            key: dateString,
            date: new Date(cursor),
            dateString,
            dayNumber: cursor.getDate(),
            isCurrentMonth: cursor.getMonth() === month,
            isToday: dateString === todayString,
            isAvailable,
            isInRange: inRange,
        });

        cursor.setDate(cursor.getDate() + 1);
    }

    return days;
});

const selectedDateLabel = computed(() => {
    if (!fechaInicio.value) return 'Sin fecha seleccionada';
    return parseLocalDate(fechaInicio.value).toLocaleDateString('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const previousMonth = () => {
    if (!canGoPrevMonth.value) return;
    monthCursor.value = new Date(monthCursor.value.getFullYear(), monthCursor.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    if (!canGoNextMonth.value) return;
    monthCursor.value = new Date(monthCursor.value.getFullYear(), monthCursor.value.getMonth() + 1, 1);
};

const seleccionarFechaCitaLibre = (day: CalendarDay) => {
    if (!day.isAvailable) return;
    fechaInicio.value = day.dateString;
};

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
                    <p class="text-sm text-muted-foreground mt-1">
                        Vista previa de la inscripción. Para paquetes se usarán sesiones ya existentes; para cita libre se creará una nueva sesión.
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!--  <div>
                        <label class="block text-sm mb-1">ID alumno (opcional)</label>
                        <input v-model="idAlumno" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div> -->
                    <div v-if="!isCitaLibre">
                        <label class="block text-sm mb-1">Fecha de inicio base</label>
                        <input v-model="fechaInicio" type="date" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="recalcular">Recalcular sesiones</button>
                    </div>
                </div>

                <div v-if="isCitaLibre" class="bg-card border border-border rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">Selecciona tu fecha de cita</h2>
                            <p class="text-sm text-muted-foreground">
                                Solo puedes reservar entre {{ rangoCitaLibre.inicio }} y {{ rangoCitaLibre.fin }}.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                class="px-3 py-1 border border-border rounded-lg disabled:opacity-50"
                                :disabled="!canGoPrevMonth"
                                @click="previousMonth"
                            >
                                ◀
                            </button>
                            <button
                                class="px-3 py-1 border border-border rounded-lg disabled:opacity-50"
                                :disabled="!canGoNextMonth"
                                @click="nextMonth"
                            >
                                ▶
                            </button>
                        </div>
                    </div>

                    <p class="text-sm font-medium capitalize">{{ currentMonthLabel }}</p>

                    <div class="grid grid-cols-7 gap-px bg-border border border-border rounded-lg overflow-hidden">
                        <div
                            v-for="d in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']"
                            :key="d"
                            class="bg-muted p-2 text-center text-xs font-semibold text-muted-foreground"
                        >
                            {{ d }}
                        </div>

                        <button
                            v-for="day in calendarDays"
                            :key="day.key"
                            type="button"
                            class="min-h-[70px] p-2 text-left transition-colors"
                            :class="[
                                day.isCurrentMonth ? 'bg-card' : 'bg-muted/20 text-muted-foreground',
                                day.isAvailable ? 'hover:bg-muted cursor-pointer' : 'cursor-not-allowed opacity-60',
                                fechaInicio === day.dateString ? 'ring-2 ring-primary ring-inset' : '',
                            ]"
                            :disabled="!day.isAvailable"
                            @click="seleccionarFechaCitaLibre(day)"
                        >
                            <span
                                class="text-sm font-medium"
                                :class="day.isToday ? 'bg-primary text-primary-foreground px-2 py-0.5 rounded-full' : ''"
                            >
                                {{ day.dayNumber }}
                            </span>
                            <p class="text-[11px] mt-2" :class="day.isAvailable ? 'text-primary font-medium' : 'text-muted-foreground'">
                                {{ day.isAvailable ? 'Libre' : (day.isInRange ? 'No disponible' : 'Fuera de rango') }}
                            </p>
                        </button>
                    </div>

                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            <strong>Fecha elegida:</strong> <span class="capitalize">{{ selectedDateLabel }}</span>
                        </p>
                        <button class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" @click="recalcular">
                            Confirmar fecha y recalcular
                        </button>
                    </div>

                    <p v-if="fechasDisponiblesCitaLibre.length === 0" class="text-sm text-destructive">
                        No hay fechas libres para este calendario dentro de los próximos 30 días.
                    </p>
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
