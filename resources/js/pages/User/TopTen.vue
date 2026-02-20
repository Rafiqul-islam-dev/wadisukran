<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Top Ten Agents',
        href: '/agents/top-ten',
    },
];
const { company_setting } = usePage().props;
const { agents } = defineProps<{
    agents: Array<any>;
}>();

</script>

<template>
    <Head title="Customers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-orange-50 border-b-2 border-orange-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Orders</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(agent, index) in agents" :key="agent.id"
                                class="hover:bg-orange-50 transition-colors duration-200">

                                <!-- Index -->
                                <td class="px-6 py-4">
                                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-bold">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <!-- Phone -->
                                <td class="px-6 py-4">
                                     <div class="relative flex items-center gap-3">
                                        <img v-if="agent.avatar" :src="agent.avatar" alt="User Photo"
                                            class="w-12 h-12 object-contain rounded-full border-4 border-indigo-100 shadow-md" />
                                        <div v-else
                                            class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center shadow-md">
                                            <span class="text-white font-bold text-lg">{{ agent.name.charAt(0)
                                                }}</span>
                                        </div>
                                        <div>
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ agent.name }}</p>
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ agent.address }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Name + Avatar -->
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ agent.orders_count }}</p>
                                    </div>
                                </td>
                                <!-- Email -->
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700">{{ agent.total_sale }} {{ company_setting?.currency }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div class="text-center py-16" v-if="agents?.length === 0">
                        <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No customers found</h3>
                        <p class="text-gray-400 text-sm">Try adjusting your search query.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
