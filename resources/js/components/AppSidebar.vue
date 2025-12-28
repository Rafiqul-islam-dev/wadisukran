<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, Key, LayoutGrid, List, Option, Shield, Trash, User, UserCog, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
const page_path = usePage().url;
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        permission: 'show dashboard'
    },
    {
        title: 'Agent',
        icon: Users,
        items: [
            {
                title: 'Agent List',
                href: '/agents',
                icon: Users,
                permission: 'show agent list'
            },
            {
                title: 'Trashed Agents',
                href: '/agents/trashed',
                icon: Trash,
                permission: 'show trashed agents'
            },
        ],
        permission: 'show agent list'
    },
    {
        title: 'Product',
        icon: Folder,
        items: [
            {
                title: 'Product List',
                href: '/products',
                icon: Users,
                permission: 'show product list'
            },
            {
                title: 'Categories',
                href: '/categories',
                icon: Option,
                permission: 'show categories'
            },
            {
                title: 'Trashed Products',
                href: '/products/trash',
                icon: Trash,
                permission: 'show trashed products'
            },
        ],
        permission: 'show product list'
    },
    {
        title: 'App Banner',
        icon: Folder,
        items: [
            {
                title: 'Banner List',
                href: '/banners',
                icon: Users,
                permission: 'show banner list'
            },
        ],
        permission: 'show banner list'
    },
    {
        title: 'Report',
        icon: Folder,
        items: [
            {
                title: 'Daily Summary Report',
                href: '/orders',
                icon: Users,
                permission: 'show daily summery report'
            },
            {
                title: 'Cancel order',
                href: '/cancel-report',
                icon: Users,
                permission: 'show cancel order'
            },
            {
                title: 'Order History',
                href: '/oder-history',
                icon: Users,
                permission: 'show order history'
            },
        ],
        permission: 'show report'
    },
    {
        title: 'Settings',
        icon: Folder,
        items: [
            {
                title: 'Company',
                href: '/company-settings',
                icon: Users,
                permission: 'show company settings'
            }
        ],
        permission: 'show settings'
    },
    {
        title: 'Users',
        href: '/users',
        icon: User,
        items: [
            {
                title: 'User List',
                href: '/users',
                icon: List,
                permission: 'show users'
            },
            {
                title: 'Trashed Users',
                href: '/users/trashed',
                icon: Trash,
                permission: 'show trashed users'
            }
        ],
        permission: 'show users'
    },
    {
        title: 'Role & Permissions',
        icon: Shield,
        items: [
            {
                title: 'Role',
                href: '/roles',
                icon: UserCog,
                permission: 'show roles'
            },
            {
                title: 'Permission',
                href: '/permissions',
                icon: Key,
                permission: 'show permissions'
            }
        ],
        permission: 'role permission'
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
