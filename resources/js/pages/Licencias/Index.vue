<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Asistencia {
    id: number;
    sesion?: {
        fecha_sesion: string;
        numero_sesion: number | null;
        calendario?: {
            servicio?: { nombre: string };
            tutor?: { usuario?: { name: string } };
        };
    };
}
interface Licencia {
    id_licencia: number;
    asistencia: Asistencia;
    fecha_solicitud: string;
    motivo: string;
    estado_aprobacion: 'PENDIENTE' | 'APROBADA' | 'RECHAZADA';
    observacion_admin?: string | null;
}
interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
interface Filters {
    search?: string;
    estado_aprobacion?: string;
    fecha_desde?: string;
    fecha_hasta?: string;
    id_tutor?: string;
    id_servicio?: string;
}

const props = defineProps<{
    licencias: Pagination<Licencia>;
    filters?: Filters;
    modo: 'alumno' | 'tutor' | 'admin';
    tutores?: Array<{ id: number; nombre: string }>;
    servicios?: Array<{ id: number; nombre: string }>;
}>();

const search    = ref(props.filters?.search ?? '');
const estado    = ref(props.filters?.estado_aprobacion ?? '');
const fechaDesde = ref(props.filters?.fecha_desde ?? '');
const fechaHasta = ref(props.filters?.fecha_hasta ?? '');
const idTutor   = ref(props.filters?.id_tutor ?? '');
const idServicio = ref(props.filters?.id_servicio ?? '');

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Licencias', href: '/licencias' }];

const page  = usePage();
const roles = page.props.auth.user.roles;
const esAlumno = computed(() => !!roles?.alumno);
const esTutor = computed(() => !!roles?.tutor);
const puedeCambiarModo = computed(() => esAlumno.value && esTutor.value);
const modoActivo = ref(props.modo);

const badgeClass = (e: string) => ({
    PENDIENTE: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    APROBADA:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    RECHAZADA: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
}[e] ?? '');

const filtrar = () => {
    router.get(route('licencias.index'), {
        modo:                modoActivo.value,
        search:              search.value,
        estado_aprobacion:   estado.value,
        fecha_desde:         fechaDesde.value,
        fecha_hasta:         fechaHasta.value,
        id_tutor:            idTutor.value,
        id_servicio:         idServicio.value,
    }, { preserveState: true, preserveScroll: true });
};

const limpiar = () => {
    search.value = ''; estado.value = ''; fechaDesde.value = '';
    fechaHasta.value = ''; idTutor.value = ''; idServicio.value = '';
    filtrar();
};

