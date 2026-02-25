<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, Home, LayoutGrid, Users,CreditCard, School, CalendarRange, UserSearch, ListTodo, Banknote } from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard } from '@/routes';
import { route } from 'ziggy-js';
import { computed } from 'vue';

const page = usePage();
const userRoles = computed(() => page.props.auth.user.roles);
const allNavItems: (NavItem & { role?: string[] })[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        role: ['propietario'], // Admin y tutor
    },

    {   title: 'Usuarios',
        href: route('users.index'),
        icon: Users,
        role: ['propietario'], // Solo admin
    },
    {    
         title: 'Servicios',
        href: route('servicios.index'),
        icon: School,
        role: [ 'propietario'], // Admin y tutor
    },
    {
        title: 'Pagos',
        href: route('pagos.index'),
        icon: CreditCard,
        role: ['alumno'], // Solo alumno

    },
    {
        title: 'Oferta Academica',
        href:route('calendarios.index'),
        icon: CalendarRange,
        role: ['tutor'], // Solo alumno
    },
    {
        title: 'Reportes',
        href: dashboard(),
        icon: CalendarRange,
        role: ['propietario'], // Solo admin
    },
    {
        title: 'Catalogo',
        href: route('catalogo.index'),
        icon: UserSearch,
        role: ['alumno'], // Solo admin
    },
    /* {
        title: 'Mis Inscripciones',
        href: dashboard(),
        icon: ListTodo,
        role: ['alumno'], // Solo alumno
    }, */
    {
        title: 'Mis Sesiones',
        href: route('sesiones.index'),
        icon: CalendarRange,
        role: ['alumno', 'tutor'], // Alumno y tutor
    },
    {
        title: 'Ventas',
        href: route('pagos.index'),
        icon: Banknote,
        role: ['propietario'], // Solo admin
    },
    
     {
        title: 'Licencias',
        href: route('licencias.index'),
        icon: ListTodo,
        role: ['tutor','alumno'], // Solo tutor

     },
     {
        title: 'Informes de clase',
        href: route('informes-clase.index'),
        icon: ListTodo,
        role: ['tutor'], // Solo tutor
     },
    {
        title: 'Categorias',
        href: route('categorias.index'),
        icon: ListTodo,
        role: ['propietario'], // Solo admin
    }


];
const filteredNavItems = computed(() => {
    return allNavItems.filter(item => {
        // Si no tiene la propiedad 'role', es público (como el Dashboard)
        if (!item.role) return true;

        // Verificamos si el usuario tiene alguno de los roles permitidos para este item
        return item.role.some(r => userRoles.value![r as keyof typeof userRoles.value]);
    });
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

const sidebarContentClass = "kids:bg-[url('data:image/svg+xml,%3Csvg width=%2760%27 height=%2760%27 viewBox=%270 0 60 60%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cg fill=%27%23ffb6d5%27 fill-opacity=%270.2%27%3E%3Cpath d=%27M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%27/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]";
</script>

<template>
    <Sidebar >
        <SidebarHeader >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent >
            <NavMain :items="filteredNavItems" />
        </SidebarContent>

        <SidebarFooter >
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
