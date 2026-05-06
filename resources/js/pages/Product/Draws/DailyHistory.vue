<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Daily History', href: '/draws/histories-daily' },
];

const { products, histories, filters } = defineProps<{
    products: Array<any>;
    histories: Array<any>;
    filters: any;
}>();

const form = useForm({
    start_date: filters.start_date || '',
    end_date: filters.end_date || '',
    start_time: filters.start_time || '',
    end_time: filters.end_time || ''
});

const formatDate = (date: string) => {
    const parsedDate = new Date(date);
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
    }).format(parsedDate);
};

const formatTime = (time: string) => {
    if (!time) return '';
    const date = new Date(time);
    return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(date);
};

const parseNumbers = (winNumber: any): string[] => {
    if (!winNumber) return [];
    if (Array.isArray(winNumber)) return winNumber.map(String);

    if (typeof winNumber === 'string') {
        try {
            const parsed = JSON.parse(winNumber);
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch {
            return [];
        }
    }

    return [];
};

const handleSearch = () => {
    form.clearErrors();
    
    let hasError = false;
    if (!form.start_date) {
        form.setError('start_date', 'The from date field is required.');
        hasError = true;
    }
    if (!form.end_date) {
        form.setError('end_date', 'The to date field is required.');
        hasError = true;
    }

    if (hasError) return;

    form.get(route('draws.histories_daily'), {
        preserveScroll: true,
        replace: true,
        showProgress: false,
        preserveState: true
    });
};
</script>

<template>
    <Head title="Daily History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">

                    <!-- Filters -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4 items-end">
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">From Date</label>
                                <Input v-model="form.start_date" type="date" class="w-full h-12" :class="{'border-red-500': form.errors.start_date}" />
                                <div v-if="form.errors.start_date" class="text-red-500 text-xs mt-1 text-center">{{ form.errors.start_date }}</div>
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">From Time</label>
                                <Input v-model="form.start_time" type="time" class="w-full h-12" />
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">To Date</label>
                                <Input v-model="form.end_date" type="date" class="w-full h-12" :class="{'border-red-500': form.errors.end_date}" />
                                <div v-if="form.errors.end_date" class="text-red-500 text-xs mt-1 text-center">{{ form.errors.end_date }}</div>
                            </div>

                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">To Time</label>
                                <Input v-model="form.end_time" type="time" class="w-full h-12" />
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    @click="handleSearch"
                                    class="h-12 px-8 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md transition-all flex items-center gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="border rounded-lg overflow-x-auto shadow-sm">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[60px]">SL</th>
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[130px]">Date</th>
                                    <th 
                                        v-for="product in products" 
                                        :key="product.id"
                                        class="px-4 py-4 text-center text-sm font-bold text-gray-800 border min-w-[220px]"
                                    >
                                        {{ product.title }} {{ product.product_number }}
                                    </th>
                                    <th class="px-4 py-4 text-center text-sm font-bold text-gray-700 border min-w-[100px]">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="histories.length === 0">
                                    <td :colspan="products.length + 3" class="px-6 py-10 text-center text-gray-500 italic">
                                        Please select date range to view daily history.
                                    </td>
                                </tr>
                                <tr v-for="(history, index) in histories" :key="index" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-6 text-center text-sm font-bold text-gray-900 border">
                                        {{ index + 1 }}
                                    </td>

                                    <td class="px-4 py-6 text-center text-sm font-medium text-gray-700 border whitespace-nowrap">
                                        {{ formatDate(history.date) }}
                                    </td>

                                    <td 
                                        v-for="product in products" 
                                        :key="product.id"
                                        class="px-4 py-6 border text-center"
                                    >
                                        <div class="flex flex-col items-center gap-2">
                                            <template v-if="history.results[product.id]">
                                                <div class="flex gap-2 justify-center flex-nowrap mb-1">
                                                    <div
                                                        v-for="(number, idx) in parseNumbers(history.results[product.id].numbers)"
                                                        :key="idx"
                                                        class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-center font-bold border-2 border-gray-800 text-gray-900 bg-white shadow-sm"
                                                    >
                                                        {{ number }}
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded shadow-sm">
                                                    {{ formatTime(history.results[product.id].time) }}
                                                </span>
                                            </template>
                                            <span v-else class="text-gray-300 text-xs">No result</span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-6 border">
                                        <div class="flex gap-2 justify-center">
                                            <Button
                                                size="sm"
                                                class="bg-indigo-500 hover:bg-indigo-600 text-white p-2 h-9 w-9 rounded-md shadow"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Button>
                                            <Button
                                                size="sm"
                                                class="bg-red-500 hover:bg-red-600 text-white p-2 h-9 w-9 rounded-md shadow"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
table {
    border-spacing: 0;
}
th, td {
    border: 1px solid #e5e7eb;
}
</style>
