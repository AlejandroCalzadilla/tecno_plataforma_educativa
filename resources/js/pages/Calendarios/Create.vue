<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { route } from 'ziggy-js';
import { watch } from 'vue';

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

const props = defineProps<{
    servicios: Servicio[];
    tutores: Tutor[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Calendarios', href: '/calendarios' },
    { title: 'Crear', href: '/calendarios/create' },
];

const form = useForm({
    id_servicio: '',
    tipo_programacion: 'CITA_LIBRE' as TipoProgramacion,
    fecha_inicio: '',
    numero_sesiones: '' as string | number,
    duracion_sesion_minutos: 60,
    costo_total: '' as string | number,
    cupos_maximos: 1,
    disponibilidades: [
        {
            dia_semana: 'LUNES',
            hora_apertura: '08:00',
            hora_cierre: '10:00',
        },
    ] as DisponibilidadForm[],
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
    form.post(route('calendarios.store'));
};


watch(
    () => form.tipo_programacion,
    (nuevoTipo) => {
        if (nuevoTipo !== 'PAQUETE_FIJO') {
            form.cupos_maximos = 1;
        }
    }
);

const calcularHoraCierre = (horaApertura: string, duracionMinutos: number | string): string => {
    if (!horaApertura || !duracionMinutos) return '';

    // Separamos horas y minutos
    const [horas, minutos] = horaApertura.split(':').map(Number);
    
    // Calculamos el total de minutos transcurridos desde las 00:00
    const totalMinutos = (horas * 60) + minutos + parseInt(duracionMinutos.toString());

    // Obtenemos las nuevas horas y minutos (usamos % 1440 para no exceder las 24h)
    const nuevasHoras = Math.floor((totalMinutos / 60) % 24);
    const nuevosMinutos = totalMinutos % 60;

    // Formateamos con ceros a la izquierda (HH:mm)
    return `${String(nuevasHoras).padStart(2, '0')}:${String(nuevosMinutos).padStart(2, '0')}`;
};

// 1. Escuchar cuando cambia la DURACIÓN global
watch(
    () => form.duracion_sesion_minutos,
    (nuevaDuracion) => {
        if (!nuevaDuracion) return;
        form.disponibilidades.forEach((disp) => {
            if (disp.hora_apertura) {
                disp.hora_cierre = calcularHoraCierre(disp.hora_apertura, nuevaDuracion);
            }
        });
    }
);

// 2. Escuchar cuando cambia cualquier HORA DE APERTURA individualmente
watch(
    () => form.disponibilidades,
    (nuevaDisp) => {
        nuevaDisp.forEach((disp) => {
            // Solo recalculamos si hay apertura y la duración está definida
            if (disp.hora_apertura && form.duracion_sesion_minutos) {
                const nuevaHoraCierre = calcularHoraCierre(disp.hora_apertura, form.duracion_sesion_minutos);
                
                // Evitamos bucles infinitos comparando antes de asignar
                if (disp.hora_cierre !== nuevaHoraCierre) {
                    disp.hora_cierre = nuevaHoraCierre;
                }
            }
        });
    },
    { deep: true } // Crucial para detectar cambios dentro del array
);


const tutorLabel = (tutor: Tutor) => tutor.usuario?.name ?? `Tutor ${tutor.id}`;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Crear calendario" />
        <UserLayout>
            <div class="max-w-4xl mx-auto bg-card border border-border rounded-xl p-6 space-y-6">
                <h1 class="text-xl font-bold text-foreground">Crear calendario</h1>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Servicio</label>
                            <select v-model="form.id_servicio"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Seleccione</option>
                                <option v-for="servicio in servicios" :key="servicio.id" :value="String(servicio.id)">
                                    {{ servicio.nombre }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_servicio" class="text-sm text-red-600 mt-1">{{
                                form.errors.id_servicio }}</p>
                        </div>

                        <!--   <div>
                            <label class="block text-sm mb-1">Tutor</label>
                            <select v-model="form.id_tutor" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Seleccione</option>
                                <option v-for="tutor in tutores" :key="tutor.id" :value="String(tutor.id)">
                                    {{ tutorLabel(tutor) }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_tutor" class="text-sm text-red-600 mt-1">{{ form.errors.id_tutor }}</p>
                        </div> -->

                        <div>
                            <label class="block text-sm mb-1">Tipo de programación</label>
                            <select v-model="form.tipo_programacion"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="CITA_LIBRE">Cita libre</option>
                                <option value="PAQUETE_FIJO">Paquete fijo</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Duración por sesión (minutos)</label>
                            <input v-model="form.duracion_sesion_minutos" type="number" min="15"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.duracion_sesion_minutos" class="text-sm text-red-600 mt-1">{{
                                form.errors.duracion_sesion_minutos }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Costo total</label>
                            <input v-model="form.costo_total" type="number" step="0.01" min="0"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.costo_total" class="text-sm text-red-600 mt-1">{{
                                form.errors.costo_total }}</p>
                        </div>

                        <div v-if="form.tipo_programacion === 'PAQUETE_FIJO'">
                            <label class="block text-sm mb-1">Cupos máximos</label>
                            <input v-model="form.cupos_maximos" type="number" min="1"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.cupos_maximos" class="text-sm text-red-600 mt-1">{{
                                form.errors.cupos_maximos }}</p>
                        </div>
                        <div v-else class="p-4 border border-slate-200  rounded-xl">
                            <label class="block text-sm font-medium mb-1 ">Cupos máximos</label>
                            <div class="flex items-center gap-2 ">
                                <span class="text-lg font-bold">1</span>
                                <span class="text-xs">(Sesión individual predeterminada)</span>
                            </div>
                            <input type="hidden" v-model="form.cupos_maximos" />
                        </div>

                    </div>

                    <div v-if="form.tipo_programacion === 'PAQUETE_FIJO'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Fecha de inicio del curso</label>
                            <input v-model="form.fecha_inicio" type="date"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">{{
                                form.errors.fecha_inicio }}</p>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Número de sesiones</label>
                            <input v-model="form.numero_sesiones" type="number" min="1"
                                class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.numero_sesiones" class="text-sm text-red-600 mt-1">{{
                                form.errors.numero_sesiones }}</p>
                        </div>

                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between">
                            <h2 class="font-semibold">Disponibilidad del tutor</h2>
                           
                            <button type="button" class="px-3 py-1.5 bg-secondary text-secondary-foreground rounded"
                                @click="addDisponibilidad">
                                Agregar bloque
                            </button>
                             <p>nota: la hora de cierre se calcula automáticamente según la duración de la sesión</p>
                        </div>

                        <div v-for="(item, index) in form.disponibilidades" :key="index"
                            class="grid grid-cols-1 md:grid-cols-4 gap-3 border border-border rounded-lg p-3">
                            <select v-model="item.dia_semana" class="px-3 py-2 border border-border rounded-lg bg-card">
                                <option v-for="dia in diasSemana" :key="dia" :value="dia">{{ dia }}</option>
                            </select>
                            <input v-model="item.hora_apertura" type="time"
                                class="px-3 py-2 border border-border rounded-lg bg-card" />
                            <input v-model="item.hora_cierre" type="time" readonly
                                class="px-3 py-2 border border-border rounded-lg bg-card" />
                            <button type="button" class="px-3 py-2 bg-destructive text-destructive-foreground rounded"
                                @click="removeDisponibilidad(index)">
                                Quitar
                            </button>
                        </div>
                        <p v-if="form.errors.disponibilidades" class="text-sm text-red-600">{{
                            form.errors.disponibilidades }}</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link :href="route('calendarios.index')"
                            class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg"
                            :disabled="form.processing">
                            Guardar calendario
                        </button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>
