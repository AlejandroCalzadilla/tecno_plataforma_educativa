<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { route } from 'ziggy-js';

type TipoProgramacion = 'CITA_LIBRE' | 'PAQUETE_FIJO';

type DisponibilidadForm = {
    dia_semana: string;
    hora_apertura: string;
    hora_cierre: string;
};

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

interface Calendario {
    id: number;
    id_servicio: number;
    id_tutor: number;
    tipo_programacion: TipoProgramacion;
    fecha_inicio: string | null;
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    cupos_maximos: number;
    disponibilidades: Disponibilidad[];
}

const props = defineProps<{
    calendario: Calendario;
    servicios: Servicio[];
    tutores: Tutor[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Calendarios', href: '/calendarios' },
    { title: 'Editar', href: `/calendarios/${props.calendario.id}/edit` },
];

const form = useForm({
    id_servicio: String(props.calendario.id_servicio),
    id_tutor: String(props.calendario.id_tutor),
    tipo_programacion: props.calendario.tipo_programacion,
    fecha_inicio: props.calendario.fecha_inicio ? String(props.calendario.fecha_inicio).substring(0, 10) : '',
    numero_sesiones: props.calendario.numero_sesiones ?? '',
    duracion_sesion_minutos: props.calendario.duracion_sesion_minutos,
    costo_total: props.calendario.costo_total,
    cupos_maximos: props.calendario.cupos_maximos,
    disponibilidades: props.calendario.disponibilidades.map((d) => ({
        dia_semana: d.dia_semana,
        hora_apertura: d.hora_apertura.substring(0, 5),
        hora_cierre: d.hora_cierre.substring(0, 5),
    })) as DisponibilidadForm[],
});

const diasSemana = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];

const addDisponibilidad = () => {
    form.disponibilidades.push({
        dia_semana: 'LUNES',
        hora_apertura: '08:00',
        hora_cierre: '10:00',
    });
};

const removeDisponibilidad = (index: number) => {
    if (form.disponibilidades.length === 1) {
        return;
    }

    form.disponibilidades.splice(index, 1);
};

const submit = () => {
    form.put(route('calendarios.update', props.calendario.id));
};

const tutorLabel = (tutor: Tutor) => tutor.usuario?.name ?? `Tutor ${tutor.id}`;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar calendario" />
        <UserLayout>
            <div class="max-w-4xl mx-auto bg-card border border-border rounded-xl p-6 space-y-6">
                <h1 class="text-xl font-bold text-foreground">Editar calendario</h1>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Servicio</label>
                            <select v-model="form.id_servicio" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Seleccione</option>
                                <option v-for="servicio in servicios" :key="servicio.id" :value="String(servicio.id)">
                                    {{ servicio.nombre }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_servicio" class="text-sm text-red-600 mt-1">{{ form.errors.id_servicio }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Tutor</label>
                            <select v-model="form.id_tutor" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Seleccione</option>
                                <option v-for="tutor in tutores" :key="tutor.id" :value="String(tutor.id)">
                                    {{ tutorLabel(tutor) }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_tutor" class="text-sm text-red-600 mt-1">{{ form.errors.id_tutor }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Tipo de programación</label>
                            <select v-model="form.tipo_programacion" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="CITA_LIBRE">Cita libre</option>
                                <option value="PAQUETE_FIJO">Paquete fijo</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Duración por sesión (minutos)</label>
                            <input v-model="form.duracion_sesion_minutos" type="number" min="15" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.duracion_sesion_minutos" class="text-sm text-red-600 mt-1">{{ form.errors.duracion_sesion_minutos }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Costo total</label>
                            <input v-model="form.costo_total" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.costo_total" class="text-sm text-red-600 mt-1">{{ form.errors.costo_total }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Cupos máximos</label>
                            <input v-model="form.cupos_maximos" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.cupos_maximos" class="text-sm text-red-600 mt-1">{{ form.errors.cupos_maximos }}</p>
                        </div>
                    </div>

                    <div v-if="form.tipo_programacion === 'PAQUETE_FIJO'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Fecha de inicio del curso</label>
                            <input v-model="form.fecha_inicio" type="date" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">{{ form.errors.fecha_inicio }}</p>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Número de sesiones</label>
                            <input v-model="form.numero_sesiones" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.numero_sesiones" class="text-sm text-red-600 mt-1">{{ form.errors.numero_sesiones }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Disponibilidad del tutor</h2>
                            <button type="button" class="px-3 py-1.5 bg-secondary text-secondary-foreground rounded" @click="addDisponibilidad">
                                Agregar bloque
                            </button>
                        </div>

                        <div v-for="(item, index) in form.disponibilidades" :key="index" class="grid grid-cols-1 md:grid-cols-4 gap-3 border border-border rounded-lg p-3">
                            <select v-model="item.dia_semana" class="px-3 py-2 border border-border rounded-lg bg-card">
                                <option v-for="dia in diasSemana" :key="dia" :value="dia">{{ dia }}</option>
                            </select>
                            <input v-model="item.hora_apertura" type="time" class="px-3 py-2 border border-border rounded-lg bg-card" />
                            <input v-model="item.hora_cierre" type="time" class="px-3 py-2 border border-border rounded-lg bg-card" />
                            <button type="button" class="px-3 py-2 bg-destructive text-destructive-foreground rounded" @click="removeDisponibilidad(index)">
                                Quitar
                            </button>
                        </div>
                        <p v-if="form.errors.disponibilidades" class="text-sm text-red-600">{{ form.errors.disponibilidades }}</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link :href="route('calendarios.index')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="form.processing">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>
