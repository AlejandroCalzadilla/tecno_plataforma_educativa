<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination<T> {
    data: T[];
    links: PaginationLink[];
}

interface PagoItem {
    id: number;
    fecha_pago: string;
    monto_abonado: string;
    metodo_pago: string;
    alumno?: {
        usuario?: {
            name: string;
        };
    };
    cuota?: {
        numero_cuota: number;
        venta?: {
            id: number;
            fecha_emision: string;
            tipo_pago_pref: string;
            estado_financiero: string;
            inscripcion?: {
                calendario?: {
                    servicio?: {
                        nombre: string;
                    };
                };
            };
        };
    };
}

const props = defineProps<{
    pagos: Pagination<PagoItem>;
    ventaId?: number;
    titulo?: string;
    showBackButton?: boolean;
}>();

const formatCurrency = (value: string | number) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(Number(value || 0));
};
</script>

<template>
    <section class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-bold text-foreground">{{ titulo || 'Pagos Registrados' }}</h2>
            <div v-if="ventaId && showBackButton" class="flex items-center gap-2">
                <Link
                    :href="route('pagos.index')"
                    class="bg-secondary text-secondary-foreground hover:bg-secondary/80 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2"
                >
                    Ver todos los pagos
                </Link>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-muted-foreground">
                <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                    <tr>
                        <th class="px-6 py-4 font-bold">Fecha Pago</th>
                        <th class="px-6 py-4 font-bold">Alumno</th>
                        <th class="px-6 py-4 font-bold">Servicio</th>
                        <th class="px-6 py-4 font-bold">Venta ID</th>
                        <th class="px-6 py-4 font-bold">Cuota</th>
                        <th class="px-6 py-4 font-bold">Tipo Venta</th>
                        <th class="px-6 py-4 font-bold">Estado Venta</th>
                        <th class="px-6 py-4 font-bold">Método</th>
                        <th class="px-6 py-4 font-bold">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="props.pagos.data.length === 0">
                        <td colspan="9" class="px-6 py-8 text-center text-muted-foreground">
                            No hay pagos registrados.
                        </td>
                    </tr>
                    <tr v-for="pago in props.pagos.data" :key="pago.id" class="hover:bg-muted transition">
                        <td class="px-6 py-4">{{ pago.fecha_pago }}</td>
                        <td class="px-6 py-4 text-foreground">{{ pago.alumno?.usuario?.name ?? 'Sin alumno' }}</td>
                        <td class="px-6 py-4">{{ pago.cuota?.venta?.inscripcion?.calendario?.servicio?.nombre ?? 'Sin servicio' }}</td>
                        <td class="px-6 py-4">#{{ pago.cuota?.venta?.id ?? '-' }}</td>
                        <td class="px-6 py-4">#{{ pago.cuota?.numero_cuota ?? '-' }}</td>
                        <td class="px-6 py-4">{{ pago.cuota?.venta?.tipo_pago_pref ?? '-' }}</td>
                        <td class="px-6 py-4">{{ pago.cuota?.venta?.estado_financiero ?? '-' }}</td>
                        <td class="px-6 py-4">{{ pago.metodo_pago }}</td>
                        <td class="px-6 py-4">{{ formatCurrency(pago.monto_abonado) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.pagos.links.length > 0" class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
            <Link
                v-for="(link, index) in props.pagos.links"
                :key="`${link.label}-${index}`"
                :href="link.url || '#'"
                :class="[
                    'px-3 py-2 rounded-lg transition text-sm',
                    link.active ? 'bg-primary text-primary-foreground' : 'bg-card border border-border text-foreground hover:bg-muted',
                    !link.url && 'opacity-50 pointer-events-none',
                ]"
                v-html="link.label"
            />
        </div>
    </section>
</template>