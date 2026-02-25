<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import { route } from 'ziggy-js';
import QrPagoModal from '@/components/pagos/QrPagoModal.vue';

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
    cuota?: {
        numero_cuota: number;
        venta?: {
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

interface CuotaItem {
    id: number;
    numero_cuota: number;
    monto_cuota: string;
    fecha_vencimiento: string;
    estado_pago: 'PENDIENTE' | 'PAGADO' | 'VENCIDO';
    venta?: {
        inscripcion?: {
            calendario?: {
                servicio?: {
                    nombre: string;
                };
            };
        };
    };
}

const props = defineProps<{
    pagos: Pagination<PagoItem>;
    cuotas: Pagination<CuotaItem>;
    qrCuotaId?: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Pagos', href: '/pagos' },
];

const metodoPago = ref<'EFECTIVO' | 'QR' | 'TRANSFERENCIA' | 'TARJETA'>('QR');

// ── Modal QR ──────────────────────────────────────────────────────────
const cuotaEnModal = ref<CuotaItem | null>(null);

const abrirModalQr = (cuota: CuotaItem) => {
    cuotaEnModal.value = cuota;
};

const cerrarModalQr = () => {
    cuotaEnModal.value = null;
};

const onPagadoQr = () => {
    cuotaEnModal.value = null;
    router.reload();
};

// ── Pago de cuota ─────────────────────────────────────────────────────
const pagarCuota = (cuota: CuotaItem) => {
    if (cuota.estado_pago === 'PAGADO') return;

    if (metodoPago.value === 'QR') {
        abrirModalQr(cuota);
        return;
    }

    // Métodos no-QR: confirmación simple y POST al endpoint existente
    if (!window.confirm(`¿Confirmas el pago de la cuota #${cuota.numero_cuota} por ${formatCurrency(cuota.monto_cuota)}?`)) return;

    router.post(
        route('pagos.pagar-cuota', cuota.id),
        { metodo_pago: metodoPago.value },
    );
};

// Auto-abrir modal si venimos del flujo de inscripción con QR
onMounted(() => {
    if (props.qrCuotaId) {
        const cuota = props.cuotas.data.find((c) => c.id === props.qrCuotaId);
        if (cuota && cuota.estado_pago !== 'PAGADO') {
            abrirModalQr(cuota);
        }
    }
});

const formatCurrency = (value: string | number) =>
    new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number(value || 0));

const getNombreServicio = (cuota: CuotaItem) =>
    cuota.venta?.inscripcion?.calendario?.servicio?.nombre ?? 'Sin servicio';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mis pagos y cuotas" />

        <!-- Modal QR (teleport para que no herede clipping) -->
        <Teleport to="body">
            <QrPagoModal
                v-if="cuotaEnModal"
                :id-cuota="cuotaEnModal.id"
                :monto="cuotaEnModal.monto_cuota"
                :servicio-nombre="getNombreServicio(cuotaEnModal)"
                :numero-cuota="cuotaEnModal.numero_cuota"
                @pagado="onPagadoQr"
                @cerrar="cerrarModalQr"
            />
        </Teleport>

        <UserLayout>
            <div class="space-y-6">
                <section class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-foreground">Mis Cuotas</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Selecciona el método antes de pagar. Para QR se abrirá el código de pago.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-muted-foreground">Método de pago</label>
                            <select
                                v-model="metodoPago"
                                class="px-3 py-2 border border-border rounded-lg bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="QR">QR (PagoFácil)</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA">Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-muted-foreground">
                            <thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Servicio</th>
                                    <th class="px-6 py-4 font-bold">Cuota</th>
                                    <th class="px-6 py-4 font-bold">Vencimiento</th>
                                    <th class="px-6 py-4 font-bold">Monto</th>
                                    <th class="px-6 py-4 font-bold">Estado</th>
                                    <th class="px-6 py-4 text-right font-bold">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="props.cuotas.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                        No tienes cuotas registradas.
                                    </td>
                                </tr>
                                <tr v-for="cuota in props.cuotas.data" :key="cuota.id" class="hover:bg-muted/50 transition">
                                    <td class="px-6 py-4 text-foreground font-medium">
                                        {{ getNombreServicio(cuota) }}
                                    </td>
                                    <td class="px-6 py-4">#{{ cuota.numero_cuota }}</td>
                                    <td class="px-6 py-4">{{ cuota.fecha_vencimiento }}</td>
                                    <td class="px-6 py-4 font-semibold">{{ formatCurrency(cuota.monto_cuota) }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="cuota.estado_pago === 'PAGADO'"
                                            class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full px-2.5 py-1 font-medium"
                                        >
                                            ✓ Pagado
                                        </span>
                                        <span
                                            v-else-if="cuota.estado_pago === 'VENCIDO'"
                                            class="inline-flex items-center gap-1 text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full px-2.5 py-1 font-medium"
                                        >
                                            ✕ Vencido
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full px-2.5 py-1 font-medium"
                                        >
                                            ◷ Pendiente
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <!-- Botón QR -->
                                        <button
                                            v-if="metodoPago === 'QR'"
                                            :disabled="cuota.estado_pago === 'PAGADO'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                                            @click="pagarCuota(cuota)"
                                        >
                                            <span>📱</span> Pagar con QR
                                        </button>
                                        <!-- Botón otros métodos -->
                                        <button
                                            v-else
                                            :disabled="cuota.estado_pago === 'PAGADO'"
                                            class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                                            @click="pagarCuota(cuota)"
                                        >
                                            Pagar cuota
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="props.cuotas.links.length > 3" class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
                        <Link
                            v-for="(link, index) in props.cuotas.links"
                            :key="`${link.label}-${index}`"
                            :href="link.url ?? '#'"
                            :class="[
                                'px-3 py-2 rounded-lg transition text-sm',
                                link.active ? 'bg-primary text-primary-foreground' : 'bg-card border border-border text-foreground hover:bg-muted',
                                !link.url && 'opacity-50 pointer-events-none',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </section>
            </div>
        </UserLayout>
    </AppLayout>
</template>
