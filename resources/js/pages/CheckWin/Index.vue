<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage, Inertia } from '@inertiajs/vue3';
import axios from 'axios';
import { Ref, ref } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Check Wins',
        href: '/check-wins',
    },
];


const invoice_no = ref('');
const errors = ref([]);

const showModal = ref(false);
const isChecking = ref(false);
const checkData = ref([]);

const handleCheck = async () => {
    if (!invoice_no.value) {
        toast.error('Please write the invoice number first.');
        return false;
    }

    showModal.value = true;
    isChecking.value = true;

    try {
        // Make the POST request using Axios
        const response = await axios.post(route('check-win'), {
            invoice_no: invoice_no.value,  // Send invoice_no as part of the body
        });

        // Update checkData with the response data
        checkData.value = response.data.summery;  // Assuming the response contains a `summery` key
        console.log('Summary received:', checkData.value);

        // Handle other actions after success
        isChecking.value = false;
    } catch (error) {
        // Handle error
        console.error('Error fetching data:', error);
        isChecking.value = false;
        errors.value = error.response?.data?.errors || [];
        toast.error('Failed to check invoice. Please try again.');
    }
};



</script>
<template>

    <Head title="Check Wins" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 items-center">
                    <div>
                        <input v-model="invoice_no" type="text" placeholder="Invoice No.."
                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div class="flex items-center flex-col">
                        <button @click="handleCheck"
                            class="cursor-pointer px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                            Check
                        </button>
                    </div>
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
                                    Invoice No
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Ticket No
                                </th>
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
                                    Product
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div class="text-center py-12" v-if="false">
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

        <!-- Loading Modal -->
        <Transition enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-400/20">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 transform transition-all relative">
                    <!-- Close Button -->
                    <button v-if="isChecking === false" @click="showModal = false"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
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
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Checking Invoice</h3>
                        <p class="text-gray-600">Please wait while we verify your invoice...</p>

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
                    <div class="text-center" v-if="checkData.value && checkData.value.length > 0">
                        <!-- Success Icon -->
                        <div class="relative mx-auto w-20 h-20 mb-6">
                            <div class="absolute inset-0 bg-green-100 rounded-full"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Success Title -->
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Invoice Verified</h3>
                        <p class="text-gray-600 mb-6">Your invoice has been successfully checked</p>

                        <!-- Summary Content -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                            <div v-for="(item, index) in checkData.value" :key="index" class="mb-3 last:mb-0">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ index }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ item.winners }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button
                                class="flex-1 bg-white border-2 border-orange-500 text-orange-500 font-semibold py-3 px-6 rounded-lg hover:bg-orange-50 transition-colors">
                                Check
                            </button>
                            <button
                                class="flex-1 bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 transition-colors">
                                Claim
                            </button>
                        </div>
                    </div>

                    <div class="text-center" v-if="isChecking === false && errors && Object.keys(errors).length > 0">
                        <div class="bg-red-100 text-red-600 p-3 rounded-md">
                            <ul class="list-disc list-inside">
                                <li v-for="(error, field) in errors" :key="field">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
