<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Sesion {
    id: number;
    fecha_sesion: string;
    numero_sesion: number;
}

const props = defineProps<{ sesiones: Sesion[] }>();

const form = useForm({
    id_asistencia: '',
    motivo: '',
    evidencia_url: null as File | null,
    estado_aprobacion: 'PENDIENTE',
    observacion_admin: '',
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Licencias', href: '/licencias' },
    { title: 'Crear', href: '/licencias/create' },
];

const submit = () => form.post(route('licencias.store'), { forceFormData: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Crear licencia" />
        <UserLayout>
            <div class="max-w-3xl mx-auto bg-card border border-border rounded-xl p-5">
                <h1 class="text-xl font-bold mb-1">Solicitar licencia</h1>
                <p class="text-sm text-muted-foreground mb-4">El tutor revisará tu solicitud y reprogramará la sesión si la aprueba.</p>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm mb-1">Sesión programada *</label>
                        <select v-model="form.id_asistencia" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="">Seleccione una sesión</option>
                            <option v-for="sesion in sesiones" :key="sesion.id" :value="String(sesion.id)">
                                #{{ sesion.id }} — {{ sesion.fecha_sesion }} (Sesión {{ sesion.numero_sesion }})
                            </option>
                        </select>
                        <p v-if="form.errors.id_asistencia" class="text-sm text-red-600 mt-1">{{ form.errors.id_asistencia }}</p>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Motivo *</label>
                        <textarea v-model="form.motivo" rows="3" class="w-full px-3 py-2 border border-border rounded-lg bg-card" placeholder="Describe el motivo de la licencia" />
                        <p v-if="form.errors.motivo" class="text-sm text-red-600 mt-1">{{ form.errors.motivo }}</p>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Evidencia (imagen opcional)</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="w-full px-3 py-2 border border-border rounded-lg bg-card"
                            @change="form.evidencia_url = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        />
                        <p v-if="form.errors.evidencia_url" class="text-sm text-red-600 mt-1">{{ form.errors.evidencia_url }}</p>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <Link :href="route('licencias.index')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="form.processing">Enviar solicitud</button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>

