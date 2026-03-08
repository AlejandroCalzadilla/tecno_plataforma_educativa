<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps<{
    idCuota: number;
    monto: string | number;
    servicioNombre: string;
    numeroCuota: number;
}>();

const emit = defineEmits<{
    pagado: [];
    cerrar: [];
}>();

type Estado = 'cargando' | 'listo' | 'esperando' | 'pagado' | 'error';

const estado        = ref<Estado>('cargando');
const qrImage       = ref('');
const transactionId = ref('');
const nroPago       = ref('');
const mensajeError  = ref('');

let pollingInterval: ReturnType<typeof setInterval> | null = null;

// ── HTTP helper ──────────────────────────────────────────────────────
// axios ya viene configurado por Inertia/Laravel con el cookie XSRF-TOKEN,
// lo cual maneja el CSRF automáticamente sin necesidad de meta tags.
const apiFetch = async (url: string, body: Record<string, unknown>) => {
    const res = await axios.post(url, body);
    return res.data;
};

// ── Flujo QR ─────────────────────────────────────────────────────────
const generarQR = async () => {
    estado.value       = 'cargando';
    mensajeError.value = '';

    try {
        const data = await apiFetch(route('pagofacil.generar-qr'), { id_cuota: props.idCuota });

        if (!data.success) throw new Error(data.message ?? 'Error al generar QR');

        qrImage.value       = data.qr_image;
        transactionId.value = data.transaction_id;
        nroPago.value       = data.nro_pago;
        estado.value        = 'listo';

        // Sólo iniciar polling si recibimos un transactionId válido
        if (!transactionId.value) {
            mensajeError.value = 'No se recibió ID de transacción de PagoFácil. Revisa los logs del servidor.';
            estado.value = 'error';
            return;
        }

        iniciarPolling();
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } }; message?: string })
            ?.response?.data?.message ?? (e instanceof Error ? e.message : 'Error desconocido');
        mensajeError.value = msg;
        estado.value       = 'error';
    }
};

const iniciarPolling = () => {
    pollingInterval = setInterval(async () => {
        try {
            // Verificar si el callback de PagoFácil ya marcó la cuota como PAGADO en nuestra BD
            const data = await apiFetch(route('pagofacil.verificar-cuota'), { id_cuota: props.idCuota });
            if (data.success && data.pagada) {
                detenerPolling();
                estado.value = 'pagado';
                setTimeout(() => emit('pagado'), 600);
            }
        } catch {
            // errores de red → ignorar, reintentar en siguiente tick
        }
    }, 3000);
};

const confirmarPago = async () => {
    estado.value = 'esperando';

    try {
        const data = await apiFetch(route('pagofacil.confirmar'), {
            id_cuota:       props.idCuota,
            transaction_id: transactionId.value,
            nro_pago:       nroPago.value,
        });

        if (data.success) {
            estado.value = 'pagado';
            setTimeout(() => emit('pagado'), 1800);
        } else {
            throw new Error(data.message ?? 'Error al confirmar pago');
        }
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } }; message?: string })
            ?.response?.data?.message ?? (e instanceof Error ? e.message : 'Error al confirmar pago');
        mensajeError.value = msg;
        estado.value       = 'error';
    }
};

const detenerPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

const cerrar = () => {
    detenerPolling();
    emit('cerrar');
};

const reintentar = () => {
    detenerPolling();
    generarQR();
};

const formatCurrency = (v: string | number) =>
    new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number(v || 0));

onMounted(generarQR);
onUnmounted(detenerPolling);
</script>

<template>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="cerrar"
    >
        <!-- Modal -->
        <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card shadow-2xl">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-border px-5 py-4">
                <div>
                    <h2 class="text-base font-bold text-foreground">Pago con QR</h2>
                    <p class="text-xs text-muted-foreground">{{ servicioNombre }} — Cuota #{{ numeroCuota }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    @click="cerrar"
                >
                    ✕
                </button>
            </div>

            <div class="p-6">

                <!-- ── Cargando ── -->
                <div v-if="estado === 'cargando'" class="flex flex-col items-center gap-4 py-8">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-primary border-t-transparent" />
                    <p class="text-sm text-muted-foreground">Generando QR…</p>
                </div>

                <!-- ── QR listo / esperando pago ── -->
                <div v-else-if="estado === 'listo' || estado === 'esperando'" class="flex flex-col items-center gap-4">
                    <!-- Monto -->
                    <div class="w-full rounded-xl bg-muted/50 px-4 py-3 text-center">
                        <p class="text-xs text-muted-foreground">Monto a pagar</p>
                        <p class="text-2xl font-bold text-foreground">{{ formatCurrency(monto) }}</p>
                    </div>

                    <!-- QR image -->
                    <div class="rounded-xl border border-border bg-white p-3 shadow-sm">
                        <img :src="qrImage" alt="Código QR de pago" class="h-48 w-48" />
                    </div>

                    <!-- Estado de polling -->
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span
                            v-if="estado === 'esperando'"
                            class="h-2.5 w-2.5 animate-bounce rounded-full bg-amber-400"
                        />
                        <span
                            v-else
                            class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400"
                        />
                        <span>{{ estado === 'esperando' ? 'Confirmando pago…' : 'Esperando pago…' }}</span>
                    </div>

                    <p class="text-center text-xs text-muted-foreground">
                        Escanea el QR con tu app de pago.<br />
                        La página se actualizará automáticamente.
                    </p>
                </div>

                <!-- ── Pago confirmado ── -->
                <div v-else-if="estado === 'pagado'" class="flex flex-col items-center gap-3 py-6">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl dark:bg-emerald-900/40">
                        ✓
                    </div>
                    <p class="text-lg font-bold text-emerald-600">¡Pago confirmado!</p>
                    <p class="text-sm text-muted-foreground">La cuota quedó registrada correctamente.</p>
                </div>

                <!-- ── Error ── -->
                <div v-else-if="estado === 'error'" class="flex flex-col items-center gap-4 py-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-2xl dark:bg-red-900/40">
                        ✕
                    </div>
                    <p class="text-sm font-semibold text-red-600">Error</p>
                    <p class="text-center text-xs text-muted-foreground">{{ mensajeError }}</p>
                    <button
                        type="button"
                        class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                        @click="reintentar"
                    >
                        Reintentar
                    </button>
                </div>

            </div>

            <!-- Footer -->
            <div
                v-if="estado === 'listo' || estado === 'esperando'"
                class="border-t border-border px-5 py-3 text-center"
            >
                <button
                    type="button"
                    class="text-xs text-muted-foreground underline-offset-2 hover:underline"
                    @click="cerrar"
                >
                    Cancelar y pagar después
                </button>
            </div>
        </div>
    </div>
</template>
