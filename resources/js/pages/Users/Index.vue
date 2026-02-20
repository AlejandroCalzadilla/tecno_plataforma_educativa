<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';

/* type Props = {
    mustVerifyEmail: boolean;
    status?: string;
}; */
const isModalOpen = ref(false);
const selectedUser = ref<User | null>(null);
const searchQuery = ref('');
const selectedRole = ref('');


const enum Estado {
    ACTIVO = 'activo',
    INACTIVO = 'inactivo',
    DESCONOCIDO = 'desconocido',
}

// Función para abrir el modal con un usuario
const openModal = (user: User) => {
    selectedUser.value = user;
    isModalOpen.value = true;
};

// Función para cerrar el modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedUser.value = null;
};


//defineProps<Props>();
interface User {
    id: number;
    name: string;
    email: string;
    is_alumno: boolean;
    is_tutor: boolean;
    is_propietario: boolean;
    created_at: string;
    estado: string;
    // Otros campos que puedas tener en tu modelo de usuario

}
interface Pagination<T> {
    data: T[];
    links: any[];
    current_page: number;
    // ... otros campos opcionales
}

// Recibimos los usuarios desde el controlador
const props=defineProps<{
    usuarios: Pagination<User>;

}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: 'users',
    },
];

const page = usePage();
const user = page.props.auth.user;


const applyFilters = () => {
    router.visit('/users', {
        data: {
            search: searchQuery.value,
            role: selectedRole.value,
        },
        method: 'get',
        preserveScroll: true,
    });
};
const clearFilters = () => {
    searchQuery.value = '';
    selectedRole.value = '';
    applyFilters();
};

const confirm = (message: string) => {
    if (!window.confirm(message)) {
        // Si el usuario cancela, evitamos que se envíe la solicitud
        event?.preventDefault();
    }
};

 console.log('Props recibidas:', props.usuarios);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Usuarios" />
        <h1 class="sr-only">Usuarios</h1>

        <UserLayout>
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <h2 class="p-6 text-lg font-bold text-foreground mb-4">Filtrar Usuarios</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4">
                    <!-- Input de búsqueda -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Buscar por nombre o email
                        </label>
                        <input v-model="searchQuery" type="text" placeholder="Escriba un nombre o email..."
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <!-- Select de rol -->
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Filtrar por rol
                        </label>
                        <select v-model="selectedRole"
                            class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Todos los roles --</option>
                            <option value="alumno">Alumno</option>
                            <option value="tutor">Tutor</option>
                            <option value="propietario">Propietario</option>
                        </select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters"
                            class="flex-1  cursor-pointer px-4 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 transition">
                            🔍 Buscar
                        </button>
                        <button @click="clearFilters"
                            class="flex-1 px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg font-medium hover:bg-secondary/60 transition">
                            ✕ Limpiar
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-muted-foreground">
                        <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                            <tr>
                                <th class="px-6 py-4 font-bold">Nombre</th>
                                <th class="px-6 py-4 font-bold">Email</th>
                                <th class="px-6 py-4 font-bold">Rol</th>
                                <th class="px-6 py-4 font-bold">Estado</th>
                                <th class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-if="usuarios.data.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-muted-foreground">
                                    No se encontraron usuarios
                                </td>
                            </tr>
                            <tr v-for="user in usuarios.data" :key="user.id" class="hover:bg-muted transition">

                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ user.name }}
                                </td>

                                <td class="px-6 py-4">{{ user.email }}</td>

                                <td class="px-6 py-4">
                                    <span v-if="user.is_alumno"
                                        class="text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded px-2 py-1">Alumno</span>
                                    <span v-else-if="user.is_tutor"
                                        class="text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded px-2 py-1">Tutor</span>
                                    <span v-else-if="user.is_propietario"
                                        class="text-sm bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded px-2 py-1">Propietario</span>
                                    <span v-else
                                        class="text-sm bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1">Usuario</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="user.estado === 'ACTIVO'"
                                        class="text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded px-2 py-1">Activo</span>
                                    <span v-else-if="user.estado === 'INACTIVO'"
                                        class="text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded px-2 py-1">Inactivo</span>
                                    <span v-else
                                        class="text-sm bg-gray-100 text-gray-800 dark:bg-gray-700/30 dark:text-gray-400 rounded px-2 py-1">Desconocido</span>

                                </td>

                                <td class="px-6 py-4 text-right space-x-3">
                                    <Button class="cursor-pointer hover:bg-muted/50" @click="openModal(user)">
                                        Ver Detalle
                                    </Button>

                                    <Link
                                        class="cursor-pointer bg-primary hover:bg-primary/80 text-primary-foreground px-4 py-2 rounded-lg transition"
                                        :href="route('users.edit', user.id)">
                                        Editar
                                    </Link>
                                    <Link as="button"
                                        method="delete"
                                        :onBefore="() => confirm('¿Estás seguro de que deseas eliminar este usuario?')"
                                         :href="route('users.destroy', user.id)"
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/60 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2">
                                        Eliminar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="usuarios.links && usuarios.links.length > 0"
                    class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
                    <Link v-for="link in usuarios.links" :key="link.url" :href="link.url || '#'" :class="[
                        'px-3 py-2 rounded-lg transition text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-card border border-border text-foreground hover:bg-muted',
                    ]" v-html="link.label" />
                </div>
            </div>





        </UserLayout>
        <div v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">

                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                    <h3 class="text-lg font-bold text-foreground">Detalle del Usuario</h3>
                    <button @click="closeModal"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-muted-foreground">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Nombre:</span>
                            <p class="font-medium text-foreground">{{ selectedUser?.name }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase font-bold text-muted-foreground">Email:</span>
                            <p class="font-medium text-foreground">{{ selectedUser?.email }}</p>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs uppercase font-bold text-muted-foreground">Rol Actual:</span>
                        <div class="mt-1">
                            <span v-if="selectedUser?.is_alumno"
                                class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded text-sm">Alumno</span>
                            <span v-if="selectedUser?.is_tutor"
                                class="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-1 rounded text-sm">Tutor</span>
                            <span v-if="selectedUser?.is_propietario"
                                class="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 px-2 py-1 rounded text-sm">Propietario</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-border">
                        <p class="text-xs text-muted-foreground italic">ID de Usuario: {{ selectedUser?.id }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
                    <!--  <Button>🔑 Resetear Clave</Button>
                    <Button>🔒 Bloquear</Button> -->
                    <button @click="closeModal"
                        class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
