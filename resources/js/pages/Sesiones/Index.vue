<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { CalendarRange, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';

// 1. Interfaces
interface Session {
    id: number;
    fecha_sesion: string; // Formato YYYY-MM-DD
    hora_inicio: string;
    hora_fin: string;
    link_virtual?: string;
    estado_asistencia?: string;
}

interface CalendarDay {
    date: Date;
    day: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    sessions: Session[];
}

// 2. Definición de Props (Lo que viene del controlador)
const props = defineProps < {
    sessions: Session[];
} > ();

// 3. Estados Reactivos
const currentDate = ref < Date > (new Date());
const selectedDay = ref < Date | null > (null);

// 4. Computados para el Calendario
const currentMonthName = computed(() => {
    return currentDate.value.toLocaleDateString('es-ES', { month: 'long' });
});

const currentYear = computed(() => currentDate.value.getFullYear());

const calendarDays = computed((): CalendarDay[] => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDay = new Date(year, month, 1);
    // Buscamos el último día del mes
    const lastDay = new Date(year, month + 1, 0);

    // Ajuste para que empiece en el domingo de la semana del primer día
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    const days: CalendarDay[] = [];
    const d = new Date(startDate);
    console.log('Generando calendario para:', props.sessions);
    // Generamos exactamente 6 semanas (42 días) para un diseño estable
    while (days.length < 42) {
        // Filtrar sesiones que correspondan a este día 'd'
        const daySessions = props.sessions.filter(session => {
            const sDate = new Date(session.fecha_sesion + 'T00:00:00'); // Forzar hora local
            return sDate.getFullYear() === d.getFullYear() &&
                sDate.getMonth() === d.getMonth() &&
                sDate.getDate() === d.getDate();
        });

        days.push({
            date: new Date(d),
            day: d.getDate(),
            isCurrentMonth: d.getMonth() === month,
            isToday: d.toDateString() === new Date().toDateString(),
            sessions: daySessions
        });

        d.setDate(d.getDate() + 1);
    }
    return days;
});

// Sesiones del día que el usuario toque en el calendario
const selectedDaySessions = computed((): Session[] => {
    if (!selectedDay.value) return [];
    const target = selectedDay.value.toDateString();
    return props.sessions.filter(session => {
        return new Date(session.fecha_sesion   + 'T00:00:00').toDateString() === target;
    });
});

// 5. Métodos de Navegación
const previousMonth = (): void => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
    selectedDay.value = null;
};

const nextMonth = (): void => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
    selectedDay.value = null;
};

const selectDay = (day: CalendarDay): void => {
    selectedDay.value = day.date;
};

const viewSession = (id: number): void => {
    router.get(route('sesiones.show', id));
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-2">
                    <CalendarRange class="w-6 h-6 text-primary" />
                    <h2 class="text-2xl font-bold capitalize">{{ currentMonthName }} {{ currentYear }}</h2>
                </div>
                <div class="flex gap-2">
                    <button @click="previousMonth" class="p-2 hover:bg-muted rounded-full border border-border">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <button @click="nextMonth" class="p-2 hover:bg-muted rounded-full border border-border">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-px bg-border border border-border rounded-xl overflow-hidden shadow-sm">
                <div v-for="d in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" :key="d"
                    class="bg-muted p-4 text-center text-sm font-bold text-muted-foreground uppercase tracking-wider">
                    {{ d }}
                </div>

                <div v-for="day in calendarDays" :key="day.date.toISOString()" @click="selectDay(day)"
                    class="min-h-[120px] p-2 transition-colors cursor-pointer bg-card hover:bg-muted/50" :class="[
                        !day.isCurrentMonth ? 'opacity-40 bg-muted/20' : '',
                        selectedDay?.toDateString() === day.date.toDateString() ? 'ring-2 ring-primary ring-inset z-10' : ''
                    ]">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium"
                            :class="day.isToday ? 'bg-primary text-primary-foreground w-7 h-7 flex items-center justify-center rounded-full' : ''">
                            {{ day.day }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <div v-for="session in day.sessions.slice(0, 3)" :key="session.id"
                            class="text-[10px] p-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 truncate font-medium">
                            {{ session.hora_inicio }} - {{ 'Sesión' }}
                        </div>
                        <div v-if="day.sessions.length > 3" class="text-[9px] text-muted-foreground text-center">
                            + {{ day.sessions.length - 3 }} más
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="selectedDay" class="mt-8 p-6 bg-card border border-border rounded-xl shadow-sm">
                <h3 class="text-lg font-bold mb-4">Sesiones para el {{ selectedDay.toLocaleDateString('es-ES', {
                    weekday: 'long', day: 'numeric', month: 'long' }) }}</h3>
                <div v-if="selectedDaySessions.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="session in selectedDaySessions" :key="session.id"
                        class="p-4 border border-border rounded-lg bg-muted/30 flex justify-between items-center">
                        <div>
                            <p class="font-bold">{{ 'Sesión Programada' }}</p>
                            <p class="text-sm text-muted-foreground">{{ session.hora_inicio }} - {{ session.hora_fin }}
                            </p>
                        </div>
                        <button @click="viewSession(session.id)"
                            class="px-3 py-1 bg-primary text-primary-foreground rounded text-sm hover:opacity-90">
                            Ver Detalle
                        </button>
                    </div>
                </div>
                <p v-else class="text-muted-foreground italic text-center py-4">No hay sesiones programadas para este
                    día.</p>
            </div>
        </div>
    </AppLayout>
</template>