<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: 'users',
    },
    {
        title: 'Crear tutor',
        href: '#',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    especialidad: '',
    biografia: '',
    banco_nombre: '',
    banco_cbu: '',
});

const submit = () => {
    form.post(route('users.store'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Crear tutor" />

        <UserLayout>
            <div class="max-w-2xl mx-auto bg-card border border-border rounded-xl shadow-sm p-6">
                <h1 class="text-2xl font-bold text-foreground mb-6">Crear Tutor</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-foreground border-b border-border pb-2">
                            Datos de acceso
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Nombre completo</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Contraseña</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Confirmar contraseña</label>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 bg-muted p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-foreground">Información del Tutor</h2>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Especialidad</label>
                            <input
                                v-model="form.especialidad"
                                type="text"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                :class="{ 'border-red-500': form.errors.especialidad }"
                            />
                            <p v-if="form.errors.especialidad" class="text-red-500 text-sm mt-1">{{ form.errors.especialidad }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Biografía</label>
                            <textarea
                                v-model="form.biografia"
                                rows="4"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                                :class="{ 'border-red-500': form.errors.biografia }"
                            />
                            <p v-if="form.errors.biografia" class="text-red-500 text-sm mt-1">{{ form.errors.biografia }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Nombre del banco</label>
                                <input
                                    v-model="form.banco_nombre"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.banco_nombre }"
                                />
                                <p v-if="form.errors.banco_nombre" class="text-red-500 text-sm mt-1">{{ form.errors.banco_nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">CBU</label>
                                <input
                                    v-model="form.banco_cbu"
                                    type="text"
                                    class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.banco_cbu }"
                                />
                                <p v-if="form.errors.banco_cbu" class="text-red-500 text-sm mt-1">{{ form.errors.banco_cbu }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end pt-6 border-t border-border">
                        <Link
                            :href="route('users.index')"
                            class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg text-sm font-medium hover:bg-secondary/80"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Creando...' : 'Crear tutor' }}
                        </button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>
