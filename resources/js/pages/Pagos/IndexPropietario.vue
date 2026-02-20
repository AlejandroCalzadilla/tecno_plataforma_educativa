<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

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
								</tr>
							</thead>
							<tbody class="divide-y divide-border">
								<tr v-if="props.ventas.data.length === 0">
									<td colspan="7" class="px-6 py-8 text-center text-muted-foreground">
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

				<section class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
					<div class="p-6 border-b border-border">
						<h2 class="text-lg font-bold text-foreground">Pagos registrados</h2>
					</div>

					<div class="overflow-x-auto">
						<table class="w-full text-sm text-left text-muted-foreground">
							<thead class="text-xs text-foreground uppercase bg-muted border-b border-border">
								<tr>
									<th class="px-6 py-4 font-bold">Fecha</th>
									<th class="px-6 py-4 font-bold">Alumno</th>
									<th class="px-6 py-4 font-bold">Servicio</th>
									<th class="px-6 py-4 font-bold">Cuota</th>
									<th class="px-6 py-4 font-bold">Método</th>
									<th class="px-6 py-4 font-bold">Monto</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-border">
								<tr v-if="props.pagos.data.length === 0">
									<td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
										No hay pagos registrados.
									</td>
								</tr>
								<tr v-for="pago in props.pagos.data" :key="pago.id" class="hover:bg-muted transition">
									<td class="px-6 py-4">{{ pago.fecha_pago }}</td>
									<td class="px-6 py-4 text-foreground">{{ pago.alumno?.usuario?.name ?? 'Sin alumno' }}</td>
									<td class="px-6 py-4">{{ pago.cuota?.venta?.inscripcion?.calendario?.servicio?.nombre ?? 'Sin servicio' }}</td>
									<td class="px-6 py-4">#{{ pago.cuota?.numero_cuota ?? '-' }}</td>
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
			</div>
		</UserLayout>
	</AppLayout>
</template>
