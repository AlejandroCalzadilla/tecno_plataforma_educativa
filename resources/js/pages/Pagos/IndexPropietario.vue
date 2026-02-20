<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';
import PagosRegistrados from '@/components/pagos/PagosRegistrados.vue';
import { route } from 'ziggy-js';
import { ref } from 'vue';

interface PaginationLink {
	url: string | null;
	label: string;
	active: boolean;
}

interface Pagination<T> {
	data: T[];
	links: PaginationLink[];
}

interface VentaItem {
	id: number;
	fecha_emision: string;
	monto_total: string;
	saldo_pendiente: string;
	tipo_pago_pref: string;
	estado_financiero: string;
	inscripcion?: {
		alumno?: {
			usuario?: {
				name: string;
			};
		};
		calendario?: {
			servicio?: {
				nombre: string;
			};
		};
	};
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
	ventas: Pagination<VentaItem>;
	pagos: Pagination<PagoItem>;
	ventaId?: number;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
	{
		title: 'Pagos y Ventas',
		href: '/pagos',
	},
];

const formatCurrency = (value: string | number) => {
	return new Intl.NumberFormat('es-BO', {
		style: 'currency',
		currency: 'BOB',
	}).format(Number(value || 0));
};

// Estado del modal
const modalOpen = ref(false);
const modalPagos = ref<Pagination<PagoItem> | null>(null);
const selectedVenta = ref<VentaItem | null>(null);
const loadingModal = ref(false);

// Función para abrir el modal con los pagos de una venta
const openModal = async (venta: VentaItem) => {
	selectedVenta.value = venta;
	modalOpen.value = true;
	loadingModal.value = true;
	
	try {
		// Filtrar pagos locales por venta_id
		const filteredPagos = props.pagos.data.filter(pago => pago.cuota?.venta?.id === venta.id);
		
		// Crear una estructura de paginación para los pagos filtrados
		modalPagos.value = {
			...props.pagos,
			data: filteredPagos,
			links: [], // No necesitamos paginación en el modal
		};
	} catch (error) {
		console.error('Error al filtrar pagos:', error);
		modalPagos.value = {
			...props.pagos,
			data: [],
			links: [],
		};
	} finally {
		loadingModal.value = false;
	}
};

// Función para cerrar el modal
const closeModal = () => {
	modalOpen.value = false;
	selectedVenta.value = null;
	modalPagos.value = null;
	loadingModal.value = false;
};
</script>

<template>
	<AppLayout :breadcrumbs="breadcrumbItems">
		<Head title="Pagos y ventas" />
		<UserLayout>
			<div class="space-y-6">
				<section class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
					<div class="p-6 border-b border-border">
						<h2 class="text-lg font-bold text-foreground">Ventas</h2>
					</div>

					<div class="overflow-x-auto">
						<table class="w-full text-sm text-left text-muted-foreground">
							<thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
								<tr>
									<th class="px-6 py-4 font-bold">Fecha</th>
									<th class="px-6 py-4 font-bold">Alumno</th>
									<th class="px-6 py-4 font-bold">Servicio</th>
									<th class="px-6 py-4 font-bold">Tipo</th>
									<th class="px-6 py-4 font-bold">Monto total</th>
									<th class="px-6 py-4 font-bold">Saldo</th>
									<th class="px-6 py-4 font-bold">Estado</th>
									<th class="px-6 py-4 text-right font-bold">Acción</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-border">
								<tr v-if="props.ventas.data.length === 0">
									<td colspan="8" class="px-6 py-8 text-center text-muted-foreground">
										No hay ventas registradas.
									</td>
								</tr>
								<tr v-for="venta in props.ventas.data" :key="venta.id" class="hover:bg-muted transition">
									<td class="px-6 py-4">{{ venta.fecha_emision }}</td>
									<td class="px-6 py-4 text-foreground">
										{{ venta.inscripcion?.alumno?.usuario?.name ?? 'Sin alumno' }}
									</td>
									<td class="px-6 py-4">{{ venta.inscripcion?.calendario?.servicio?.nombre ?? 'Sin servicio' }}</td>
									<td class="px-6 py-4">{{ venta.tipo_pago_pref }}</td>
									<td class="px-6 py-4">{{ formatCurrency(venta.monto_total) }}</td>
									<td class="px-6 py-4">{{ formatCurrency(venta.saldo_pendiente) }}</td>
									<td class="px-6 py-4">{{ venta.estado_financiero }}</td>
									<td class="px-6 py-4 text-right">
										<button
											@click="openModal(venta)"
											class="bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer inline-flex items-center justify-center rounded-md text-sm font-medium px-4 py-2"
										>
											Ver detalle
										</button>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div v-if="props.ventas.links.length > 0" class="px-6 py-4 border-t border-border bg-muted flex justify-center gap-2">
						<Link
							v-for="(link, index) in props.ventas.links"
							:key="`${link.label}-${index}`"
							:href="link.url || '#'
							"
							:class="[
								'px-3 py-2 rounded-lg transition text-sm',
								link.active ? 'bg-primary text-primary-foreground' : 'bg-card border border-border text-foreground hover:bg-muted',
								!link.url && 'opacity-50 pointer-events-none',
							]"
							v-html="link.label"
						/>
					</div>
				</section>

				<!-- <PagosRegistrados 
					:pagos="props.pagos" 
					:venta-id="props.ventaId"
					:titulo="props.ventaId ? 'Pagos de la Venta #' + props.ventaId : 'Pagos Registrados'"
					:show-back-button="true"
				/> -->
			</div>

			<!-- Modal para ver pagos de una venta -->
			<div v-if="modalOpen && selectedVenta"
				class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4">
				<div class="bg-card border border-border rounded-xl shadow-2xl overflow-hidden w-full max-w-6xl max-h-[90vh] overflow-y-auto">

					<div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted">
						<h3 class="text-lg font-bold text-foreground">Pagos de la Venta #{{ selectedVenta.id }}</h3>
						<button @click="closeModal"
							class="text-muted-foreground hover:text-foreground text-2xl transition">&times;</button>
					</div>

					<div class="p-6">
						<div v-if="modalPagos && !loadingModal" class="mt-4">
							<PagosRegistrados 
								:pagos="modalPagos" 
								:venta-id="selectedVenta.id"
								:titulo="'Pagos de la Venta #' + selectedVenta.id"
								:show-back-button="false"
							/>
						</div>
						<div v-else-if="loadingModal" class="flex items-center justify-center py-8">
							<div class="text-muted-foreground">Cargando pagos...</div>
						</div>
						<div v-else class="flex items-center justify-center py-8">
							<div class="text-muted-foreground">No se pudieron cargar los pagos</div>
						</div>
					</div>

					<div class="px-6 py-4 bg-muted border-t border-border flex flex-wrap gap-2 justify-end">
						<button @click="closeModal"
							class="px-4 py-2 cursor-pointer bg-secondary text-secondary-foreground rounded-lg text-sm font-bold hover:opacity-80 transition">Cerrar</button>
					</div>
				</div>
			</div>
		</UserLayout>
	</AppLayout>
</template>
