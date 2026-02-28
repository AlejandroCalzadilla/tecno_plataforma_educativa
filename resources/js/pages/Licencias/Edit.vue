<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Sesion {
    id: number;
    fecha_sesion: string;
    numero_sesion: number;
}

interface Licencia {
    id_licencia: number;
    id_asistencia: number;
    motivo: string;
    evidencia_url?: string | null;
    estado_aprobacion: 'PENDIENTE' | 'APROBADA' | 'RECHAZADA';
    observacion_admin?: string | null;
}

const props = defineProps<{
    licencia: Licencia;
    sesiones: Sesion[];
    fechaSugerida?: string | null;
    modo: 'alumno' | 'tutor' | 'admin';
}>();

// Alumno edita sus propios campos
const formAlumno = useForm({
    id_asistencia: String(props.licencia.id_asistencia),
    motivo:        props.licencia.motivo,
    evidencia_url: null as File | null,
    modo:          'alumno',
});

// Tutor/admin edita estado, observacion y reprogramación
const formTutor = useForm({
    estado_aprobacion:    props.licencia.estado_aprobacion,
    observacion_admin:    props.licencia.observacion_admin ?? '',
    fecha_reprogramacion: props.fechaSugerida ?? '',
    modo:                 props.modo === 'admin' ? 'admin' : 'tutor',
});

// Cuando cambia estado a no-APROBADA limpiar fecha sugerida
watch(() => formTutor.estado_aprobacion, (val) => {
    if (val !== 'APROBADA') formTutor.fecha_reprogramacion = '';
    else formTutor.fecha_reprogramacion = props.fechaSugerida ?? '';
});

const esAlumno = computed(() => props.modo === 'alumno');
const esTutorAdmin = computed(() => props.modo === 'tutor' || props.modo === 'admin');
const requiereReprogramacion = computed(() => formTutor.estado_aprobacion === 'APROBADA');

const estadoBadge = computed(() => ({
    PENDIENTE: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    APROBADA:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    RECHAZADA: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
}[props.licencia.estado_aprobacion]));

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Licencias', href: '/licencias' },
    { title: 'Editar', href: `/licencias/${props.licencia.id_licencia}/edit` },
];

const evidenciaPreviewUrl = computed(() => {
    const value = props.licencia.evidencia_url;
    if (!value) return null;
    if (value.startsWith('http://') || value.startsWith('https://')) return value;
    return `/storage/${value}`;
});


const submitAlumno   = () => formAlumno
    .transform((data) => ({ ...data, _method: 'put' }))
    .post(route('licencias.update', props.licencia.id_licencia), { forceFormData: true });
