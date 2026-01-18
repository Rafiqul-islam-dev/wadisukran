<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick, watch, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const { company_setting } = usePage().props;
const { users, filters, orders } = defineProps<{
    orders: Array<any>;
    users: Array<any>;
    company: Record<string, any>;
    categories: Array<any>;
    products: Array<any>;
    filters: Record<string, any>;
    product_prizes: Array<any>;
}>();

const filter = ref({
    user_id: filters?.user_id ?? '',
    date_from: filters?.date_from ?? '',
    time_from: filters?.time_from ?? '',
    date_to: filters?.date_to ?? '',
    time_to: filters?.time_to ?? '',
    match_type: filters?.match_type ?? '',
    category_id: filters?.category_id ?? '',
    product_id: filters?.product_id ?? '',
});

function resetFilters() {
    filter.value = {
        user_id: '',
        date_from: '',
        time_from: '',
        date_to: '',
        time_to: '',
        match_type: '',
        category_id: '',
        product_id: '',
    };
    handleSearch();
}

const handleSearch = () => {
    router.get(
        route('reports.cancel-report'),
        { 
            ...filter.value,
            status: 'Cancel' // Match database status value
        },
        {
            preserveScroll: true,
            replace: true,
            showProgress: false
        }
    );
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0
    }).format(amount);
};

</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                            Cancelled Orders
                        </h1>
                        <p class="text-gray-600 mt-1">Search and manage your cancelled orders</p>
                    </div>
                    <div class="bg-white px-6 py-3 rounded-2xl shadow-lg border-2 border-orange-200">
                        <p class="text-sm text-gray-500">Total Cancelled</p>
                        <p class="text-2xl font-bold text-orange-600">{{ orders?.length || 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section with Enhanced Design -->
            <div class="bg-white rounded-3xl shadow-2xl p-6 mb-6 border-2 border-orange-100 hover:shadow-orange-200 transition-all duration-300">
                <div class="flex items-center mb-4">
                    <div class="w-1 h-8 bg-gradient-to-b from-orange-500 to-amber-500 rounded-full mr-3"></div>
                    <h2 class="text-xl font-bold text-gray-800">Search Filters</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                    <!-- User Select -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Select User
                        </label>
                        <select v-model="filter.user_id"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gradient-to-br from-white to-orange-50 hover:border-orange-300">
                            <option value="">All Users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            From Date
                        </label>
                        <input v-model="filter.date_from" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gradient-to-br from-white to-orange-50 hover:border-orange-300" />
                    </div>

                    <!-- From Time -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            From Time
                        </label>
                        <input v-model="filter.time_from" type="time"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gradient-to-br from-white to-orange-50 hover:border-orange-300" />
                    </div>

                    <!-- To Date -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            To Date
                        </label>
                        <input v-model="filter.date_to" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gradient-to-br from-white to-orange-50 hover:border-orange-300" />
                    </div>

                    <!-- To Time -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            To Time
                        </label>
                        <input v-model="filter.time_to" type="time"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gradient-to-br from-white to-orange-50 hover:border-orange-300" />
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t-2 border-gray-100">
                    <button @click="resetFilters"
                        class="px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-xl hover:from-gray-200 hover:to-gray-300 transition-all duration-200 font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                    <button @click="handleSearch"
                        class="px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search Orders
                    </button>
                </div>
            </div>

            <!-- Table Section with Enhanced Design -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border-2 border-orange-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-orange-500 to-amber-500 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Invoice No
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Vendor
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Sales Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Product
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Raffle Ticket
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">
                                    Total Price
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(order, index) in orders" :key="order.id" 
                                class="hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 transition-all duration-200"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-orange-600">#{{ order.invoice_no }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-400 rounded-full flex items-center justify-center text-white font-bold mr-2">
                                            {{ order.user?.name?.charAt(0) || 'V' }}
                                        </div>
                                        <span class="font-medium text-gray-900">{{ order.user?.name || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
                                        {{ order.product?.title || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div v-for="ticket in order.tickets" :key="ticket.id" class="mb-3">
                                        <div class="flex flex-nowrap gap-2">
                                            <span v-for="type in ticket.selected_play_types" :key="type"
                                                class="bg-gray-100 rounded-lg px-2 py-1 text-sm whitespace-nowrap">
                                                {{ type }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div v-for="ticket in order.tickets" :key="ticket.id" class="mb-3">
                                        <div class="flex flex-nowrap gap-2 overflow-x-auto whitespace-nowrap">
                                            <span v-for="number in ticket.selected_numbers" :key="number"
                                                class="bg-gray-100 rounded-lg px-2 py-1 text-sm whitespace-nowrap shrink-0">
                                                {{ number }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-900">{{ order.quantity || 0 }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-orange-600 text-lg">{{ formatCurrency(order.total_price || 0) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="!orders || orders.length === 0" class="text-center py-16">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-32 h-32 bg-gradient-to-br from-orange-200 to-amber-200 rounded-full opacity-20 animate-pulse"></div>
                            </div>
                            <svg class="w-20 h-20 text-orange-300 mx-auto mb-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">No Cancelled Orders Found</h3>
                        <p class="text-gray-500 mb-6">Try adjusting your search filters to see more results.</p>
                        <button @click="resetFilters" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-semibold shadow-lg">
                            Clear All Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>