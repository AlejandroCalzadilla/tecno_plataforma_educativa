<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import UserLayout from '@/layouts/UserLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { route } from 'ziggy-js';
import { ref } from 'vue';



const isModalOpen = ref(false);
const isFormModalOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const selectedCalendario = ref<Calendario | null>(null);
const horariosList = ref<Horario[]>([]);

const formData = ref({
    id_servicio: '',
    id_tutor: '',
    costo_base: '',
    cupos_MAX: '1',
    horarios: [] as number[],
});

const nuevoHorarioData = ref({
    dia_semana: 'LUNES',
    hora_inicio: '09:00',
    hora_fin: '11:00',
});

interface Calendario {
    id: number;
    id_servicio: number;
    id_tutor: number;
    costo_base: number;
    cupos_MAX: number;
    cupos_actual: number;
    created_at: string;
    servicio?: Servicio;
    tutor?: Tutor;
    horarios?: Horario[];
}

interface Servicio {
    id: number;
    nombre: string;
    modalidad: string;
}

interface Tutor {
    id: number;
    id_usuario: number;
    nombre?: string;
}

interface Horario {
    id: number;
    dia_semana: string;
    hora_inicio: string;
    hora_fin: string;
    estado_disponibilidad: boolean;
}

interface Pagination<T> {
    data: T[];
    links: any[];
    current_page: number;
}
const props = defineProps<{
    calendarios: Pagination<Calendario>;
    servicios: Servicio[];
    tutores: Tutor[];
    horarios: Horario[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: 'users',
    },
    {
        title: `calendarios`,
        href: '#',
    },
];

const submitForm = () => {
    if (formMode.value === 'create') {
        router.post(route('calendarios.store'), formData.value, {
            onSuccess: () => {
                closeFormModal();
            },
        });
    } else if (formMode.value === 'edit' && selectedCalendario.value) {
        router.put(route('calendarios.update', selectedCalendario.value.id), formData.value, {
            onSuccess: () => {
                closeFormModal();
            },
        });
    }
};


const closeFormModal = () => {
    isFormModalOpen.value = false;
    selectedCalendario.value = null;
};
const getServicioNombre = (id: number, servicios: Servicio[]) => {
    return servicios.find(s => s.id === id)?.nombre || 'N/A';
};

const getTutorNombre = (id: number, tutores: Tutor[]) => {
    return tutores.find(t => t.id === id)?.nombre || 'Tutor ' + id;
};

const getHorarioTexto = (horario: Horario) => {
    return `${horario.dia_semana} ${horario.hora_inicio} - ${horario.hora_fin}`;
};

/* const form = useForm({
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
    
}); */

/* const submit = () => {
    form.put(route('users.update', props.user.id));
}; */
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Crear Calendario`" />

        <UserLayout>
             <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">
                <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                   <!--  <h3 class="text-lg font-bold text-foreground">{{ formMode === 'create' ? 'Crear Calendario' : 'Editar Calendario' }}</h3>
                    --> <!-- <button @click="closeFormModal"
                        class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button> -->
                </div>

                <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Servicio *
                            </label>
                            <select v-model="formData.id_servicio"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                                <option value="">Seleccione un servicio</option>
                                <option v-for="servicio in servicios" :key="servicio.id" :value="servicio.id">
                                    {{ servicio.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Tutor *
                            </label>
                            <select v-model="formData.id_tutor"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required>
                                <option value="">Seleccione un tutor</option>
                                <option v-for="tutor in tutores" :key="tutor.id" :value="tutor.id">
                                    {{ getTutorNombre(tutor.id, tutores) }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Costo Base *
                            </label>
                            <input v-model="formData.costo_base" type="number" placeholder="0.00" step="0.01" min="0"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Cupos Máximos *
                            </label>
                            <input v-model="formData.cupos_MAX" type="number" placeholder="20" min="1"
                                class="w-full px-4 py-2 border border-border rounded-lg bg-card text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Seleccionar Horarios * (Mínimo 1)
                            </label>
                            <div class="flex gap-2 mb-2">
                               <!--  <button type="button" @click="openCreateHorarioModal"
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    ➕ Crear Horario
                                </button> -->
                            </div>
                            <div class="border border-border rounded-lg p-4 bg-card max-h-48 overflow-y-auto space-y-2">
                                <div v-if="horariosList.length === 0" class="text-sm text-muted-foreground text-center py-4">
                                    No hay horarios disponibles
                                </div>
                               <!--  <label v-for="horario in horariosList" :key="horario.id" class="flex items-center gap-3 cursor-pointer hover:bg-muted p-2 rounded">
                                    <input 
                                        type="checkbox" 
                                        :checked="formData.horarios.includes(horario.id)"
                                        @change="toggleHorario(horario.id)"
                                        class="w-4 h-4" />
                                    <span class="text-sm text-foreground">
                                        {{ getHorarioTexto(horario) }}
                                    </span>
                                </label> -->
                            </div>
                            <p v-if="formData.horarios.length === 0" class="text-xs text-destructive mt-2">
                                Debes seleccionar al menos un horario
                            </p>
                        </div>

                        <div class="flex gap-2 justify-end pt-4">
                            <button type="button" @click="closeFormModal"
                                class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-4 py-2 cursor-pointer bg-primary text-primary-foreground rounded-lg text-sm font-bold hover:bg-primary/80 transition"
                                :disabled="formData.horarios.length === 0">
                                {{ formMode === 'create' ? 'Crear' : 'Actualizar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>