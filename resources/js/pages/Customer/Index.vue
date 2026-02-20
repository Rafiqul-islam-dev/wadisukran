<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customer List',
        href: '/customers',
    },
];

const { customers } = defineProps<{
    customers: Array<any>;
}>();

const search = ref('');

function handleSearch(){
    router.get(
        route('customers.index'),
        { search: search.value },
        {
            preserveScroll: true,
            replace: true,
            showProgress: false,
            preserveState: true
        }
    );
}

function goTo(url) {
    if (!url) return

    const page = new URL(url).searchParams.get('page')
    const params = new URLSearchParams(window.location.search)

    if (page) params.set('page', page)

    router.visit(`${window.location.pathname}?${params.toString()}`, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false
    })
}
</script>

<template>
    <Head title="Customers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

            <!-- Header -->
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage and view all registered customers</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 text-sm font-semibold text-gray-600">
                    Total: <span class="text-orange-500">{{ customers?.data?.length }}</span>
                </div>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="search"
                        v-on:change="handleSearch"
                        type="text"
                        placeholder="Search by name, email or phone..."
                        class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-100 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition-all text-sm"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Table Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-amber-500 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h2 class="text-white font-bold text-lg">Customer List</h2>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-orange-50 border-b-2 border-orange-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(customer, index) in customers?.data" :key="customer.id"
                                class="hover:bg-orange-50 transition-colors duration-200">

                                <!-- Index -->
                                <td class="px-6 py-4">
                                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-bold">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <!-- Phone -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2" v-if="customer.phone">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ customer.phone ?? '-' }}</span>
                                    </div>
                                </td>
                                <!-- Name + Avatar -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3" v-if="customer.name">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                            {{ customer.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ customer.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Email -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2" v-if="customer.email">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ customer.email ?? '-' }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                     <div class="flex justify-end py-2 px-6">
                        <nav class="flex items-center space-x-1">
                            <button v-for="(link, i) in customers.links" :key="i" @click="goTo(link.url)"
                                v-html="link.label" :disabled="!link.url" :class="[
                                    'px-3 py-1 rounded border transition-all duration-200',
                                    link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]" />
                        </nav>
                    </div>

                    <!-- Empty State -->
                    <div class="text-center py-16" v-if="customers?.data?.length === 0">
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
