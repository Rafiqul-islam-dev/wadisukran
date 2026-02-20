<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { can } from '@/helpers/permissions';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const { company_setting } = usePage().props;
const { agent_count, today_sales, today_commissions, top_ten_customers, top_ten_agents } = defineProps<{
    agent_count: string;
    today_sales: string;
    today_commissions: string;
    top_ten_customers: Array<any>;
    top_ten_agents: Array<any>;
}>();

console.log(top_ten_agents)

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

                <!-- Top 3 Agents Card -->
                <div v-if="can('show top agents')"
                    class="group relative overflow-hidden rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm transition-all hover:shadow-md dark:border-orange-900/30 dark:from-orange-950/20 dark:to-gray-900 md:col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-md font-medium text-orange-600 dark:text-orange-400">Top 10 Agents This Month</p>
                        </div>
                        <Link :href="route('agents.top-ten')" class="flex items-center gap-x-2">
                            <button
                                class="flex items-center gap-1 rounded-lg border border-orange-200 cursor-pointer bg-white px-3 py-1.5 text-xs font-medium text-orange-600 transition hover:bg-orange-50 dark:border-orange-800 dark:bg-gray-800 dark:text-orange-400 dark:hover:bg-orange-900/20">
                                View All
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </Link>
                    </div>

                    <div class="divide-y divide-orange-100 dark:divide-orange-900/30">
                        <div v-for="(agent, index) in top_ten_agents.slice(0, 3)" :key="agent.id"
                            class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">

                            <!-- Rank Badge -->
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                :class="{
                                    'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': index === 0,
                                    'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': index === 1,
                                    'bg-orange-100 text-orange-500 dark:bg-orange-900/30 dark:text-orange-400': index === 2,
                                }">
                                {{ index + 1 }}
                            </div>

                            <!-- Avatar -->
                            <div class="relative">
                                <img v-if="agent.avatar" :src="agent.avatar" alt="User Photo"
                                    class="w-12 h-12 object-contain rounded-full border-4 border-indigo-100 shadow-md" />
                                <div v-else
                                    class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center shadow-md">
                                    <span class="text-white font-bold text-lg">{{ agent.name.charAt(0)
                                        }}</span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white"
                                    :class="agent.is_active ? 'bg-green-500' : 'bg-gray-400'"></div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ agent.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ agent.orders_count }} sales</p>
                            </div>

                            <!-- Commission -->
                            <div class="text-right">
                                <p class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                    {{ agent.total_sale }} {{ company_setting?.currency }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-orange-400 to-amber-500"></div>
                </div>
                <!-- Top 3 Customer Card -->
                <div v-if="can('show top customers')"
                    class="group relative overflow-hidden rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6 shadow-sm transition-all hover:shadow-md dark:border-orange-900/30 dark:from-orange-950/20 dark:to-gray-900 md:col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-md font-medium text-orange-600 dark:text-orange-400">Top 10 Customers This Month</p>
                        </div>
                        <Link :href="route('customers.top-ten')" class="flex items-center gap-x-2">
                            <button
                                class="flex items-center gap-1 rounded-lg border border-orange-200 cursor-pointer bg-white px-3 py-1.5 text-xs font-medium text-orange-600 transition hover:bg-orange-50 dark:border-orange-800 dark:bg-gray-800 dark:text-orange-400 dark:hover:bg-orange-900/20">
                                View All
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </Link>
                    </div>

                    <div class="divide-y divide-orange-100 dark:divide-orange-900/30">
                        <div v-for="(customer, index) in top_ten_customers.slice(0, 3)" :key="customer.id"
                            class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">

                            <!-- Rank Badge -->
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                :class="{
                                    'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': index === 0,
                                    'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': index === 1,
                                    'bg-orange-100 text-orange-500 dark:bg-orange-900/30 dark:text-orange-400': index === 2,
                                }">
                                {{ index + 1 }}
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ customer.phone }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ customer.orders_count }} orders</p>
                            </div>

                            <!-- Commission -->
                            <div class="text-right">
                                <p class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                    {{ customer.total_sale }} {{ company_setting?.currency }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-orange-400 to-amber-500"></div>
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
