<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
    tutor?: Tutor;
    servicio?: Servicio;
}

interface SesionPreview {
    numero_sesion: number;
    fecha_sesion: string;
    dia_semana: string;
    fecha_hora_inicio: string;
    fecha_hora_fin: string;
    estado_asistencia: string;
}

interface CuotaPreview {
    numero_cuota: number;
    monto_cuota: string;
    fecha_vencimiento: string;
}

interface Params {
    id_alumno: string | number;
    fecha_inicio: string;
    tipo_pago_pref: 'CONTADO' | 'CUOTAS';
    cantidad_cuotas: number;
    metodo_pago: 'EFECTIVO' | 'QR' | 'TRANSFERENCIA' | 'TARJETA';
}

const props = defineProps<{
    calendario: Calendario;
    sesionesProgramadas: SesionPreview[];
    cuotasPreview: CuotaPreview[];
    params: Params;
}>();

const form = useForm({
    id_alumno: String(props.params.id_alumno ?? ''),
    fecha_inicio: props.params.fecha_inicio,
    tipo_pago_pref: props.params.tipo_pago_pref,
    cantidad_cuotas: props.params.cantidad_cuotas,
    metodo_pago: props.params.metodo_pago,
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Catálogo', href: '/catalogo' },
    { title: props.calendario.servicio?.nombre ?? 'Servicio', href: `/catalogo/servicios/${props.calendario.servicio?.id}` },
    { title: 'Pago', href: `/catalogo/calendarios/${props.calendario.id}/pago` },
];

const tutorLabel = computed(() => props.calendario.tutor?.usuario?.name ?? 'Tutor sin nombre');

const recalcularPago = () => {
    form.get(route('catalogo.inscripcion.pago', props.calendario.id), {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmarPago = () => {
    form.post(route('catalogo.inscripcion.confirmar-pago', props.calendario.id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Pago (simulado)" />
        <UserLayout>
            <div class="space-y-5">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">Pago simulado</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Al confirmar: en paquete fijo se crean asistencias sobre sesiones existentes; en cita libre se crea una sesión y su asistencia.
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>Servicio:</strong> {{ calendario.servicio?.nombre }}</p>
                    <p><strong>Tutor:</strong> {{ tutorLabel }}</p>
                    <p><strong>Tipo:</strong> {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? 'Paquete' : 'Cita única' }}</p>
                    <p><strong>Total:</strong> ${{ calendario.costo_total }}</p>
                </div>

                <form class="bg-card border border-border rounded-xl p-5 space-y-4" @submit.prevent="confirmarPago">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">ID alumno *</label>
                            <input v-model="form.id_alumno" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.id_alumno" class="text-sm text-red-600 mt-1">{{ form.errors.id_alumno }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Fecha inicio sesiones *</label>
                            <input v-model="form.fecha_inicio" type="date" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">{{ form.errors.fecha_inicio }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Tipo de pago</label>
                            <select v-model="form.tipo_pago_pref" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="CONTADO">Contado</option>
                                <option value="CUOTAS">Cuotas</option>
                            </select>
                        </div>

                        <div v-if="form.tipo_pago_pref === 'CUOTAS'">
                            <label class="block text-sm mb-1">Cantidad de cuotas</label>
                            <input v-model="form.cantidad_cuotas" type="number" min="2" max="24" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.cantidad_cuotas" class="text-sm text-red-600 mt-1">{{ form.errors.cantidad_cuotas }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Método</label>
                            <select v-model="form.metodo_pago" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="QR">QR</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA">Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="recalcularPago">
                            Recalcular vista
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="form.processing">
                            Pagar y guardar inscripción
                        </button>
                    </div>
                </form>

                <div v-if="form.tipo_pago_pref === 'CUOTAS'" class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Plan de cuotas (preview)</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2">Cuota</th>
                                <th class="py-2">Monto</th>
                                <th class="py-2">Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cuota in cuotasPreview" :key="cuota.numero_cuota" class="border-b border-border/60">
                                <td class="py-2">{{ cuota.numero_cuota }}</td>
                                <td class="py-2">${{ cuota.monto_cuota }}</td>
                                <td class="py-2">{{ cuota.fecha_vencimiento }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Sesiones previstas</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2 pr-3">#</th>
                                <th class="py-2 pr-3">Fecha</th>
                                <th class="py-2 pr-3">Día</th>
                                <th class="py-2 pr-3">Inicio</th>
                                <th class="py-2 pr-3">Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sesion in sesionesProgramadas" :key="sesion.numero_sesion" class="border-b border-border/60">
                                <td class="py-2 pr-3">{{ sesion.numero_sesion }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_sesion }}</td>
                                <td class="py-2 pr-3">{{ sesion.dia_semana }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_hora_inicio }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_hora_fin }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-2">
                    <Link :href="route('catalogo.inscripcion.preview', calendario.id)" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">
                        Volver al preview
                    </Link>
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
