<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { can } from '@/helpers/permissions';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const { company_setting } = usePage().props;
const { agent_count, today_sales, today_commissions } = defineProps<{
    agent_count: string;
    today_sales: string;
    today_commissions: string;
}>();

// Sample data - replace with your actual data
const metrics = {
    totalAgents: 42,
    todaySales: 125000,
    todayCommission: 8750,
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Metrics Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Total Agents Card -->
                <div v-if="can('show total agents')"
                    class="group relative overflow-hidden rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm transition-all hover:shadow-md dark:border-orange-900/30 dark:from-orange-950/20 dark:to-gray-900">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                Total Agents
                            </p>
                            <h3 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ agent_count }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Active agents in your system
                            </p>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-orange-400 to-orange-600">
                    </div>
                </div>

                <!-- Today Sales Card -->
                <div v-if="can('show today sales')"
                    class="group relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm transition-all hover:shadow-md dark:border-amber-900/30 dark:from-amber-950/20 dark:to-gray-900">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">
                                Today's Sales
                            </p>
                            <h3 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                                {{  today_sales + ' ' + company_setting?.currency }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Total sales for today
                            </p>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-amber-400 to-amber-600"></div>
                </div>

                <!-- Today Commission Card -->
                <div v-if="can('show today commission')"
                    class="group relative overflow-hidden rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm transition-all hover:shadow-md dark:border-orange-900/30 dark:from-orange-950/20 dark:to-gray-900">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                Today's Commission
                            </p>
                            <h3 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{  today_commissions + ' ' + company_setting?.currency }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Total commission for agents
                            </p>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-orange-400 to-orange-600">
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <!-- <div class="relative min-h-[100vh] flex-1 rounded-xl border border-orange-200/70 bg-white p-6 shadow-sm md:min-h-min dark:border-orange-900/30 dark:bg-gray-900">
                <div class="flex h-full items-center justify-center">
                    <div class="text-center">
                        <div class="mx-auto mb-4 rounded-full bg-orange-100 p-4 w-16 h-16 flex items-center justify-center dark:bg-orange-900/30">
                            <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Analytics Section
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Add your charts, tables, or additional content here
                        </p>
                    </div>
                </div>
            </div> -->
        </div>
    </AppLayout>
</template>
