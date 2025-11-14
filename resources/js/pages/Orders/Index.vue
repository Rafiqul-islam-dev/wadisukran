<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const { orders, users, company } = defineProps<{
    orders: Array<any>;
    users: Array<any>;
    company: Record<string, any>; // or: company: any;
}>();


const filter = ref({
    user_id: '',
    date_from: '',
    date_to: '',
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);
const modalVisible = ref(false);

const filteredOrders = computed(() => {
    return props.orders.filter(order => {
        const salesDate = new Date(order.sales_date);
        const dateFrom = filter.value.date_from ? new Date(filter.value.date_from) : null;
        const dateTo = filter.value.date_to ? new Date(filter.value.date_to) : null;

        const matchesUser = filter.value.user_id ? order.user_id === parseInt(filter.value.user_id) : true;
        const matchesDateFrom = dateFrom ? salesDate >= dateFrom : true;
        const matchesDateTo = dateTo ? salesDate <= dateTo : true;

        return matchesUser && matchesDateFrom && matchesDateTo;
    });
});

const totalPages = computed(() => Math.ceil(filteredOrders.value.length / itemsPerPage.value));
const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredOrders.value.slice(start, end);
});

const selectedOrder = ref(null);
const showModal = ref(false);

function resetFilters() {
    filter.value = {
        user_id: '',
        date_from: '',
        date_to: '',
    };
    currentPage.value = 1;
}

function formatDate(dateString) {
    return dateString ? new Date(dateString).toLocaleDateString() : '-';
}

function openModal(order) {
    selectedOrder.value = order;
    showModal.value = true;
    document.body.style.overflow = 'hidden';
    modalVisible.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedOrder.value = null;
    document.body.style.overflow = 'auto';
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
}

function changeItemsPerPage(newItemsPerPage) {
    itemsPerPage.value = newItemsPerPage;
    currentPage.value = 1;
}

const pageNumbers = computed(() => {
    const pages = [];
    const totalPageCount = totalPages.value;
    const current = currentPage.value;

    if (totalPageCount <= 7) {
        for (let i = 1; i <= totalPageCount; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(totalPageCount);
        } else if (current >= totalPageCount - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = totalPageCount - 4; i <= totalPageCount; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(totalPageCount);
        }
    }
    return pages;
});