const submitTutor    = () => formTutor.put(route('licencias.update', props.licencia.id_licencia));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar licencia" />
        <UserLayout>
            <div class="max-w-3xl mx-auto space-y-4">

                <!-- Info de la licencia (siempre visible) -->
                <div class="bg-card border border-border rounded-xl p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-lg font-bold">Licencia #{{ licencia.id_licencia }}</h1>
                            <p class="text-sm text-muted-foreground mt-0.5">{{ licencia.motivo }}</p>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="estadoBadge">
                            {{ licencia.estado_aprobacion }}
                        </span>
                    </div>
                    <div v-if="licencia.observacion_admin" class="mt-3 rounded-lg bg-muted/40 px-3 py-2 text-sm">
                        <span class="font-medium">Observación:</span> {{ licencia.observacion_admin }}
                    </div>
                     <div v-if="esTutorAdmin && evidenciaPreviewUrl" class="mt-4">
                        <p class="text-sm font-medium mb-2">Evidencia adjunta</p>
                        <a :href="evidenciaPreviewUrl" target="_blank" rel="noopener noreferrer" class="inline-block">
                            <img
                                :src="evidenciaPreviewUrl"
                                alt="Evidencia de licencia"
                                class="h-24 w-24 rounded-lg border border-border object-cover"
                            />
                        </a>
                    </div>
                </div>

                <!-- Formulario ALUMNO -->
                <div v-if="esAlumno" class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-4">Editar solicitud</h2>
                    <form class="space-y-4" @submit.prevent="submitAlumno">
                        <div>
                            <label class="block text-sm mb-1">Sesión programada</label>
                            <select v-model="formAlumno.id_asistencia" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Seleccione</option>
                                <option v-for="sesion in sesiones" :key="sesion.id" :value="String(sesion.id)">
                                    #{{ sesion.id }} — {{ sesion.fecha_sesion }} (Sesión {{ sesion.numero_sesion }})
                                </option>
                            </select>
                            <p v-if="formAlumno.errors.id_asistencia" class="text-sm text-red-600 mt-1">{{ formAlumno.errors.id_asistencia }}</p>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Motivo</label>
                            <textarea v-model="formAlumno.motivo" rows="3" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="formAlumno.errors.motivo" class="text-sm text-red-600 mt-1">{{ formAlumno.errors.motivo }}</p>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Evidencia (imagen)</label>
                            <input
                                type="file"
                                accept="image/*"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                                @change="formAlumno.evidencia_url = ($event.target as HTMLInputElement).files?.[0] ?? null"
                            />
                            <p v-if="formAlumno.errors.evidencia_url" class="text-sm text-red-600 mt-1">{{ formAlumno.errors.evidencia_url }}</p>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <Link :href="route('licencias.index', { modo: 'alumno' })" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="formAlumno.processing">Guardar</button>
                        </div>
                    </form>
                </div>

                <!-- Formulario TUTOR / ADMIN -->
                <div v-if="esTutorAdmin" class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-4">Revisar licencia</h2>
                    <form class="space-y-4" @submit.prevent="submitTutor">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm mb-1">Estado de aprobación</label>
                                <select v-model="formTutor.estado_aprobacion" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                    <option value="PENDIENTE">Pendiente</option>
                                    <option value="APROBADA">Aprobar (reprogramar sesión)</option>
                                    <option value="RECHAZADA">Rechazar</option>
                                </select>
                                <p v-if="formTutor.errors.estado_aprobacion" class="text-sm text-red-600 mt-1">{{ formTutor.errors.estado_aprobacion }}</p>
                            </div>

                            <!-- Fecha reprogramación: solo cuando APROBADA -->
                            <div v-if="requiereReprogramacion">
                                <label class="block text-sm mb-1">
                                    Fecha de reprogramación
                                    <span v-if="fechaSugerida" class="text-xs text-muted-foreground ml-1">(sugerida: {{ fechaSugerida }})</span>
                                </label>
                                <input
                                    v-model="formTutor.fecha_reprogramacion"
                                    type="date"
                                    class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                                />
                                <p class="text-xs text-muted-foreground mt-1">Dejar vacío para usar la próxima fecha disponible automáticamente.</p>
                                <p v-if="formTutor.errors.fecha_reprogramacion" class="text-sm text-red-600 mt-1">{{ formTutor.errors.fecha_reprogramacion }}</p>
                            </div>
                        </div>

                        <!-- Banner explicativo al aprobar -->
                        <div v-if="requiereReprogramacion" class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 p-3 text-sm text-emerald-700 dark:text-emerald-300">
                            ℹ️ Al aprobar se creará automáticamente una nueva sesión en la fecha indicada (o la próxima disponible),
                            respetando la disponibilidad del calendario y sin conflictos con otras sesiones.
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Observación</label>
                            <textarea v-model="formTutor.observacion_admin" rows="3" class="w-full px-3 py-2 border border-border rounded-lg bg-card" placeholder="Comentario opcional para el alumno" />
                        </div>

                        <div class="flex gap-2 justify-end">
                            <Link :href="route('licencias.index', { modo: formTutor.modo })" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                            <button
                                type="submit"
                                class="px-4 py-2 text-white font-medium rounded-lg transition-colors"
                                :class="formTutor.estado_aprobacion === 'APROBADA' ? 'bg-emerald-600 hover:bg-emerald-700' : formTutor.estado_aprobacion === 'RECHAZADA' ? 'bg-destructive hover:bg-destructive/90' : 'bg-primary hover:bg-primary/90'"
                                :disabled="formTutor.processing"
                            >
                                <span v-if="formTutor.processing">Procesando…</span>
                                <span v-else-if="formTutor.estado_aprobacion === 'APROBADA'">Aprobar y reprogramar</span>
                                <span v-else-if="formTutor.estado_aprobacion === 'RECHAZADA'">Rechazar licencia</span>
                                <span v-else>Guardar</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </UserLayout>
    </AppLayout>
</template>
