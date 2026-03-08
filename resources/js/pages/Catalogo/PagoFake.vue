<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref, onUnmounted } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Tutor {
    id: number;
    usuario?: {
        id: number;
        name: string;
    };
}

interface Servicio {
    id: number;
    nombre: string;
}

interface Calendario {
    id: number;
    tipo_programacion: 'CITA_LIBRE' | 'PAQUETE_FIJO';
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    tutor?: Tutor;
    servicio?: Servicio;
}

interface SesionPreview {
    numero_sesion: number;
    fecha_sesion: string;
    dia_semana: string;
    fecha_hora_inicio: string;
    fecha_hora_fin: string;
    estado_asistencia: string;
}

interface CuotaPreview {
    numero_cuota: number;
    monto_cuota: string;
    fecha_vencimiento: string;
}

interface Params {
    id_alumno: string | number;
    fecha_inicio: string;
    tipo_pago_pref: 'CONTADO' | 'CUOTAS';
    cantidad_cuotas: number;
    metodo_pago: 'EFECTIVO' | 'QR' | 'TRANSFERENCIA' | 'TARJETA';
}

const props = defineProps<{
    calendario: Calendario;
    sesionesProgramadas: SesionPreview[];
    cuotasPreview: CuotaPreview[];
    params: Params;
}>();

const form = useForm({
    id_alumno: String(props.params.id_alumno ?? ''),
    fecha_inicio: props.params.fecha_inicio,
    tipo_pago_pref: props.params.tipo_pago_pref,
    cantidad_cuotas: props.params.cantidad_cuotas,
    metodo_pago: props.params.metodo_pago,
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Catálogo', href: '/catalogo' },
    { title: props.calendario.servicio?.nombre ?? 'Servicio', href: `/catalogo/servicios/${props.calendario.servicio?.id}` },
    { title: 'Pago', href: `/catalogo/calendarios/${props.calendario.id}/pago` },
];

const tutorLabel       = computed(() => props.calendario.tutor?.usuario?.name ?? 'Tutor sin nombre');
const esMetodoQR       = computed(() => form.metodo_pago === 'QR');
const esCuotas         = computed(() => form.tipo_pago_pref === 'CUOTAS');
const primeraCuota     = computed(() => props.cuotasPreview[0] ?? null);
const montoCobrarAhora = computed(() => primeraCuota.value?.monto_cuota ?? String(props.calendario.costo_total));

const formatMonto = (v: string | number) =>
    new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(Number(v || 0));

// ── Estado QR inline ────────────────────────────────────────────────
type QrEstado = 'generando' | 'listo' | 'pagado' | 'error';

const qrEstado     = ref<QrEstado | null>(null); // null = mostrando formulario
const qrImage      = ref('');
const qrIdCuota    = ref<number | null>(null);
const qrMonto      = ref('');
const qrError      = ref('');
let   pollingTimer: ReturnType<typeof setInterval> | null = null;

const detenerPolling = () => {
    if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null; }
};

const iniciarPolling = (idCuota: number) => {
    pollingTimer = setInterval(async () => {
        try {
            const { data } = await axios.post(route('pagofacil.verificar-cuota'), { id_cuota: idCuota });
            if (data.success && data.pagada) {
                detenerPolling();
                qrEstado.value = 'pagado';
                setTimeout(() => router.visit(route('pagos.index')), 1800);
            }
        } catch { /* red → reintentar */ }
    }, 3000);
};

