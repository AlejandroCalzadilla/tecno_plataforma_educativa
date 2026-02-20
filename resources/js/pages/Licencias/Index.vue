<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Licencia {
    id_licencia: number;
    id_asistencia: number;
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
}

const props = defineProps<{
    licencias: Pagination<Licencia>;
    filters?: Filters;
}>();

const search = ref(props.filters?.search ?? '');
const estado = ref(props.filters?.estado_aprobacion ?? '');
const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Licencias', href: '/licencias' }];

const filtrar = () => {
    router.get(route('licencias.index'), { search: search.value, estado_aprobacion: estado.value }, { preserveState: true, preserveScroll: true });
};

const limpiar = () => {
    search.value = '';
    estado.value = '';
    filtrar();
};

const eliminar = (id: number) => {
    if (window.confirm('¿Eliminar licencia?')) {
        router.delete(route('licencias.destroy', id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Licencias" />
        <UserLayout>
            <div class="space-y-4">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold">Licencias</h1>
                </div>

                <div class="bg-card border border-border rounded-xl p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input v-model="search" type="text" placeholder="Buscar por motivo" class="px-3 py-2 border border-border rounded-lg bg-card" />
                    <select v-model="estado" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="APROBADA">Aprobada</option>
                        <option value="RECHAZADA">Rechazada</option>
                    </select>
                    <button class="px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="filtrar">Filtrar</button>
                    <button class="px-3 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="limpiar">Limpiar</button>
                </div>

                <div class="flex justify-end">
                    <Link :href="route('licencias.create')" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg">Nueva licencia</Link>
                </div>

                <div class="bg-card border border-border rounded-xl p-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2 pr-3">ID</th>
                                <th class="py-2 pr-3">Sesión</th>
                                <th class="py-2 pr-3">Motivo</th>
                                <th class="py-2 pr-3">Estado</th>
                                <th class="py-2 pr-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="licencia in licencias.data" :key="licencia.id_licencia" class="border-b border-border/60">
                                <td class="py-2 pr-3">{{ licencia.id_licencia }}</td>
                                <td class="py-2 pr-3">{{ licencia.id_asistencia }}</td>
                                <td class="py-2 pr-3">{{ licencia.motivo }}</td>
                                <td class="py-2 pr-3">{{ licencia.estado_aprobacion }}</td>
                                <td class="py-2 pr-3 space-x-2">
                                    <Link :href="route('licencias.edit', licencia.id_licencia)" class="px-3 py-1 bg-primary text-primary-foreground rounded">Editar</Link>
                                    <button class="px-3 py-1 bg-destructive text-destructive-foreground rounded" @click="eliminar(licencia.id_licencia)">Eliminar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
