<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';

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

const props = defineProps<{ licencia: Licencia; sesiones: Sesion[] }>();

const form = useForm({
    id_asistencia: String(props.licencia.id_asistencia),
    motivo: props.licencia.motivo,
    evidencia_url: props.licencia.evidencia_url ?? '',
    estado_aprobacion: props.licencia.estado_aprobacion,
    observacion_admin: props.licencia.observacion_admin ?? '',
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Licencias', href: '/licencias' },
    { title: 'Editar', href: `/licencias/${props.licencia.id_licencia}/edit` },
];

const submit = () => form.put(route('licencias.update', props.licencia.id_licencia));
const page = usePage();
const roles = page.props.auth.user.roles;
const esAlumno = computed(() => !!roles!.alumno);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar licencia" />
        <UserLayout>
            <div class="max-w-3xl mx-auto bg-card border border-border rounded-xl p-5">
                <h1 class="text-xl font-bold mb-4">Editar licencia</h1>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm mb-1">Sesión programada</label>
                        <select v-model="form.id_asistencia" :disabled="!esAlumno" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="">Seleccione</option>
                            <option v-for="sesion in sesiones" :key="sesion.id" :value="String(sesion.id)">
                                #{{ sesion.id }} - {{ sesion.fecha_sesion }} (Sesión {{ sesion.numero_sesion }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Motivo</label>
                        <textarea v-model="form.motivo" :disabled="!esAlumno" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Evidencia URL</label>
                        <input v-model="form.evidencia_url" :disabled="!esAlumno"  type="text" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Estado</label>
                        <select v-model="form.estado_aprobacion"  class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="APROBADA">Aprobada</option>
                            <option value="RECHAZADA">Rechazada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Observación admin</label>
                        <textarea v-model="form.observacion_admin" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div class="flex gap-2 justify-end">
                        <Link :href="route('licencias.index')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="form.processing">Actualizar</button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>
