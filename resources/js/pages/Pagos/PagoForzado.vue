<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import QrPagoModal from '@/components/pagos/QrPagoModal.vue';
import { type BreadcrumbItem } from '@/types';

interface CuotaItem {
    id: number;
    numero_cuota: number;
    monto_cuota: string;
    fecha_vencimiento: string;
    estado_pago: 'PENDIENTE' | 'PAGADO' | 'VENCIDO';
    venta?: {
        inscripcion?: {
            calendario?: {
                servicio?: { nombre: string };
            };
        };
    };
}

const props = defineProps<{ cuotas: CuotaItem[] }>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Pagar cuotas vencidas', href: '/pagos/cuotas-vencidas' }];

const cuotaEnModal = ref<CuotaItem | null>(null);

const getNombreServicio = (c: CuotaItem) =>
    c.venta?.inscripcion?.calendario?.servicio?.nombre ?? 'Servicio';

const formatCurrency = (v: string | number) =>
    new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number(v || 0));

const onPagadoQr = () => {
    cuotaEnModal.value = null;
    // Recarga completa: el controller redirige si ya no hay vencidas
    router.reload();
};

const totalVencido = props.cuotas.reduce((sum, c) => sum + Number(c.monto_cuota), 0);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Cuotas vencidas — Pago requerido" />

        <!-- Modal QR -->
        <Teleport to="body">
            <QrPagoModal
                v-if="cuotaEnModal"
                :id-cuota="cuotaEnModal.id"
                :monto="cuotaEnModal.monto_cuota"
                :servicio-nombre="getNombreServicio(cuotaEnModal)"
                :numero-cuota="cuotaEnModal.numero_cuota"
                @pagado="onPagadoQr"
                @cerrar="cuotaEnModal = null"
            />
        </Teleport>

        <UserLayout>
            <div class="max-w-2xl mx-auto space-y-5">

                <!-- Banner de alerta -->
                <div class="rounded-2xl border border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/40 p-5">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0 text-red-500 dark:text-red-400">
                            <!-- Ícono advertencia -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-red-700 dark:text-red-300">
                                Tienes cuotas vencidas
                            </h1>
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                Para continuar usando la plataforma debes regularizar los pagos pendientes.
                                Por favor abona las cuotas vencidas a continuación.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lista de cuotas vencidas -->
                <div class="bg-card border border-border rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h2 class="font-semibold">Cuotas a pagar</h2>
                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                            Total: {{ formatCurrency(totalVencido) }}
                        </span>
                    </div>

                    <ul class="divide-y divide-border">
                        <li
                            v-for="cuota in cuotas"
                            :key="cuota.id"
                            class="flex items-center justify-between px-5 py-4 gap-4"
                        >
                            <div class="min-w-0">
                                <p class="font-medium text-sm truncate">{{ getNombreServicio(cuota) }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Cuota #{{ cuota.numero_cuota }} · Venció {{ cuota.fecha_vencimiento }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="font-semibold text-sm">{{ formatCurrency(cuota.monto_cuota) }}</span>
                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 px-2 py-0.5 rounded-full font-medium">
                                    VENCIDO
                                </span>
                                <button
                                    class="px-4 py-1.5 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
                                    @click="cuotaEnModal = cuota"
                                >
                                    Pagar QR
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-center text-muted-foreground">
                    Una vez pagadas todas las cuotas vencidas podrás seguir navegando normalmente.
                </p>
            </div>
        </UserLayout>
    </AppLayout>
</template>
