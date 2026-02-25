<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';
import debounce from 'lodash/debounce';
interface Servicio {
    id: number;
    nombre: string;
    descripcion: string | null;
    modalidad: 'VIRTUAL' | 'PRESENCIAL' | 'HIBRIDO';
    categoria:Categoria ;
    categorias: Categoria[]; // Agregado para recibir la lista de categorías asociadas al servicio
    calendarios_count: number;
}

interface Categoria {
    id: number;
    id_categoria_padre: number | null;
    nombre: string;
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
    categorias: Categoria[];    
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
            replace: true,
        },
    );
};


watch(
    search,
    debounce(() => {
        aplicarFiltros();
    }, 300)
);

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
const categoriascolorbase = 'text-sm rounded px-2 py-1';


const coloresmodalidad: Record<Servicio['modalidad'], string> = {
    VIRTUAL: 'text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded px-2 py-1',
    PRESENCIAL: 'text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded px-2 py-1',
    HIBRIDO: 'text-sm bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded px-2 py-1',
};

const getAncestros = (categoriaActual: Categoria | undefined): Categoria[] => {
    if (!categoriaActual || !categoriaActual.id_categoria_padre) return [];
    
    const ancestros: Categoria[] = [];
    let idPadreBuscado = categoriaActual.id_categoria_padre;

    // Buscamos hacia arriba en el árbol mientras el id del padre no sea nulo
    while (idPadreBuscado !== null) {
        const padre = props.categorias.find(c => c.id === idPadreBuscado);
        if (padre) {
            ancestros.unshift(padre); // Insertamos al inicio para mantener el orden jerárquico
            idPadreBuscado = padre.id_categoria_padre!;
        } else {
            break;
        }
    }
    
    return ancestros;
};



const styleAncestros = 'bg-slate-800 text-white font-bold shadow-sm';
const styleCategoriaActual = 'bg-slate-100 text-slate-600 border border-slate-200 font-medium';
console.log('Servicios recibidos en el catálogo:', props.servicios);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Catálogo de servicios" />
        <UserLayout>
            <div class="space-y-5">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold text-foreground">Catálogo de servicios</h1>
                    <p class="text-sm text-muted-foreground mt-1">Explora servicios y elige calendario por tutor y tipo.
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input v-model="search" type="text" placeholder="Buscar servicio"
                        class="px-3 py-2 border border-border rounded-lg bg-card" />
                    <select v-model="modalidad" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todas las modalidades</option>
                        <option value="VIRTUAL">Virtual</option>
                        <option value="FISICO">Físico</option>
                        <option value="HIBRIDO">Híbrido</option>
                    </select>
                     <button class="px-3 py-2 bg-primary text-primary-foreground rounded-lg"
                        @click="aplicarFiltros">Filtrar</button> 
                    <button class="px-3 py-2 bg-secondary text-secondary-foreground rounded-lg"
                        @click="limpiar">Limpiar</button>
                </div>

               <div v-if="servicios.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <article v-for="servicio in servicios.data" :key="servicio.id" 
                        class="group bg-card border border-border rounded-2xl p-5 flex flex-col shadow-sm hover:shadow-md hover:border-primary/20 transition-all duration-300"
                    >
                        <!-- Categoría y Modalidad -->
                        <div class="flex flex-wrap gap-2 mb-4">
                            <!-- Badge de Categoría con lógica de jerarquía -->
                            

                           <span v-for="ancestro in getAncestros(servicio.categoria)" :key="ancestro.id"
                                :class="[styleAncestros, 'text-[9px] px-2 py-1 rounded-md uppercase tracking-wider']"
                            >
                                {{ ancestro.nombre }}
                            </span>
                            <span  v-if="servicio.categoria" 
                                :class="[styleCategoriaActual, 'text-[10px] px-2 py-1 rounded-md uppercase tracking-wider']"
                            >
                                {{ servicio.categoria.nombre }}
                            </span>
                          
                            
                             
                            
                            <span :class="[coloresmodalidad[servicio.modalidad], 'text-[10px] px-2 py-1 rounded-md uppercase tracking-wider border font-bold']">
                                {{ modalidadLabel(servicio.modalidad) }}
                            </span>
                        </div>

                        <div class="flex-1">
                            <h2 class="font-bold text-xl text-foreground mb-2 group-hover:text-primary transition-colors">
                                {{ servicio.nombre }}
                            </h2>
                            <p class="text-sm text-muted-foreground line-clamp-3 leading-relaxed mb-6">
                                {{ servicio.descripcion || 'Este servicio no cuenta con una descripción detallada todavía.' }}
                            </p>
                        </div>

                        <!-- Footer de la tarjeta -->
                        <div class="pt-4 border-t border-border flex items-center justify-between mt-auto">
                            <div class="flex flex-col">
                                <span class="text-xs text-muted-foreground uppercase font-bold tracking-tighter">Disponibilidad</span>
                                <span class="text-sm font-medium text-foreground">
                                    {{ servicio.calendarios_count }} {{ servicio.calendarios_count === 1 ? 'Calendario' : 'Calendarios' }}
                                </span>
                            </div>

                            <Link
                                :href="route('catalogo.servicio.show', servicio.id)"
                                class="inline-flex items-center px-5 py-2.5 bg-primary text-primary-foreground text-sm font-bold rounded-xl shadow-lg shadow-primary/10 hover:shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all"
                            >
                                Ver horarios
                            </Link>
                        </div>
                    </article>
                </div>

                <!-- Estado Vacío -->
                <div v-else class="bg-card border border-border rounded-xl p-12 text-center">
                    <p class="text-muted-foreground">No se encontraron servicios que coincidan con tu búsqueda.</p>
                    <button @click="limpiar" class="mt-4 text-primary font-bold hover:underline">Ver todos los servicios</button>
                </div>



                <div v-if="servicios.links?.length"
                    class="bg-card border border-border rounded-xl p-4 flex flex-wrap gap-2">
                    <Link v-for="link in servicios.links" :key="link.label" :href="link.url || '#'"
                        class="px-3 py-2 rounded border border-border"
                        :class="{ 'bg-primary text-primary-foreground': link.active, 'pointer-events-none opacity-50': !link.url }"
                        v-html="link.label" />
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
