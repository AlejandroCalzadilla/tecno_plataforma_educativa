<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';
interface User {
    id: number;
    name: string;
    email: string;
    estado: string;
    is_alumno: boolean;
    is_tutor: boolean;
    is_propietario: boolean;
}

interface Tutor {
    id: number;
    especialidad: string;
    biografia: string;
    banco_nombre: string;
    banco_cbu: string;
}

interface Alumno {
    id: number;
    direccion: string;
    fecha_nacimiento: string;
    nivel_educativo: string;
}

const props = defineProps<{
    user: User;
    tutor?: Tutor | null;
    alumno?: Alumno | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: 'users',
    },
    {
        title: `Editar ${props.user.name}`,
        href: '#',
    },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    estado: props.user.estado,
    is_alumno: props.user.is_alumno,
    is_tutor: props.user.is_tutor,
    is_propietario: props.user.is_propietario,

    // Campos de alumno
    direccion: props.alumno?.direccion || '',
    fecha_nacimiento: props.alumno?.fecha_nacimiento || '',
    nivel_educativo: props.alumno?.nivel_educativo || '',
    // Campos de tutor
    especialidad: props.tutor?.especialidad || '',
    biografia: props.tutor?.biografia || '',
    banco_nombre: props.tutor?.banco_nombre || '',
    banco_cbu: props.tutor?.banco_cbu || '',
    
});

const submit = () => {
    form.put(route('users.update', props.user.id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Editar ${user.name}`" />

        <UserLayout>
            <div class="max-w-2xl mx-auto bg-card border border-border rounded-xl shadow-sm p-6">
                <h1 class="text-2xl font-bold text-foreground mb-6">Editar Usuario</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Información básica -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">
                            Información Básica
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Nombre Completo
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Email
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"+
                                    disabled
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Roles -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">
                            Roles
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_alumno" type="checkbox" class="h-4 w-4" />
                                <span class="text-foreground">Alumno</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_tutor" type="checkbox" class="h-4 w-4" />
                                <span class="text-foreground">Tutor</span>
                            </label>

                            <!-- <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_propietario" type="checkbox" class="h-4 w-4" />
                                <span class="text-foreground">Propietario</span>
                            </label> -->
                        </div>
                    </div>

                    <!-- Datos de Alumno -->
                    <div v-if="form.is_alumno" class="space-y-4 bg-muted p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-foreground">Información del Alumno</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Dirección
                            </label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Fecha de Nacimiento
                                </label>
                                <input
                                    v-model="form.fecha_nacimiento"
                                    type="date"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Nivel Educativo
                                </label>
                                <input
                                    v-model="form.nivel_educativo"
                                    type="text"
                                    placeholder="Ej: Secundario, Universitario"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Tutor -->
                    <div v-if="form.is_tutor" class="space-y-4 bg-muted p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-foreground">Información del Tutor</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Especialidad
                            </label>
                            <input
                                v-model="form.especialidad"
                                type="text"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Biografía
                            </label>
                            <textarea
                                v-model="form.biografia"
                                rows="4"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Nombre del Banco
                                </label>
                                <input
                                    v-model="form.banco_nombre"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    CBU
                                </label>
                                <input
                                    v-model="form.banco_cbu"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-4 justify-end pt-6 border-t border-border">
                        <Button
                            
                            :href="route('users.index')"
                            class="bg-secondary text-secondary-foreground hover:bg-secondary/80"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </Button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>