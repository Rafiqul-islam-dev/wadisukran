<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, Key, LayoutGrid, Shield, User, UserCog, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page_path = usePage().url;


const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Agent',
        icon: Users,
        items: [
            {
                title: 'Agent List',
                href: '/agents',
                icon: Users,
            },
        ],
    },
    {
        title: 'Product',
        icon: Folder,
        items: [
            {
                title: 'Product List',
                href: '/products',
                icon: Users,
            },
        ],
    },
    {
        title: 'App Banner',
        icon: Folder,
        items: [
            {
                title: 'Banner List',
                href: '/banners',
                icon: Users,
            },
        ],
    },
    {
        title: 'Report',
        icon: Folder,
        items: [
            {
                title: 'Daily Summary Report',
                href: '/orders',
                icon: Users,
            },
            {
                title: 'Cancel order',
                href: '/cancel-report',
                icon: Users,
            },
            {
                title: 'Order History',
                href: '/oder-history',
                icon: Users,
            },
        ],
    },
    {
        title: 'Settings',
        icon: Folder,
        items: [
            {
                title: 'Company',
                href: '/company-settings',
                icon: Users,
            }
        ],
    },
    {
        title: 'Users',
        href: '/users',
        icon: User
    },
    {
        title: 'Role & Permissions',
        icon: Shield,
        items: [
            {
                title: 'Role',
                href: '/roles',
                icon: UserCog,
            },
            {
                title: 'Permission',
                href: '/permissions',
                icon: Key,
            }
        ],
    },

];


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :current-path="page_path" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
