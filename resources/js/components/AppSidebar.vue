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
        role: [ 'tutor','propietario'], // Admin y tutor
    },
    {
        title: 'Pagos',
        href: dashboard(),
        icon: CreditCard,
        role: ['alumno','propietario'], // Solo alumno

    },
    {
        title: 'Oferta Academica',
        href:route('calendarios.index'),
        icon: CalendarRange,
        role: ['propietario'], // Solo alumno
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
    {
        title: 'Mis Inscripciones',
        href: dashboard(),
        icon: ListTodo,
        role: ['alumno'], // Solo alumno
    },
    {
        title: 'Ventas',
        href: dashboard(),
        icon: Banknote,
        role: ['propietario'], // Solo admin
    },
    {
        title: 'Asistencia',
        href: dashboard(),
        icon: ListTodo,
        role: ['tutor'], // Solo tutor
    },
     {
        title: 'Licencias',
        href: route('licencias.index'),
        icon: ListTodo,
        role: ['tutor'], // Solo tutor

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
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
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

        <SidebarContent>
            <NavMain :items="filteredNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
