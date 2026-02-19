<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },

    {   title: 'Usuarios',
        href: route('users.index'),
        icon: Users,
    },
    {    
         title: 'Servicios',
        href: route('servicios.index'),
        icon: School,
    },
    {
        title: 'Pagos',
        href: dashboard(),
        icon: CreditCard,
    },
    {
        title: 'Oferta Academica',
        href:route('calendarios.index'),
        icon: CalendarRange,
    },
    {
        title: 'Reportes',
        href: dashboard(),
        icon: CalendarRange,
    },
    {
        title: 'Catalogo',
        href: dashboard(),
        icon: UserSearch,
    },
    {
        title: 'Mis Inscripciones',
        href: dashboard(),
        icon: ListTodo,
    },
    {
        title: 'Ventas',
        href: dashboard(),
        icon: Banknote,
    },
    {
        title: 'Asistencia',
        href: dashboard(),
        icon: ListTodo,
    },{
        title: 'Categorias',
        href: route('categorias.index'),
        icon: ListTodo,
    }


];


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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
