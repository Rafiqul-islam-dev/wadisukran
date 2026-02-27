<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product Wise Report',
        href: '/sales-report/product-wise',
    },
];
const { company_setting } = usePage().props;
const { data, agents } = defineProps<{
    data: Array<any>;
    agents: Array<any>;
}>();

console.log(data)

const errors = ref<Record<string, string>>({});
const form = useForm({
    agent_id: '',
    from_date: '',
    to_date: '',
});

const handleSearch = () => {
    const messages: Record<string, string> = {};
    let valid = true;
    if(!form.from_date){
        messages.from_date = 'From date is required';
        valid = false;
    }
    if(!form.agent_id){
        messages.agent_id = 'Agent is required';
        valid = false;
    }
    if(!form.to_date){
        messages.to_date = 'To date is required';
        valid = false;
    }

    if(valid === false){
        errors.value = messages;
        return;
    }
    errors.value = messages;

    form.get('/sales-report/product-wise', {
        preserveState: true,
        preserveScroll: true,
    });
}


const reportData = ref([
    {
        name: 'Infinity 1',
        total_sales: 1500,
        total_commission: 150,
        total_prize_paid: 0,
    },
    {
        name: 'Infinity 2',
        total_sales: 2300,
        total_commission: 230,
        total_prize_paid: 100,
    },
    {
        name: 'Infinity 3',
        total_sales: 870,
        total_commission: 87,
        total_prize_paid: 50,
    },
]);

const storeInfo = {
    name: 'Didarul Mobile',
    location: 'Firozmora Dubai UAE',
    trn: '5756852365',
};

</script>

<template>
    <Head title="Product Wise Report" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-4">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 items-center">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Agent</label>
                        <select v-model="form.agent_id" :class="errors.agent_id ? 'border-red-400' : ''"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Agents</option>
                            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                {{ agent.name }}
                            </option>
                        </select>
                        <p class="text-red-500 text-sm mt-1" v-if="errors.agent_id">{{ errors.agent_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Date <span class="text-red-500">*</span></label>
                        <input type="date" v-model="form.from_date" :class="errors.from_date ? 'border-red-400' : ''"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                        <p class="text-red-500 text-sm mt-1" v-if="errors.from_date">{{ errors.from_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Date <span class="text-red-500">*</span></label>
                        <input type="date" v-model="form.to_date" :class="errors.to_date ? 'border-red-400' : ''"
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                        <p class="text-red-500 text-sm mt-1" v-if="errors.to_date">{{ errors.to_date }}</p>
                    </div>
                    <button @click="handleSearch"
                        class="px-4 cursor-pointer py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2 text-center w-[50%] m-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search
                    </button>

                </div>
            </div>
             <!-- Receipt -->
            <div class="flex justify-center">
                <div id="printDiv" class="bg-white w-2xl shadow-2xl">
                    <div class="px-6 pb-4 pt-1">
                        <!-- Logo & Store Info -->
                        <div class="flex flex-col items-center mb-4">
                            <div class="w-36 h-36 rounded-full flex items-center justify-center mb-3 shadow-md">
                               <img :src="company_setting.logo_url" alt="">
                            </div>
                            <h2 class="text-md font-bold text-gray-800 tracking-wide">{{ data?.name }}</h2>
                            <p class="text-md text-gray-500 mt-0.5">{{ data?.address }}</p>
                        </div>

                        <!-- Dashed Divider -->
                        <div class="border-t border-dashed border-gray-300 my-3"></div>

                        <!-- Date & To Row -->
                        <div class="flex justify-between text-gray-700 py-1">
                            <span>Date: <span class="font-medium">{{ data?.from_date || 'null' }}</span></span>
                            <span>To: <span class="font-medium">{{ data?.to_date || 'null' }}</span></span>
                        </div>

                        <!-- Dashed Divider -->
                        <div class="border-t border-dashed border-gray-300 my-3"></div>

                        <!-- TRN -->
                        <div class="flex justify-between text-gray-700 py-1">
                            <span class="font-semibold">TRN:</span>
                            <span class="font-mono tracking-widest font-semibold text-gray-800">{{ data?.trn }}</span>
                        </div>

                        <!-- Dashed Divider -->
                        <div class="border-t border-dashed border-gray-300 my-3"></div>

                        <!-- Report Title -->
                        <h3 class="text-center font-bold text-gray-800 mb-4">Daily Summary Report</h3>

                        <!-- Static Product Rows -->
                        <div v-for="(product, index) in data?.products?.data" :key="index">
                            <!-- Product Name -->
                            <p class="text-center font-bold text-gray-700 mb-2">{{ product.product_title }}</p>

                            <!-- Rows -->
                            <div class="space-y-1 mb-3">
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Sales:</span>
                                    <span class="font-mono font-semibold">{{ product.total_price }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Commission:</span>
                                    <span class="font-mono font-semibold">{{ product.total_commission }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Prize Paid:</span>
                                    <span class="font-mono font-semibold">{{ product.total_prize_paid }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Cancel Order:</span>
                                    <span class="font-mono font-semibold">{{ product.cancel_orders }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 bg-gray-100 p-4 rounded-lg">
                            <p class="text-center font-bold text-gray-700 mb-2">Total</p>

                            <!-- Rows -->
                            <div class="space-y-1 mb-3">
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Sales:</span>
                                    <span class="font-mono font-semibold">{{ data?.total_sell }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Commission:</span>
                                    <span class="font-mono font-semibold">{{ data?.total_commission }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Prize Paid:</span>
                                    <span class="font-mono font-semibold">{{ data?.total_prize_paid }}</span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Total Cancel Orders:</span>
                                    <span class="font-mono font-semibold">{{ data?.total_cancel_orders }}</span>
                                </div>
                            </div>
                        </div>

                         <button v-print="'#printDiv'"
                            class="hidden-print text-center mt-2 m-auto px-4 cursor-pointer py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2 text-center">
                            Print Pdf
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


<style scoped>
@media print {
    .hidden-print {
        display: none !important;
    }
    @page {
        margin-top: 20mm;
        margin-bottom: 0;
    }
    #printDiv {
        background: none !important;
        box-shadow: none !important;
        width: 100% !important;
    }
}
</style>

