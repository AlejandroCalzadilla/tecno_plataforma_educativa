<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Calendar,
    Clock,
    Video,
    ListTodo,
    ArrowLeft,
    FileText,
    CheckCircle2,
    XCircle,
    AlertCircle
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

// 1. Interfaces para el tipado estricto
interface UserData {
    id: number;
    name: string;
    email: string;
}

interface Informe {
    id: number;
    titulo: string;
    temas_vistos: string;
    contenido: string;
    created_at: string;
    progreso: number;
    asistencia: boolean;
    desempenio: string;
    tareas_asignadas: string;
}

interface SessionDetail {
    id: number;
    fecha: string;
    hora_inicio: string;
    hora_fin: string;
    status: 'PENDIENTE' | 'PRESENTE' | 'AUSENTE' | 'JUSTIFICADO' | 'CANCELADA';
    link_virtual?: string;
    notas?: string;
    servicio: {
        nombre: string;
        categoria_nivel: { nombre: string };
    };
    tutor: {
        id: number;
        user: UserData;
    };
    alumno: {
        id: number;
        user: UserData;
    };
    asistencias?: {
        id: number;
        estado_asistencia: 'PENDIENTE' | 'PRESENTE' | 'AUSENTE' | 'TARDANZA' | 'JUSTIFICADO';
        observaciones?: string;
        alumno: {
            id: number;
            user: UserData;
        };
    }[];
    es_tutor?: boolean;
    es_alumno?: boolean;
    modo_activo?: 'tutor' | 'alumno';
    informes?: Informe[];
}

// 2. Definición de Props
const props = defineProps<{
    session: SessionDetail;
}>();

// 3. Computed Properties con lógica de Roles
const canEditSession = computed(() => {
    return !!props.session.es_tutor;
});

const canCancelSession = computed(() => {
    return !!props.session.es_tutor || !!props.session.es_alumno;
});

// 4. Utilidades
const formatDate = (dateString: string) => {
    if (!dateString) return 'Fecha no disponible';
    // 1. Limpiamos posibles espacios en blanco o caracteres extraños
    const cleanDate = dateString.trim();
    const parts = cleanDate.split('-');
    if (parts.length === 3) {
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]) - 1; // Los meses en JS van de 0 a 11
        const day = parseInt(parts[2]);
        const date = new Date(year, month, day);
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
};
const joinSession = () => {
    if (props.session.link_virtual) {
        window.open(props.session.link_virtual, '_blank');
    }
};

const cancelSession = () => {
    if (confirm('¿Estás seguro de que quieres cancelar esta sesión?')) {
        alert('Llamada al backend para cancelar...');
    }
};

const estadosAsistencia = ['PENDIENTE', 'PRESENTE', 'AUSENTE', 'TARDANZA', 'JUSTIFICADO'] as const;

const linkForm = useForm({
    link_sesion: props.session.link_virtual ?? '',
});

const actualizarLinkSesion = () => {
    linkForm.patch(route('sesiones.link.update', props.session.id), {
        preserveScroll: true,
    });
};

