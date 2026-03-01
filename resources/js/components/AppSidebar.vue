<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart, BarChart3, Book, CheckCheck, Download, Folder, History, Key, LayoutGrid, List, Option, Shield, Shuffle, Trash, User, UserCircle, UserCog, Users, Wallet, Wind } from 'lucide-vue-next';
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
            {
                title: 'Agent History',
                href: '/agents/history',
                icon: History,
                permission: 'show agent history'
            },
        ],
        permission: 'show agent list'
    },
    {
        title: 'Account',
        icon: Wallet,
        items: [
            {
                title: 'Accounts Summery',
                href: '/accounts/summery',
                icon: BarChart3,
                permission: 'show accounts summery'
            },
            {
                title: 'Ledger',
                href: '/accounts/ledger',
                icon: Book,
                permission: 'show accounts ledger'
            },
            {
                title: 'Bills',
                href: '/accounts/bills',
                icon: Download,
                permission: 'show accounts bills'
            },
        ],
        permission: 'show agent list'
    },
    {
        title: 'Product',
        icon: Folder,
        items: [
             {
                title: 'Categories',
                href: '/categories',
                icon: Option,
                permission: 'show categories'
            },
            {
                title: 'Product List',
                href: '/products',
                icon: Users,
                permission: 'show product list'
            },
            {
                title: 'Trashed Products',
                href: '/trashed-products',
                icon: Trash,
                permission: 'show trashed products'
            }
        ],
        permission: 'show product list'
    },
    {
        title: 'Draw',
        icon: Shuffle,
        items: [
            {
                title: 'Daily Draw',
                href: '/draws',
                icon: Shuffle,
                permission: 'show draws'
            },
            {
                title: 'Probability Wins',
                href: '/probable-wins',
                icon: Wind,
                permission: 'show probable wins'
            },
            {
                title: 'Check Winners',
                href: '/check-wins',
                icon: CheckCheck,
                permission: 'show check winners'
            },
            {
                title: 'Draw History',
                href: '/draws/histories',
                icon: History,
                permission: 'show draw history'
            }
        ],
        permission: 'show draws'
    },
    {
        title: 'App Banner',
        icon: Folder,
        items: [
            {
                title: 'Banner List',
                href: '/slides',
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
                title: 'Daily Sales Report',
                href: '/orders',
                icon: Users,
                permission: 'show daily summery report'
            },
            {
                title: 'Product Wise Report',
                href: '/sales-report/product-wise',
                icon: BarChart,
                permission: 'show product wise report'
            },
            {
                title: 'Cancel Request',
                href: '/reports/cancel-request',
                icon: Users,
                permission: 'show cancel request'
            },
            {
                title: 'Cancel order',
                href: '/reports/cancel-report',
                icon: Users,
                permission: 'show cancel order'
            },

            {
                title: 'Winner Report',
                href: '/reports/winner-report',
                icon: Users,
                permission: 'show winner report'
            },
            {
                title: 'Winner Report Agent',
                href: '/reports/winner-report-agent',
                icon: Users,
                permission: 'show winner report agent'
            },
        ],
        permission: 'show report'
    },
    {
        title: 'Payment',
        icon: Folder,
        items: [
            {
                title: 'All Payment',
                href: '/payments/all-payment',
                icon: Users,
                permission: 'show all payment'
            },
        ],
        permission: 'all payment'
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
        title: 'Customers',
        href: '/customers',
        icon: UserCircle,
        items: [
            {
                title: 'Customer List',
                href: '/customers',
                icon: List,
                permission: 'show customers'
            }
        ],
        permission: 'show customers'
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
