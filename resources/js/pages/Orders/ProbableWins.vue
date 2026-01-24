<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Probable Wins',
        href: '/probable-wins',
    },
];
const { company_setting } = usePage().props;

const { filters, products, product_prizes, product, summary } = defineProps<{
    summary: Array<any>;
    products: Array<any>;
    filters: Record<string, any>;
    product_prizes: Array<any>;
    product: Array<any>;
}>();

console.log(summary)
const filter = ref({
    user_id: filters?.user_id ?? '',
    date_from: filters?.date_from ?? '',
    time_from: filters?.time_from ?? '',
    date_to: filters?.date_to ?? '',
    time_to: filters?.time_to ?? '',
    match_type: filters?.match_type ?? '',
    category_id: filters?.category_id ?? '',
    product_id: filters?.product_id ?? '',
    pick_number: filters?.pick_number ?? [],
    btn: filters?.btn ?? ''
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
        pick_number: [],
        btn: ''
    };
    router.get(
        route('probable-wins.index')
    );
}

const generateRandomNumbers = () => {
    if (!product || !product.pick_number) return;

    filter.value.pick_number = [];

    for (let i = 0; i < product.pick_number; i++) {
        filter.value.pick_number.push(
            Math.floor(Math.random() * product.type_number) + 1
        );
    }
};


const handleSearch = () => {
    filter.value.btn = 'search';
    router.get(
        route('probable-wins.index'),
        { ...filter.value },
        {
            preserveScroll: true,
            replace: true,
            showProgress: false
        }
    );
};

</script>

<template>

    <Head title="Probable Wins" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 items-center">
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
                    <div v-if="product_prizes?.length">
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                        <input type="date" v-model="filter.date_from"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Time</label>
                        <input type="time" v-model="filter.time_from"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                        <input type="date" v-model="filter.date_to"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Time</label>
                        <input type="time" v-model="filter.time_to"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                </div>
                <div class="mt-3" v-if="product">
                    <label for="" class="block text-center font-bold mb-2">Number</label>
                    <div class="flex justify-center gap-2 md:gap-5 overflow-x-auto w-full">
                        <input v-for="(_, index) in product.pick_number" :key="index" type="text" inputmode="numeric"
                            pattern="[0-9]*" v-model="filter.pick_number[index]" class="h-10 md:h-12 w-10 md:w-12 border-2 border-gray-400 rounded-lg
         flex items-center justify-center
         text-center
         text-lg font-semibold" />

                    </div>
                    <div class="text-center mt-3">
                        <button @click="generateRandomNumbers"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition cursor-pointer">
                            Generate Random Numbers
                        </button>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button @click="resetFilters"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                        Reset Filters
                    </button>
                    <button @click="handleSearch"
                        class="px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-all duration-200 font-semibold ml-2">
                        Search
                    </button>
                </div>
            </div>

            <!-- Table View -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Match Type
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Winners
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Prize Per Winner
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Total Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(win, index) in summary" :key="index"
                                class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ win.match_type }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <p>{{ win.winners }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <p>{{ win.prize_per_winner }} {{ company_setting?.currency }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ win.total_amount }} {{ company_setting?.currency }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div class="text-center py-12" v-if="summary.length === 0">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No records found</h3>
                        <p class="text-gray-500 mb-4">Try adjusting your filters to see more results.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