const recalcularPago = () => {
    form.get(route('catalogo.inscripcion.pago', props.calendario.id), {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmarPago = async () => {
    if (esMetodoQR.value) {
        // Paso 1: crear inscripción vía axios (el servidor devuelve JSON con id_cuota)
        qrEstado.value = 'generando';
        qrError.value  = '';
        try {
            const { data: ins } = await axios.post(
                route('catalogo.inscripcion.confirmar-pago', props.calendario.id),
                {
                   // id_alumno:       form.id_alumno,
                    fecha_inicio:    form.fecha_inicio,
                    tipo_pago_pref:  form.tipo_pago_pref,
                    cantidad_cuotas: form.cantidad_cuotas,
                    metodo_pago:     form.metodo_pago,
                },
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
            if (!ins.success) throw new Error(ins.message ?? 'Error al crear inscripción');

            qrIdCuota.value = ins.id_cuota;
            qrMonto.value   = ins.monto;

            // Paso 2: generar QR con PagoFácil
            const { data: qr } = await axios.post(route('pagofacil.generar-qr'), { id_cuota: ins.id_cuota });
            if (!qr.success) throw new Error(qr.message ?? 'Error al generar QR');

            qrImage.value  = qr.qr_image;
            qrEstado.value = 'listo';
            iniciarPolling(ins.id_cuota);
        } catch (e: unknown) {
            qrError.value  = (e as { response?: { data?: { message?: string } }; message?: string })
                ?.response?.data?.message ?? (e instanceof Error ? e.message : 'Error desconocido');
            qrEstado.value = 'error';
        }
    } else {
        // No QR: Inertia form normal
        form.post(route('catalogo.inscripcion.confirmar-pago', props.calendario.id));
    }
};

const volverAlFormulario = () => {
    detenerPolling();
    qrEstado.value = null;
    qrImage.value  = '';
    qrIdCuota.value = null;
};

onUnmounted(detenerPolling);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Confirmar inscripción" />
        <UserLayout>
            <div class="space-y-5">

                <!-- ══ SECCIÓN QR INLINE ══ -->
                <template v-if="qrEstado !== null">

                    <!-- Generando -->
                    <div v-if="qrEstado === 'generando'" class="bg-card border border-border rounded-xl p-10 flex flex-col items-center gap-4">
                        <div class="h-10 w-10 animate-spin rounded-full border-4 border-primary border-t-transparent" />
                        <p class="text-sm text-muted-foreground">Creando inscripción y generando QR…</p>
                    </div>

                    <!-- QR listo -->
                    <div v-else-if="qrEstado === 'listo'" class="bg-card border border-border rounded-xl p-6 flex flex-col items-center gap-5">
                        <div>
                            <h2 class="text-lg font-bold text-foreground text-center">Escanea para pagar</h2>
                            <p class="text-sm text-muted-foreground text-center mt-1">
                                {{ calendario.servicio?.nombre }} — Cuota 1
                            </p>
                        </div>

                        <div class="rounded-xl border border-border bg-muted/40 px-6 py-3 text-center">
                            <p class="text-xs text-muted-foreground">Monto a pagar</p>
                            <p class="text-3xl font-bold text-foreground">Bs. {{ formatMonto(qrMonto) }}</p>
                        </div>

                        <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                            <img :src="qrImage" alt="Código QR de pago" class="h-56 w-56" />
                        </div>

                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400" />
                            <span>Esperando confirmación de pago…</span>
                        </div>

                        <p class="text-center text-xs text-muted-foreground">
                            Escanea el código con tu app de billetera móvil.<br />
                            La página se actualizará automáticamente al confirmar.
                        </p>

                        <button type="button" class="text-xs text-muted-foreground underline-offset-2 hover:underline" @click="volverAlFormulario">
                            Cancelar y pagar después
                        </button>
                    </div>

                    <!-- Pago confirmado -->
                    <div v-else-if="qrEstado === 'pagado'" class="bg-card border border-border rounded-xl p-10 flex flex-col items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl dark:bg-emerald-900/40">✓</div>
                        <p class="text-lg font-bold text-emerald-600">¡Pago confirmado!</p>
                        <p class="text-sm text-muted-foreground">Redirigiendo a tus pagos…</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="qrEstado === 'error'" class="bg-card border border-border rounded-xl p-8 flex flex-col items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-2xl dark:bg-red-900/40">✕</div>
                        <p class="font-semibold text-red-600">Error al procesar</p>
                        <p class="text-center text-sm text-muted-foreground">{{ qrError }}</p>
                        <button
                            type="button"
                            class="rounded-lg bg-secondary px-5 py-2 text-sm font-medium transition hover:opacity-90"
                            @click="volverAlFormulario"
                        >
                            Volver al formulario
                        </button>
                    </div>

                </template>

                <!-- ══ FORMULARIO NORMAL ══ -->
                <template v-else>
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">Confirmar inscripción</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Revisa los datos, elige el tipo de pago y el método antes de confirmar.
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>Servicio:</strong> {{ calendario.servicio?.nombre }}</p>
                    <p><strong>Tutor:</strong> {{ tutorLabel }}</p>
                    <p><strong>Tipo:</strong> {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? 'Paquete' : 'Cita única' }}</p>
                    <p><strong>Total:</strong> ${{ calendario.costo_total }}</p>
                </div>

                <form class="bg-card border border-border rounded-xl p-5 space-y-4" @submit.prevent="confirmarPago">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                       <!--  <div>
                            <label class="block text-sm mb-1">ID alumno *</label>
                            <input v-model="form.id_alumno" type="number" min="1" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.id_alumno" class="text-sm text-red-600 mt-1">{{ form.errors.id_alumno }}</p>
                        </div> -->

                       <!--  <div>
                            <label class="block text-sm mb-1">Fecha inicio sesiones *</label>
                            <input v-model="form.fecha_inicio" type="date" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">{{ form.errors.fecha_inicio }}</p>
                        </div> -->

                        <div>
                            <label class="block text-sm mb-1">Tipo de pago</label>
                            <select v-model="form.tipo_pago_pref" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="CONTADO">Contado (pago único)</option>
                                <option value="CUOTAS">Cuotas mensuales</option>
                            </select>
                        </div>

                        <div v-if="esCuotas">
                            <label class="block text-sm mb-1">Cantidad de cuotas</label>
                            <input v-model="form.cantidad_cuotas" type="number" min="2" max="24" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                            <p v-if="form.errors.cantidad_cuotas" class="text-sm text-red-600 mt-1">{{ form.errors.cantidad_cuotas }}</p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Método</label>
                            <select v-model="form.metodo_pago" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                                <option value="QR">QR (PagoFácil)</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA">Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <!-- Resumen de cobro -->
                    <div class="rounded-xl border border-border bg-muted/40 p-4">
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-2">Lo que se cobrará al confirmar</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-foreground">Bs. {{ formatMonto(montoCobrarAhora) }}</span>
                            <span v-if="esCuotas" class="text-sm text-muted-foreground">
                                (1ª cuota de {{ form.cantidad_cuotas }})
                            </span>
                            <span v-else class="text-sm text-muted-foreground">(pago total)</span>
                        </div>
                        <p v-if="esCuotas" class="text-xs text-muted-foreground mt-1">
                            Las cuotas restantes se pagarán mensualmente desde tu sección de pagos.
                        </p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Total del curso: <strong>Bs. {{ formatMonto(calendario.costo_total) }}</strong>
                        </p>
                    </div>

                    <!-- Banner informativo QR -->
                    <div
                        v-if="esMetodoQR"
                        class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-800 p-3 text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2"
                    >
                        <span class="text-base leading-none mt-0.5">📱</span>
                        <div>
                            <p class="font-medium">Pago con QR (PagoFácil)</p>
                            <p class="mt-0.5 text-blue-600 dark:text-blue-400">
                                Se generará un código QR para pagar
                                <strong>Bs. {{ formatMonto(montoCobrarAhora) }}</strong>
                                <template v-if="esCuotas"> (primera cuota)</template>.
                                Serás redirigido automáticamente a la sección de pagos.
                            </p>
                        </div>
                    </div>

                    <!-- Banner informativo otros métodos -->
                    <div
                        v-else
                        class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800 p-3 text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2"
                    >
                        <span class="text-base leading-none mt-0.5">ℹ️</span>
                        <p>
                            Se registrará la inscripción con pago por
                            <strong>{{ form.metodo_pago.toLowerCase() }}</strong>.
                            <template v-if="esCuotas">
                                La primera cuota de <strong>Bs. {{ formatMonto(montoCobrarAhora) }}</strong> quedará pendiente de cobro manual.
                            </template>
                            <template v-else>
                                El pago total de <strong>Bs. {{ formatMonto(montoCobrarAhora) }}</strong> quedará pendiente de cobro manual.
                            </template>
                        </p>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button type="button" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="recalcularPago">
                            Recalcular vista
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-lg text-white font-medium transition-colors"
                            :class="esMetodoQR ? 'bg-blue-600 hover:bg-blue-700' : 'bg-primary hover:bg-primary/90'"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Procesando…</span>
                            <span v-else-if="esMetodoQR">
                                📱 Inscribirse y pagar {{ esCuotas ? '1ª cuota' : 'al contado' }} con QR
                            </span>
                            <span v-else>Confirmar inscripción</span>
                        </button>
                    </div>
                </form>

                <!-- Plan de cuotas: siempre visible para que el alumno vea el desglose -->
                <div class="bg-card border border-border rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">
                            {{ esCuotas ? 'Plan de cuotas' : 'Resumen de pago' }}
                        </h2>
                        <button
                            type="button"
                            class="text-xs px-3 py-1 bg-secondary text-secondary-foreground rounded-lg"
                            @click="recalcularPago"
                        >
                            Recalcular
                        </button>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2">Cuota</th>
                                <th class="py-2">Monto</th>
                                <th class="py-2">Vencimiento</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cuota in cuotasPreview"
                                :key="cuota.numero_cuota"
                                class="border-b border-border/60"
                                :class="cuota.numero_cuota === 1 ? 'bg-primary/5' : ''"
                            >
                                <td class="py-2">{{ cuota.numero_cuota }}</td>
                                <td class="py-2 font-medium">Bs. {{ formatMonto(cuota.monto_cuota) }}</td>
                                <td class="py-2 text-muted-foreground">{{ cuota.fecha_vencimiento }}</td>
                                <td class="py-2">
                                    <span
                                        v-if="cuota.numero_cuota === 1"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                    >
                                        Se cobra ahora
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="py-2" colspan="1">Total</td>
                                <td class="py-2" colspan="3">Bs. {{ formatMonto(calendario.costo_total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="bg-card border border-border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Sesiones previstas</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2 pr-3">#</th>
                                <th class="py-2 pr-3">Fecha</th>
                                <th class="py-2 pr-3">Día</th>
                                <th class="py-2 pr-3">Inicio</th>
                                <th class="py-2 pr-3">Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sesion in sesionesProgramadas" :key="sesion.numero_sesion" class="border-b border-border/60">
                                <td class="py-2 pr-3">{{ sesion.numero_sesion }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_sesion }}</td>
                                <td class="py-2 pr-3">{{ sesion.dia_semana }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_hora_inicio }}</td>
                                <td class="py-2 pr-3">{{ sesion.fecha_hora_fin }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-2">
                    <Link :href="route('catalogo.inscripcion.preview', calendario.id)" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">
                        Volver al preview
                    </Link>
                </div>

                </template>
            </div>
        </UserLayout>
    </AppLayout>
</template>