const eliminar = (id: number) => {
    if (window.confirm('¿Eliminar licencia?')) router.delete(route('licencias.destroy', id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Licencias" />
        <UserLayout>
            <div class="space-y-4">
                <div class="bg-card border border-border rounded-xl p-5 flex items-center justify-between">
                    <h1 class="text-xl font-bold">Licencias</h1>
                    <div class="flex items-center gap-2">
                        <select
                            v-if="puedeCambiarModo"
                            v-model="modoActivo"
                            class="px-3 py-2 border border-border rounded-lg bg-card text-sm"
                            @change="filtrar"
                        >
                            <option value="alumno">Ver como alumno</option>
                            <option value="tutor">Ver como tutor</option>
                        </select>
                        <Link
                            v-if="esAlumno && modoActivo === 'alumno'"
                            :href="route('licencias.create', { modo: modoActivo })"
                            class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm"
                        >
                        + Nueva solicitud
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-card border border-border rounded-xl p-4 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input v-model="search" type="text" placeholder="Buscar por motivo" class="px-3 py-2 border border-border rounded-lg bg-card" />
                        <select v-model="estado" class="px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="APROBADA">Aprobada</option>
                            <option value="RECHAZADA">Rechazada</option>
                        </select>
                        <!-- Fecha: visible para tutor y admin -->
                        <template v-if="modo !== 'alumno'">
                            <input v-model="fechaDesde" type="date" class="px-3 py-2 border border-border rounded-lg bg-card" placeholder="Desde" />
                            <input v-model="fechaHasta" type="date" class="px-3 py-2 border border-border rounded-lg bg-card" placeholder="Hasta" />
                        </template>
                        <!-- Filtros admin -->
                        <template v-if="modo === 'admin'">
                            <select v-model="idTutor" class="px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Todos los tutores</option>
                                <option v-for="t in tutores" :key="t.id" :value="String(t.id)">{{ t.nombre }}</option>
                            </select>
                            <select v-model="idServicio" class="px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="">Todos los servicios</option>
                                <option v-for="s in servicios" :key="s.id" :value="String(s.id)">{{ s.nombre }}</option>
                            </select>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm" @click="filtrar">Filtrar</button>
                        <button class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg text-sm" @click="limpiar">Limpiar</button>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="bg-card border border-border rounded-xl p-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2 pr-3">ID</th>
                                <th class="py-2 pr-3">Sesión / Fecha</th>
                             <!--    <th v-if="modo !== 'alumno'" class="py-2 pr-3">Alumno</th> -->
                                <th v-if="modo === 'admin'" class="py-2 pr-3">Tutor / Servicio</th>
                                <th class="py-2 pr-3">Motivo</th>
                                <th class="py-2 pr-3">Solicitud</th>
                                <th class="py-2 pr-3">Estado</th>
                                <th class="py-2 pr-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="lic in licencias.data" :key="lic.id_licencia" class="border-b border-border/60">
                                <td class="py-2 pr-3 text-muted-foreground">#{{ lic.id_licencia }}</td>
                                <td class="py-2 pr-3">
                                    <div>Sesión #{{ lic.asistencia.id }}</div>
                                    <div class="text-xs text-muted-foreground">{{ lic.asistencia.sesion?.fecha_sesion }}</div>
                                </td>
                               <!--  <td v-if="modo !== 'alumno'" class="py-2 pr-3 text-sm">
                                    {{ lic.asistencia.inscripcion?.alumno?.usuario?.name ?? '—' }}
                                </td> -->
                                <td v-if="modo === 'admin'" class="py-2 pr-3 text-xs">
                                    <div>{{ lic.asistencia.sesion?.calendario?.tutor?.usuario?.name ?? '—' }}</div>
                                    <div class="text-muted-foreground">{{ lic.asistencia.sesion?.calendario?.servicio?.nombre ?? '—' }}</div>
                                </td>
                                <td class="py-2 pr-3 max-w-xs truncate">{{ lic.motivo }}</td>
                                <td class="py-2 pr-3 text-muted-foreground text-xs">{{ lic.fecha_solicitud }}</td>
                                <td class="py-2 pr-3">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="badgeClass(lic.estado_aprobacion)">
                                        {{ lic.estado_aprobacion }}
                                    </span>
                                </td>
                                <td class="py-2 pr-3 space-x-2 whitespace-nowrap">
                                    <Link :href="route('licencias.edit', { licencia: lic.id_licencia, modo: modoActivo })" class="px-3 py-1 bg-primary text-primary-foreground rounded text-xs">Editar</Link>
                                    <button v-if="esAlumno && modoActivo === 'alumno'" class="px-3 py-1 bg-destructive text-destructive-foreground rounded text-xs" @click="eliminar(lic.id_licencia)">Eliminar</button>
                                </td>
                            </tr>
                            <tr v-if="licencias.data.length === 0">
                                <td colspan="8" class="py-8 text-center text-muted-foreground">No hay licencias con esos filtros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="licencias.links.length > 3" class="flex gap-1 flex-wrap">
                    <template v-for="link in licencias.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 text-sm rounded-lg border border-border"
                            :class="link.active ? 'bg-primary text-primary-foreground' : 'bg-card'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                    </template>
                </div>

            </div>
        </UserLayout>
    </AppLayout>
</template>
