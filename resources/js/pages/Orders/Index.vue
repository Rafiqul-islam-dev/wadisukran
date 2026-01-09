<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';


const { orders, users, company, filters, categories, products, product_prizes } = defineProps<{
    orders: Array<any>;
    users: Array<any>;
    company: Record<string, any>;
    categories: Array<any>;
    products: Array<any>;
    filters: Record<string, any>;
    product_prizes: Array<any>;
}>();

console.log(orders)

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

const modalVisible = ref(false);




const selectedOrder = ref<any | null>(null);
const showModal = ref(false);

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

function formatDate(dateString: string | null) {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    });
}


function openModal(order: any) {
    // console.log(order)
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

const handleSearch = () => {
    router.get(
        route('orders.index'),
        { ...filter.value },
        {
            preserveScroll: true,
            replace: true,
            showProgress: false
        }
    );
};

function goTo(url) {
    if (!url) return

    // page from pagination link
    const page = new URL(url).searchParams.get('page')

    // current params (filters/search/sort etc)
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
    <AppLayout>
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg px-4 py-2 mb-4">
                <div class="grid grid-cols-2 md:grid-cols-9 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <select v-model="filter.category_id" v-on:change="handleSearch"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Product</label>
                        <select v-model="filter.product_id" v-on:change="handleSearch"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Products</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.title }}
                            </option>
                        </select>
                    </div>
                    <div v-if="product_prizes.length">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Match Type</label>
                        <select v-model="filter.match_type"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All</option>
                            <option v-for="prize in product_prizes" :key="prize.id" :value="prize.id">
                                {{ prize.name }} {{ prize.chance_number }}
                            </option>
                        </select>
                    </div>
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                        <input v-model="filter.date_from" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Time</label>
                        <input v-model="filter.time_from" type="time"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                        <input v-model="filter.date_to" type="date"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Time</label>
                        <input v-model="filter.time_to" type="time"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div class="flex items-center justify-center gap-3">
                        <button v-on:click="handleSearch"
                            class="px-4 py-1 text-white rounded-lg bg-teal-500 text-xl">Search</button>
                        <button @click="resetFilters"
                            class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                            Reset
                        </button>
                    </div>
                </div>
                <!-- <div class="mt-4 flex justify-end">
                    <button @click="resetFilters"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                        Reset Filters
                    </button>
                </div> -->
            </div>

            <!-- Table View -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Invoice No
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    User Type
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Vendor
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Sales Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Product
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Type
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Raffile Ticket
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Total Price
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Vat (%)
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Commission (%)
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="order in orders.data" :key="order.id"
                                class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ order.invoice_no }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <p>{{ order.user.user_type }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <p>{{ order.user.name }}</p>
                                    <p class="text-teal-500">{{ order.user?.agent?.username }}</p>
                                    <p class="text-red-400">{{ order.user?.address }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ formatDate(order.sales_date) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ order.product.title }}</td>
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
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ order.quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">
                                    {{ order.total_price }} AED
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">
                                    {{ order.vat }} ({{ order.vat_percentage }}%)
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">
                                    {{ order.commission }} ({{ order.commission_percentage }}%)
                                </td>
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

                    <div class="mt-4 flex justify-end py-5">
                        <nav class="flex items-center space-x-1">
                            <button v-for="(link, i) in orders.links" :key="i" @click="goTo(link.url)"
                                v-html="link.label" :disabled="!link.url" :class="[
                                    'px-3 py-1 rounded border',
                                    link.active ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]" />

                        </nav>
                    </div>

                    <!-- Empty State -->
                    <div v-if="orders.data?.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                        <p class="text-gray-500 mb-4">Try adjusting your filters to see more results.</p>
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
                            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-3 text-white">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h2 class="text-3xl font-bold">Order Invoice</h2>
                                        <p class="text-blue-100 mt-1">
                                            Order #{{ selectedOrder?.invoice_no }}
                                        </p>
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
                                    <!-- Product Information -->
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-6 mb-8">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-2xl font-bold text-gray-900">
                                                {{ selectedOrder.product?.title }}
                                            </h3>
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
                                                        <p class="font-semibold">
                                                            {{ formatDate(selectedOrder.product.draw_date) }}
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
                                                        <p class="font-semibold">
                                                            {{ selectedOrder.product.draw_time }}
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
                                                        <p class="font-semibold text-green-600">
                                                            {{ selectedOrder.product.price }} AED
                                                        </p>
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
                                                        <p class="font-semibold">
                                                            {{ selectedOrder.user.name }}
                                                        </p>
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
                                                        <p class="font-bold text-lg text-green-600">
                                                            {{ selectedOrder.total_price }} AED
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tax Invoice Details -->
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
                                                    <p class="font-semibold text-lg">{{ company.trn_no }}</p>
                                                </div>
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Invoice Number</p>
                                                    <p class="font-semibold text-lg">
                                                        {{ selectedOrder.invoice_no }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Sales Date</p>
                                                    <p class="font-semibold text-lg">
                                                        {{ formatDate(selectedOrder.sales_date) }}
                                                    </p>
                                                </div>
                                                <div class="p-4 bg-gray-50 rounded-xl">
                                                    <p class="text-sm text-gray-600">Product Name</p>
                                                    <p class="font-semibold text-lg">
                                                        {{ selectedOrder.product.title }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ticket Details -->
                                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 mb-8">
                                        <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">
                                            Ticket Details
                                        </h3>
                                        <div class="flex justify-center mb-6">
                                            <div class="w-32 h-32 bg-white rounded-2xl shadow-lg overflow-hidden">
                                                <img v-if="selectedOrder.product.image_url"
                                                    :src="selectedOrder.product.image_url" alt="Product Image"
                                                    class="w-full h-full object-cover" />
                                                <img v-else src="https://via.placeholder.com/128"
                                                    alt="Placeholder Image" class="w-full h-full object-cover" />
                                            </div>
                                        </div>
                                        <div v-for="(card, index) in selectedOrder.tickets" :key="index" class="mb-6">
                                            <div class="bg-white rounded-xl p-6 shadow-md">
                                                <h4 class="font-bold text-lg mb-4 text-center text-gray-800">
                                                    Card {{ index + 1 }}
                                                </h4>
                                                <div class="mb-6">
                                                    <h5 class="font-semibold text-center mb-3 text-gray-700">
                                                        Selected Numbers
                                                    </h5>
                                                    <div class="flex justify-center gap-3 flex-wrap">
                                                        <div v-for="n in card.selected_numbers" :key="n"
                                                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-full flex items-center justify-center font-bold text-lg shadow-lg">
                                                            {{ n }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="
                                                    card.selected_play_types &&
                                                    card.selected_play_types.length > 0
                                                " class="grid grid-cols-3 gap-4">
                                                    <div class="text-center p-3 bg-green-50 rounded-lg">
                                                        <h5 class="font-semibold text-green-800 mb-1">
                                                            Straight
                                                        </h5>
                                                        <p class="text-2xl font-bold" :class="card.selected_play_types.includes(
                                                            'Straight',
                                                        )
                                                            ? 'text-green-600'
                                                            : 'text-gray-400'
                                                            ">
                                                            {{
                                                                card.selected_play_types.includes(
                                                                    'Straight',
                                                                )
                                                                    ? '✓'
                                                                    : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                                                        <h5 class="font-semibold text-blue-800 mb-1">
                                                            Rumble
                                                        </h5>
                                                        <p class="text-2xl font-bold" :class="card.selected_play_types.includes(
                                                            'Ramble',
                                                        )
                                                            ? 'text-blue-600'
                                                            : 'text-gray-400'
                                                            ">
                                                            {{
                                                                card.selected_play_types.includes(
                                                                    'Ramble',
                                                                )
                                                                    ? '✓'
                                                                    : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="text-center p-3 bg-purple-50 rounded-lg">
                                                        <h5 class="font-semibold text-purple-800 mb-1">
                                                            Chance
                                                        </h5>
                                                        <p class="text-2xl font-bold" :class="card.selected_play_types.includes(
                                                            'Chance',
                                                        )
                                                            ? 'text-purple-600'
                                                            : 'text-gray-400'
                                                            ">
                                                            {{
                                                                card.selected_play_types.includes(
                                                                    'Chance',
                                                                )
                                                                    ? '✓'
                                                                    : '✗'
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Point of Sales Details -->
                                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-6 mb-8">
                                        <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">
                                            Point of Sales Details
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-4">
                                                <div class="p-4 bg-white rounded-xl shadow-sm">
                                                    <p class="text-sm text-gray-600">Vendor Name</p>
                                                    <p class="font-semibold text-lg">
                                                        {{ selectedOrder.user.name }}
                                                    </p>
                                                </div>
                                                <div class="p-4 bg-white rounded-xl shadow-sm">
                                                    <p class="text-sm text-gray-600">Address</p>
                                                    <p class="font-semibold text-lg">
                                                        {{ company.address }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                                <p class="text-sm text-gray-600">
                                                    {{ selectedOrder.product.title }} Big Prize on
                                                    {{ formatDate(selectedOrder.sales_date) }}
                                                </p>
                                                <p class="text-2xl font-bold text-yellow-600 mt-2">
                                                    <!-- {{ getBigPrize(selectedOrder) }} -->
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Code Section -->
                                    <div class="text-center mb-8">
                                        <div class="inline-block p-4 bg-white rounded-2xl shadow-lg">
                                            <img src="https://via.placeholder.com/120" alt="QR Code"
                                                class="w-30 h-30 mx-auto mb-2" />
                                            <p class="text-sm text-gray-600">
                                                Scan for verification
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Company Details -->
                                    <div
                                        class="bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-2xl p-6 text-center">
                                        <h3 class="text-xl font-bold mb-4">{{ company.name }}</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <svg class="w-5 h-5 mx-auto mb-2 text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 111.314 0z">
                                                    </path>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <p class="text-gray-300">Address:</p>
                                                <p>{{ company.address }}</p>
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
                                                <p>{{ company.email }}</p>
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
                                                <p>{{ company.website }}</p>
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
