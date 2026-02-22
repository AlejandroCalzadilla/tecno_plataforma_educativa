<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Servicio {
    id: number;
    nombre: string;
    descripcion: string | null;
    modalidad: 'VIRTUAL' | 'PRESENCIAL' | 'HIBRIDO';
    calendarios_count: number;
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    search?: string;
    modalidad?: string;
}

const props = defineProps<{
    servicios: Pagination<Servicio>;
    filters?: Filters;
}>();

const search = ref(props.filters?.search ?? '');
const modalidad = ref(props.filters?.modalidad ?? '');

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Catálogo', href: '/catalogo' }];

const aplicarFiltros = () => {
    router.get(
        route('catalogo.index'),
        {
            search: search.value,
            modalidad: modalidad.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const limpiar = () => {
    search.value = '';
    modalidad.value = '';
    aplicarFiltros();
};

const modalidadLabel = (value: Servicio['modalidad']) => {
    if (value === 'VIRTUAL') return 'Virtual';
    if (value === 'PRESENCIAL') return 'Físico';
    return 'Híbrido';
};
console.log('Servicios recibidos en el catálogo:', props.servicios);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Catálogo de servicios" />
        <UserLayout>
            <div class="space-y-5">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">Catálogo de servicios</h1>
                    <p class="text-sm text-muted-foreground mt-1">Explora servicios y elige calendario por tutor y tipo.</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar servicio"
                        class="px-3 py-2 border border-border rounded-lg bg-card"
                    />
                    <select v-model="modalidad" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todas las modalidades</option>
                        <option value="VIRTUAL">Virtual</option>
                        <option value="FISICO">Físico</option>
                    </select>
                    <button class="px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="aplicarFiltros">Filtrar</button>
                    <button class="px-3 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="limpiar">Limpiar</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <article v-for="servicio in servicios.data" :key="servicio.id" class="bg-card border border-border rounded-xl p-4 space-y-3">
                        <div>
                            <h2 class="font-semibold text-foreground">{{ servicio.nombre }}</h2>
                            <p class="text-sm text-muted-foreground">{{ modalidadLabel(servicio.modalidad) }}</p>
                        </div>

                        <p class="text-sm text-muted-foreground min-h-10">{{ servicio.descripcion || 'Sin descripción.' }}</p>

                        <p class="text-sm">
                            Calendarios disponibles: <strong>{{ servicio.calendarios_count }}</strong>
                        </p>

                        <Link
                            :href="route('catalogo.servicio.show', servicio.id)"
                            class="inline-flex px-3 py-2 bg-primary text-primary-foreground rounded-lg"
                        >
                            Ver calendarios
                        </Link>
                    </article>
                </div>

                <div v-if="servicios.links?.length" class="bg-card border border-border rounded-xl p-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in servicios.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-2 rounded border border-border"
                        :class="{ 'bg-primary text-primary-foreground': link.active, 'pointer-events-none opacity-50': !link.url }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
