<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, nextTick, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Probable Wins',
        href: '/probable-wins',
    },
];
const { company_setting } = usePage().props;

const { filters, products, product_prizes, product, summary, orders } = defineProps<{
    orders: Array<any>;
    summary: Array<any>;
    products: Array<any>;
    filters: Record<string, any>;
    product_prizes: Array<any>;
    product: Array<any>;
}>();

console.log(orders);

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

const inputs = ref<HTMLInputElement[]>([]);

const handleInput = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement;
  target.value = target.value.replace(/\D/g, '');
  filter.value.pick_number[index] = target.value;

  if (!product) return;

  const maxLength = product.type_number <= 9 ? 1 : 2;

  if (target.value.length >= maxLength) {
    nextTick(() => {
      const next = inputs.value[index + 1];
      if (next) {
        next.focus();
      }
    });
  }
};

const copyNumbers = async () => {
  if (!filter.value.pick_number.length) return;

  const text = filter.value.pick_number.join(',');

  try {
    await navigator.clipboard.writeText(text);
    toast.success('Copied: ' + text); // or use toast if you have one
  } catch (err) {
    console.error('Copy failed', err);
  }
};


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
                       <input
                            v-for="(_, index) in product.pick_number"
                            :key="index"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            v-model="filter.pick_number[index]"
                            @input="handleInput(index, $event)"
                            ref="inputs"
                            class="h-10 md:h-12 w-10 md:w-12 border-2 border-gray-400 rounded-lg text-center text-lg font-semibold"
                        />
                    </div>
                    <div class="text-center mt-3 flex gap-3 justify-center">
                        <button @click="generateRandomNumbers"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition cursor-pointer">
                            Generate Random Numbers
                        </button>
                       <button @click="copyNumbers"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition cursor-pointer border-none">
                        Copy
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
           <!-- vendor list -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 mt-3">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-amber-500 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h2 class="text-white font-bold text-lg">Winner Vendors</h2>
                    </div>
                    <span class="bg-white bg-opacity-20 text-black text-sm font-semibold px-3 py-1 rounded-full">
                        {{ orders?.length ?? 0 }} Winners
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-orange-50 border-b-2 border-orange-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">
                                    #
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Vendor
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Raffle Ticket
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Match Type
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Win Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(order, index) in orders" :key="index"
                                class="hover:bg-orange-50 transition-colors duration-200 group">

                                <!-- SL -->
                                <td class="px-6 py-4">
                                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-bold">
                                        {{ index + 1 }}
                                    </span>
                                </td>

                                <!-- Vendor Name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ order.vendor_name }}</p>
                                            <p class="text-xs text-gray-400">Ticket #{{ order.id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Raffle Ticket Numbers -->
                                <td class="px-6 py-4">
                                    <div class="flex gap-1 flex-wrap">
                                        <span v-for="number in order.selected_numbers" :key="number"
                                            class="w-8 h-8 bg-gradient-to-br from-orange-400 to-amber-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-sm">
                                            {{ number }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Match Type -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-green-100 text-green-700': order.match_type === 'Straight',
                                            'bg-blue-100 text-blue-700': order.match_type === 'Rumble',
                                            'bg-purple-100 text-purple-700': order.match_type?.startsWith('Chance'),
                                            'bg-indigo-100 text-indigo-700': order.match_type?.startsWith('Number'),
                                        }">
                                        {{ order.match_type }}
                                    </span>
                                </td>

                                <!-- Win Amount -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-green-600">
                                            {{ order.win_amount }} {{ company_setting?.currency }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Footer Total -->
                        <tfoot v-if="orders?.length > 0">
                            <tr class="bg-gradient-to-r from-orange-50 to-amber-50 border-t-2 border-orange-200">
                                <td colspan="4" class="px-6 py-4 text-sm font-bold text-gray-700 text-right">
                                    Total Win Amount:
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-base font-bold text-green-600">
                                        {{ orders.reduce((sum, o) => sum + (o.win_amount || 0), 0) }} {{ company_setting?.currency }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Empty State -->
                    <div class="text-center py-16" v-if="!orders || orders.length === 0">
                        <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No winners found</h3>
                        <p class="text-gray-400 text-sm">Try adjusting your filters to see results.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