const actualizarAsistencia = (asistenciaId: number, estadoAsistencia: string, observaciones?: string) => {
    router.patch(route('sesiones.asistencias.update', [props.session.id, asistenciaId]), {
        estado_asistencia: estadoAsistencia,
        observaciones: observaciones ?? null,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Calendario', href: route('sesiones.index') }, { title: 'Detalles' }]">

        <Head :title="`Sesión: ${session.servicio.nombre}`" />

        <div class="py-12 bg-background  t ">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="mb-6">
                    <Link :href="route('sesiones.index')"
                        class="inline-flex items-center text-sm text-muted-foreground hover:text-primary transition-colors">
                        <ArrowLeft class="w-4 h-4 mr-1" />
                        Volver al calendario
                    </Link>
                </div>

                <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-border bg-muted/30">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-primary font-semibold text-sm uppercase tracking-wider">
                                    {{ session.servicio.categoria_nivel.nombre }}
                                </span>
                                <h1 class="text-3xl font-bold text-foreground mt-1">{{ session.servicio.nombre }}</h1>
                                <div v-if="session.modo_activo" class="mt-2">
                                    <span class="inline-flex items-center rounded-full border border-border bg-muted px-2.5 py-1 text-xs font-medium capitalize">
                                        Modo actual: {{ session.modo_activo }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span :class="[
                                    'inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold border',
                                    session.status === 'PENDIENTE' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                        session.status === 'PRESENTE' ? 'bg-green-50 text-green-700 border-green-200' :
                                            session.status === 'AUSENTE' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' :
                                                session.status === 'JUSTIFICADO' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                                    'bg-destructive/10 text-destructive border-destructive/20'
                                ]">
                                    <CheckCircle2 v-if="session.status === 'PRESENTE'" class="w-4 h-4 mr-1.5" />
                                    <AlertCircle v-else-if="session.status === 'PENDIENTE'" class="w-4 h-4 mr-1.5" />
                                    <XCircle v-else class="w-4 h-4 mr-1.5" />
                                    {{ session.status.toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                            <div class="lg:col-span-2 space-y-8">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                    <div class="flex items-start gap-4">
                                        <div class="p-3 bg-primary/10 rounded-lg">
                                            <Calendar class="w-6 h-6 text-primary" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-muted-foreground uppercase">Fecha</h4>
                                            <p class="text-lg font-medium text-foreground capitalize">{{
                                                formatDate(session.fecha) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="p-3 bg-primary/10 rounded-lg">
                                            <Clock class="w-6 h-6 text-primary" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-muted-foreground uppercase">Horario</h4>
                                            <p class="text-lg font-medium text-foreground">{{ session.hora_inicio }} —
                                                {{ session.hora_fin }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="session.link_virtual"
                                    class="bg-primary/5 border border-primary/20 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="p-4 bg-primary text-primary-foreground rounded-full shadow-lg shadow-primary/20">
                                            <Video class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold">Clase Virtual lista</h4>
                                            <p class="text-sm text-muted-foreground">Accede a través del botón o el
                                                enlace directo.</p>
                                        </div>
                                    </div>
                                    <button @click="joinSession"
                                        class="w-full sm:w-auto px-8 py-3 bg-primary text-primary-foreground font-bold rounded-xl hover:bg-primary/90 transition-all active:scale-95 shadow-lg shadow-primary/20">
                                        Unirme ahora
                                    </button>
                                </div>

                                <div v-if="session.notas">
                                    <h4 class="flex items-center text-lg font-bold mb-4">
                                        <FileText class="w-5 h-5 mr-2 text-primary" />
                                        Notas de la sesión
                                    </h4>
                                    <div class="bg-muted p-6 rounded-xl text-foreground leading-relaxed">
                                        {{ session.notas }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div class="bg-muted/30 p-6 rounded-2xl border border-border">
                                    <h4 class="text-sm font-bold text-muted-foreground uppercase mb-6">Participantes
                                    </h4>
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center font-bold text-primary">
                                                T
                                            </div>
                                            <div>
                                                <p class="text-xs text-muted-foreground">Tutor</p>
                                                <p class="font-bold">{{ session.tutor.user.name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-secondary/40 rounded-full flex items-center justify-center font-bold text-secondary-foreground">
                                                A
                                            </div>
                                            <div>
                                                <p class="text-xs text-muted-foreground">Alumno</p>
                                                <p class="font-bold">{{ session.alumno.user.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="canCancelSession || canEditSession" class="flex flex-col gap-3">
                                    <form v-if="canEditSession" class="space-y-3" @submit.prevent="actualizarLinkSesion">
                                        <label class="block text-xs font-bold text-muted-foreground uppercase">Link de la sesión</label>
                                        <input
                                            v-model="linkForm.link_sesion"
                                            type="url"
                                            placeholder="https://..."
                                            class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                                        />
                                        <p v-if="linkForm.errors.link_sesion" class="text-sm text-destructive">
                                            {{ linkForm.errors.link_sesion }}
                                        </p>
                                        <button
                                            type="submit"
                                            :disabled="linkForm.processing"
                                            class="w-full py-3 bg-card border border-border hover:bg-muted font-bold rounded-xl transition-colors disabled:opacity-50"
                                        >
                                            {{ linkForm.processing ? 'Guardando...' : 'Actualizar link' }}
                                        </button>
                                    </form>
                                   <!--  <button v-if="canCancelSession && session.status === 'PENDIENTE'"
                                        @click="cancelSession"
                                        class="w-full py-3 text-destructive hover:bg-destructive/10 font-bold rounded-xl transition-colors">
                                        Cancelar Sesión
                                    </button> -->
                                </div>

                            </div>
                        </div>

                        <div v-if="session.informes?.length" class="mt-12 pt-12 border-t border-border">
                            <div class="flex items-center gap-2 mb-8">
                                <FileText class="w-6 h-6 text-primary" />
                                <h3 class="text-xl font-bold text-foreground">Informes de Clase</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="informe in session.informes" :key="informe.id"
                                    class="group p-6 bg-card border border-border rounded-2xl shadow-sm hover:shadow-md transition-all">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h5
                                                class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">
                                                Temas Vistos</h5>
                                            <p class="font-bold text-lg text-foreground leading-tight">Resumen de la
                                                Sesión</p>
                                        </div>
                                        <span
                                            class="text-xs font-medium px-2 py-1 bg-muted rounded-md text-muted-foreground">
                                            {{ formatDate(informe.created_at) }}
                                        </span>
                                    </div>
                                    <div class="bg-muted/40 p-4 rounded-xl mb-4 border border-border/50">
                                        <p class="text-sm text-foreground/80 leading-relaxed italic">
                                            "{{ informe.temas_vistos }}"
                                        </p>
                                    </div>

                                    <div v-if="informe.tareas_asignadas" class="mb-6">
                                        <h6 class="text-[10px] font-bold text-primary uppercase mb-2 flex items-center">
                                            <ListTodo class="w-3 h-3 mr-1" /> Tareas Asignadas
                                        </h6>
                                        <p
                                            class="text-sm text-muted-foreground bg-primary/5 p-3 rounded-lg border border-primary/10">
                                            {{ informe.tareas_asignadas }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between pt-4 border-t border-border/50">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[10px] font-bold text-muted-foreground uppercase">Desempeño:</span>
                                            <span :class="[
                                                'text-xs font-bold px-2 py-0.5 rounded-full',
                                                informe.desempenio === 'EXCELENTE' ? 'bg-green-100 text-green-700' :
                                                    informe.desempenio === 'BAJO' ? 'bg-yellow-100 text-yellow-700' :
                                                        'bg-blue-100 text-blue-700'
                                            ]">
                                                {{ informe.desempenio }}
                                            </span>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="canEditSession && session.asistencias?.length" class="mt-12 pt-12 border-t border-border">
                            <div class="flex items-center gap-2 mb-8">
                                <CheckCircle2 class="w-6 h-6 text-primary" />
                                <h3 class="text-xl font-bold text-foreground">Control de Asistencia</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div
                                    v-for="asistencia in session.asistencias"
                                    :key="asistencia.id"
                                    class="p-6 bg-card border border-border rounded-2xl shadow-sm"
                                >
                                    <p class="text-base font-bold text-foreground mb-4">{{ asistencia.alumno.user.name }}</p>

                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-bold text-muted-foreground uppercase mb-2">Estado</label>
                                            <select
                                                :value="asistencia.estado_asistencia"
                                                class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                                                @change="(event) => actualizarAsistencia(asistencia.id, (event.target as HTMLSelectElement).value, asistencia.observaciones)"
                                            >
                                                <option v-for="estado in estadosAsistencia" :key="estado" :value="estado">
                                                    {{ estado }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-muted-foreground uppercase mb-2">Observaciones</label>
                                            <textarea
                                                :value="asistencia.observaciones ?? ''"
                                                class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                                                rows="3"
                                                placeholder="Registrar observaciones"
                                                @change="(event) => actualizarAsistencia(asistencia.id, asistencia.estado_asistencia, (event.target as HTMLTextAreaElement).value)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>