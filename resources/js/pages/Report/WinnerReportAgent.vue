<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { ref, nextTick, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { BreadcrumbItem } from '@/types';
import Multiselect from '@vueform/multiselect'
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Winner Report Agent',
        href: '/reports/winner-report-agent',
    },
];
const {agents, wins, products } = defineProps<{
    agents: Array<any>;
    wins: Array<any>;
    products: Array<any>;
}>();

console.log(agents)
const errors = ref([]);
const showModal = ref(false);
const isChecking = ref(false);
const checkData = ref([]);
const checkMsg = ref('');



const form = useForm({
    agent: '',
    from_date: '',
    to_date: ''
});

const handleSearch = () => {
    form.get(route('reports.winner-report-agent'), {
        showProgress: false,
        preserveState: true
    })
}

const checkmsg = computed(() => checkMsg.value)
const handleCheck = async () => {
   
   
};


const handleClose = () => {
    showModal.value = false;
    checkData.value = [];
    errors.value = [];
    checkMsg.value = '';
    isClaimed.value = false;
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

    <Head title="Winner Report" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 p-3">
                                <div class="bg-white rounded-2xl shadow-lg">
                                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 p-3 mb-2 items-center">
                                        <div class="group">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Agent <span class="text-red-500">*</span>
                                            </label>
                                            <Multiselect
                                                v-model="form.agent"
                                                :options="agents"
                                                valueProp="id"
                                                label="name"
                                                placeholder="Agent..."
                                                :searchable="true"
                                                class="w-full"
                                            />

                                            <p v-if="form.errors.agent" class="text-red-600 text-sm">
                                                {{ form.errors.agent }}
                                            </p>
                                        </div>
                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                                From Date
                                            </label>
                                            <Input v-model="form.from_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                                        </div>
                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                                To Date
                                            </label>
                                            <Input v-model="form.to_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                                        </div>
                                        <div class="flex items-center flex-col">
                                            <button @click="handleSearch"
                                                class="cursor-pointer px-6 py-2 bg-gradient-to-r from-teal-500 to-green-500 text-white rounded-xl hover:from-teal-600 hover:to-green-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                                                Search
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Table View -->
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead
                                                class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                                <tr>
                                                    
                                                    <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Vendor Name
                                                    </th>
                                                    <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Address
                                                    </th>
                                                    <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Total Prize
                                                    </th>
                                                    <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Claimed
                                                    </th>
                                                     <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Unclaimed
                                                    </th>
                                                    <th
                                                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr v-for="win in wins?.data" :key="win.id"
                                                    class="hover:bg-orange-50 transition-colors duration-200">
                                                    
                                                    <td class="px-4 py-4 text-sm text-gray-900">
                                                        <p class="font-medium text-gray-900">{{ win?.user?.name }}</p>
                                                       

                                                    </td>
                                                     <td class="px-4 py-4 text-sm text-gray-900">
                                                        <p class="font-medium text-gray-900">{{ win?.user?.address }}</p>
                                                    </td>
                                                   
                                                    <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                                        {{ win?.check_win?.total_prize }}
                                                    </td>
                                                    <td
                                                        class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap font-medium">
                                                        </td>
                                                    <td
                                                        class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap font-medium">
                                                        </td>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <button @click="handleIndividualClaim(win.invoice_no)"
                                                            class="inline-flex items-center cursor-pointer px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7">
                                                                </path>
                                                            </svg>
                                                            All Winner
                                                        </button>
                                                        <button 
                                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg cursor-pointer">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                            Claimed Only
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mt-4 flex justify-end py-5 px-6">
                                            <nav class="flex items-center space-x-1">
                                                <button v-for="(link, i) in wins?.links" :key="i"
                                                    @click="goTo(link.url)" v-html="link.label" :disabled="!link.url"
                                                    :class="[
                                                        'px-3 py-1 rounded border transition-all duration-200',
                                                        link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                                    ]" />
                                            </nav>
                                        </div>
                                        <!-- Empty State -->
                                        <div class="text-center py-12" v-if="false">
                                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                                                </path>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No records found</h3>
                                            <p class="text-gray-500 mb-4">Try adjusting your filters to see more
                                                results.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-400/20">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all relative">
                    <!-- Close Button -->
                    <button v-if="isChecking === false" @click="handleClose"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>

                    <div class="text-center" v-if="isChecking">
                        <!-- Animated Spinner -->
                        <div class="relative mx-auto w-20 h-20 mb-6">
                            <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                            <div
                                class="absolute inset-0 border-4 border-t-orange-500 border-r-amber-500 border-b-transparent border-l-transparent rounded-full animate-spin">
                            </div>
                        </div>

                        <!-- Loading Text -->
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Checking</h3>
                        <p class="text-gray-600">Please wait while we verify...</p>

                        <!-- Animated Dots -->
                        <div class="flex justify-center items-center gap-2 mt-4">
                            <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 0ms">
                            </div>
                            <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce"
                                style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce"
                                style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                    
                    <!-- Summary State -->

                    
                    <div class="text-center" v-if="isChecking === false && errors && Object.keys(errors).length > 0">
                        <div class="bg-red-100 text-red-600 p-3 rounded-md">
                            <ul class="list-disc list-inside">
                                <li v-for="(msgs, field) in errors" :key="field">
                                    {{ msgs[0] }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