// Function to determine the big prize based on play types and numbers
const getBigPrize = (order) => {
    const numberPrizes = {
        '5 NUMBERS': '5000.00 AED',
        '6 NUMBERS': '6000.00 AED',
        '7 NUMBERS': '7000.00 AED',
    };

    const playTypePrizes = {
        chance: ['1250.00 AED', '70.00 AED', '10.00 AED'],
        ramble: '500.00 AED',
        straight: '3000.00 AED',
    };

    // Check if the order has game_cards with selected_numbers
    const selectedNumbersCount = order?.game_cards?.[0]?.selected_numbers?.length || 0;
    let prize = '';

    // Determine prize based on number of selected numbers
    if (selectedNumbersCount === 5) {
        prize = numberPrizes['5 NUMBERS'];
    } else if (selectedNumbersCount === 6) {
        prize = numberPrizes['6 NUMBERS'];
    } else if (selectedNumbersCount === 7) {
        prize = numberPrizes['7 NUMBERS'];
    }

    // If play types are available, prioritize the highest prize from play types
    const playTypes = order?.game_cards?.[0]?.selected_play_types || [];
    if (playTypes.length > 0) {
        if (playTypes.includes('chance')) {
            prize = playTypePrizes.chance[0]; // Take the highest chance prize
        } else if (playTypes.includes('straight')) {
            prize = playTypePrizes.straight;
        } else if (playTypes.includes('ramble')) {
            prize = playTypePrizes.ramble;
        }
    }

    return prize || 'N/A';
};
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header and Filters (unchanged) -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Order Management</h1>
                    <p class="text-gray-600">View and filter orders by user and date range</p>
                </div>
                <div class="text-sm text-gray-500">
                    Total Orders: <span class="font-semibold text-gray-700">{{ filteredOrders.length }}</span>
                </div>
            </div>

            <!-- Filters (unchanged) -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select User</label>
                        <select v-model="filter.user_id"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date From</label>
                        <input v-model="filter.date_from" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date To</label>
                        <input v-model="filter.date_to" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Items per page</label>
                        <select v-model="itemsPerPage" @change="changeItemsPerPage(itemsPerPage)"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option :value="5">5 per page</option>
                            <option :value="10">10 per page</option>
                            <option :value="25">25 per page</option>
                            <option :value="50">50 per page</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button @click="resetFilters"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Table View (unchanged) -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Invoice No</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    User</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Total Price</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Sales Date</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="order in paginatedOrders" :key="order.id"
                                class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ order.invoice_no }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ order.user.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ order.product.title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ order.quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">{{ order.total_price }} AED
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(order.sales_date) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openModal(order)"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State (unchanged) -->
                    <div v-if="filteredOrders.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                        <p class="text-gray-500 mb-4">Try adjusting your filters to see more results.</p>
                    </div>
                </div>

                <!-- Pagination (unchanged) -->
                <div v-if="filteredOrders.length > 0" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                            to
                            <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredOrders.length)
                                }}</span>
                            of
                            <span class="font-medium">{{ filteredOrders.length }}</span>
                            results
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" :class="[
                                'px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                                currentPage === 1 ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400'
                            ]">
                                Previous
                            </button>
                            <div class="flex space-x-1">
                                <template v-for="(page, index) in pageNumbers" :key="index">
                                    <button v-if="page !== '...'" @click="goToPage(page)" :class="[
                                        'px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                                        page === currentPage ? 'text-white bg-blue-600 shadow-md' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400'
                                    ]">
                                        {{ page }}
                                    </button>
                                    <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
                                </template>
                            </div>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" :class="[
                                'px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                                currentPage === totalPages ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400'
                            ]">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Popup -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                    <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out"
                        :class="modalVisible ? 'opacity-50' : 'opacity-0'"></div>
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div
                            class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden transform transition-all">
                            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6 text-white">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h2 class="text-3xl font-bold">Order Invoice</h2>
                                        <p class="text-blue-100 mt-1">Order #{{ selectedOrder?.invoice_no }}</p>
                                    </div>
                                    <button @click="closeModal"
                                        class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition-all duration-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
                                <div v-if="selectedOrder" class="p-8">
                                    <!-- Product Information (unchanged) -->
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-6 mb-8">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-2xl font-bold text-gray-900">{{ selectedOrder.product.title
                                                }}</h3>
                                            <div class="flex items-center space-x-2">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <div class="space-y-3">
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Draw Date</p>
                                                        <p class="font-semibold">{{
                                                            formatDate(selectedOrder.product.draw_date) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Draw Time</p>
                                                        <p class="font-semibold">{{ selectedOrder.product.draw_time }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-3">
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-green-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z">
                                                        </path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Unit Price</p>
                                                        <p class="font-semibold text-green-600">{{
                                                            selectedOrder.product.price }}
                                                            AED</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-purple-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Quantity</p>
                                                        <p class="font-semibold">{{ selectedOrder.quantity }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-3">
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-indigo-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                        </path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Customer</p>
                                                        <p class="font-semibold">{{ selectedOrder.user.name }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <svg class="w-5 h-5 text-orange-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                                        </path>
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm text-gray-600">Total Amount</p>
                                                        <p class="font-bold text-lg text-green-600">{{
                                                            selectedOrder.total_price }}
                                                            AED</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tax Invoice Details (unchanged) -->
                                    <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 mb-8">
                                        <div class="text-center mb-6">
                                            <h3 class="text-xl font-bold text-gray-900 mb-2">TAX INVOICE</h3>
                                            <div
                                                class="w-20 h-1 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-4">
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">TRN Number</p>
                                                    <p class="font-semibold text-lg">{{ props.company.trn_no }}</p>
                                                </div>
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Invoice Number</p>
                                                    <p class="font-semibold text-lg">{{ selectedOrder.invoice_no }}</p>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Sales Date</p>
                                                    <p class="font-semibold text-lg">{{
                                                        formatDate(selectedOrder.sales_date) }}</p>
                                                </div>
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Product Name</p>
                                                    <p class="font-semibold text-lg">{{ selectedOrder.product.title }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ticket Details (unchanged) -->
                                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 mb-8">
                                        <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">Ticket Details</h3>
                                        <div class="flex justify-center mb-6">
                                            <div class="w-32 h-32 bg-white rounded-2xl shadow-lg overflow-hidden">
                                                <img v-if="selectedOrder.product.image_url"
                                                    :src="selectedOrder.product.image_url" alt="Product Image"
                                                    class="w-full h-full object-cover">
                                                <img v-else src="https://via.placeholder.com/128"
                                                    alt="Placeholder Image" class="w-full h-full object-cover">
                                            </div>
                                        </div>
                                        <div v-for="(card, index) in selectedOrder.game_cards" :key="index"
                                            class="mb-6">
                                            <div class="bg-white rounded-xl p-6 shadow-md">
                                                <h4 class="font-bold text-lg mb-4 text-center text-gray-800">Card {{
                                                    index + 1 }}
                                                </h4>
                                                <div class="mb-6">
                                                    <h5 class="font-semibold text-center mb-3 text-gray-700">Selected
                                                        Numbers</h5>
                                                    <div class="flex justify-center gap-3 flex-wrap">
                                                        <div v-for="n in card.selected_numbers" :key="n"
                                                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-full flex items-center justify-center font-bold text-lg shadow-lg">
                                                            {{ n }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="card.selected_play_types && card.selected_play_types.length > 0"
                                                    class="grid grid-cols-3 gap-4">
                                                    <div class="text-center p-3 bg-green-50 rounded-lg">
                                                        <h5 class="font-semibold text-green-800 mb-1">Straight</h5>
                                                        <p class="text-2xl font-bold"
                                                            :class="card.selected_play_types.includes('Straight') ? 'text-green-600' : 'text-gray-400'">
                                                            {{ card.selected_play_types.includes('Straight') ? '✓' : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                                                        <h5 class="font-semibold text-blue-800 mb-1">Rumble</h5>
                                                        <p class="text-2xl font-bold"
                                                            :class="card.selected_play_types.includes('Ramble') ? 'text-blue-600' : 'text-gray-400'">
                                                            {{ card.selected_play_types.includes('Ramble') ? '✓' : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="text-center p-3 bg-purple-50 rounded-lg">
                                                        <h5 class="font-semibold text-purple-800 mb-1">Chance</h5>
                                                        <p class="text-2xl font-bold"
                                                            :class="card.selected_play_types.includes('Chance') ? 'text-purple-600' : 'text-gray-400'">
                                                            {{ card.selected_play_types.includes('Chance') ? '✓' : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Point of Sales Details -->
                                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-6 mb-8">
                                        <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">Point of Sales
                                            Details</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-4">
                                                <div class="p-4 bg-white rounded-xl shadow-sm">
                                                    <p class="text-sm text-gray-600">Vendor Name</p>
                                                    <p class="font-semibold text-lg">{{ selectedOrder.user.name }}</p>
                                                </div>
                                                <div class="p-4 bg-white rounded-xl shadow-sm">
                                                    <p class="text-sm text-gray-600">Address</p>
                                                    <p class="font-semibold text-lg">{{ props.company.address }}</p>
                                                </div>
                                            </div>
                                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                                <p class="text-sm text-gray-600">{{ selectedOrder.product.title }} Big Prize on {{ formatDate(selectedOrder.sales_date)
                                                }} </p>
                                                <p class="text-2xl font-bold text-yellow-600 mt-2">{{
                                                    getBigPrize(selectedOrder) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Code Section (unchanged) -->
                                    <div class="text-center mb-8">
                                        <div class="inline-block p-4 bg-white rounded-2xl shadow-lg">
                                            <img src="https://via.placeholder.com/120" alt="QR Code"
                                                class="w-30 h-30 mx-auto mb-2">
                                            <p class="text-sm text-gray-600">Scan for verification</p>
                                        </div>
                                    </div>

                                    <!-- Company Details -->
                                    <div
                                        class="bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-2xl p-6 text-center">
                                        <h3 class="text-xl font-bold mb-4">{{ props.company.name }}</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <svg class="w-5 h-5 mx-auto mb-2 text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                    </path>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <p class="text-gray-300">Address:</p>
                                                <p>{{ props.company.address }}</p>
                                            </div>
                                            <div>
                                                <svg class="w-5 h-5 mx-auto mb-2 text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                                <p class="text-gray-300">Email:</p>
                                                <p>{{ props.company.email }}</p>
                                            </div>
                                            <div>
                                                <svg class="w-5 h-5 mx-auto mb-2 text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9m0 9c-5 0-9-4-9-9s4-9 9-9">
                                                    </path>
                                                </svg>
                                                <p class="text-gray-300">Website:</p>
                                                <p>{{ props.company.website }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="bg-gray-50 px-8 py-4 border-t border-gray-200 flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    Generated on {{ new Date().toLocaleDateString() }}
                                </div>
                                <div class="flex space-x-3">
                                    <button @click="window.print()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                            </path>
                                        </svg>
                                        <span>Print</span>
                                    </button>
                                    <button @click="closeModal"
                                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar and print styles (unchanged) */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@media print {
    .fixed {
        position: static !important;
    }

    .bg-black {
        display: none !important;
    }

    .shadow-2xl {
        box-shadow: none !important;
    }
}
</style>