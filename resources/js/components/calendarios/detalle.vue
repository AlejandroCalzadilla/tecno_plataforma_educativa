<script setup lang="ts">


defineProps<{
    show: boolean;
    selectedCalendario: Calendario | null;
    tutores: Tutor[];
    servicios: Servicio[];

}>();
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
defineEmits(['close']);

const getServicioNombre = (id: number, servicios: Servicio[]) => {
    return servicios.find(s => s.id === id)?.nombre || 'N/A';
};
const getTutorNombre = (id: number, tutores: Tutor[]) => {
    return tutores.find(t => t.id === id)?.nombre || 'Tutor ' + id;
};
const getHorarioTexto = (horario: Horario) => {
    return `${horario.dia_semana} ${horario.hora_inicio} - ${horario.hora_fin}`;
};
</script>

<template>

    <div v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-lg">
            <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
                <h3 class="text-lg font-bold text-foreground">Detalle del Calendario</h3>
                <button @click="$emit('close')"
                    class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
            </div>
            <div class="p-6 space-y-4 text-muted-foreground max-h-96 overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs uppercase font-bold text-muted-foreground">Servicio:</span>
                        <p class="font-medium text-foreground">{{ getServicioNombre(selectedCalendario?.id_servicio
                            || 0, servicios) }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-bold text-muted-foreground">Tutor:</span>
                        <p class="font-medium text-foreground">{{ getTutorNombre(selectedCalendario?.id_tutor || 0,
                            tutores) }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-bold text-muted-foreground">Costo Base:</span>
                        <p class="font-medium text-foreground">${{ selectedCalendario?.costo_base.toFixed(2) }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-bold text-muted-foreground">Cupos:</span>
                        <p class="font-medium text-foreground">{{ selectedCalendario?.cupos_actual }} / {{
                            selectedCalendario?.cupos_MAX }}</p>
                    </div>
                </div>

                <div v-if="selectedCalendario?.horarios && selectedCalendario.horarios.length > 0">
                    <span class="text-xs uppercase font-bold text-muted-foreground">Horarios:</span>
                    <div class="mt-2 space-y-1">
                        <p v-for="horario in selectedCalendario.horarios" :key="horario.id"
                            class="text-sm text-foreground">
                            • {{ getHorarioTexto(horario) }}
                        </p>
                    </div>
                </div>
                <div class="pt-4 border-t border-border">
                    <p class="text-xs text-muted-foreground italic">ID: {{ selectedCalendario?.id }}</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
                <button @click="$emit('close')"
                    class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
            </div>
        </div>
    </div>

</template>