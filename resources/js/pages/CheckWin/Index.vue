<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage, Inertia } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, Ref, ref } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Check Wins',
        href: '/check-wins',
    },
];

const { wins, products } = defineProps<{
    wins: Array<any>;
    products: Array<any>;
}>();

const invoice_no = ref('');
const errors = ref([]);

const form = useForm({
    invoice_no: '',
    product_id: ''
});

const showModal = ref(false);
const isChecking = ref(false);
const isClaiming = ref(false);
const isClaimed = ref(false);
const checkData = ref([]);
const checkMsg = ref('');
const summaryEntries = computed(() => Object.entries(checkData.value));

const totalWonAmount = computed(() => {
    return summaryEntries.value.reduce((sum, [key, item]) => {
        return sum + Number((item?.number_of_ticket * item?.prize_per_winner) || 0);
    }, 0);
});


const checkmsg = computed(() => checkMsg.value)
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

        if (response.data?.summery) {
            checkData.value = response.data?.summery?.summery;  // Assuming the response contains a `summery` key
        }
        checkMsg.value = response.data?.message;
        // Handle other actions after success
        isChecking.value = false;
    } catch (error) {
        // Handle error
        console.error('Error fetching data:', error);
        isChecking.value = false;
        errors.value = error.response?.data?.errors || [];
        console.log(errors.value)
        toast.error('Failed to check invoice. Please try again.');
    }
};

const handleIndividualClaim = (invo) => {
    invoice_no.value = invo;
    handleCheck()
}

const handleSearch = () => {
    form.get(route('check-wins'), {
        showProgress: false,
        preserveState: true
    })
}

const handleClose = () => {
    showModal.value = false;
    checkData.value = [];
    errors.value = [];
    checkMsg.value = '';
    isClaimed.value = false;
}

const claimWin = async () => {
    if (!invoice_no.value) {
        toast.error('Invoice number is empty.');
        return false;
    }
    isClaiming.value = true;
    try {
        const response = await axios.post(route('claim-win'), {
            invoice_no: invoice_no.value,  // Send invoice_no as part of the body
        });

        if (response.data) {
            isClaiming.value = true;
            isClaimed.value = true;
            toast.success(response.data.message);
        }
    } catch (error) {
        // Handle error
        console.error('Error fetching data:', error);
        toast.error('Failed to check invoice. Please try again.');
    }
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
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 p-3 mb-2 items-center">
                    <div>
                        <select name="" id="" v-model="form.product_id"
                            class="w-full border-2 border-gray-200 px-3 py-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Select Product</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">{{ product.title
                            }}</option>
                        </select>
                    </div>
                    <div>
                        <input v-model="form.invoice_no" type="text" placeholder="Invoice No.."
                            class="w-full border-2 border-gray-200 px-3 py-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
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
                            <tr v-for="win in wins?.data" :key="win.id"
                                class="hover:bg-orange-50 transition-colors duration-200">
                                <td class="px-4 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{
                                    win.invoice_no }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    <span v-for="ticket in win?.check_win?.tickets"
                                        class="px-2 py-1 mb-1 bg-blue-100 text-blue-800 block text-center rounded-lg text-xs font-semibold">
                                        <span v-for="item in ticket" class="p-1">{{ item }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <p class="font-medium text-gray-900">{{ win?.user?.name }}</p>
                                    <p class="text-teal-600 text-xs mt-1" v-if="win.user?.agent?.username">{{
                                        win?.user?.agent?.username }}</p>

                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <p class="  mt-1 truncate max-w-[180px]" v-if="win.user?.address">{{
                                        win.user.address }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    {{ win?.check_win?.total_prize }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap font-medium">{{
                                    win?.product?.title }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <button v-if="win.is_claimed === 0" @click="handleIndividualClaim(win.invoice_no)"
                                        class="inline-flex items-center cursor-pointer px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7">
                                            </path>
                                        </svg>
                                        Claim
                                    </button>
                                    <button v-else disabled
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg cursor-not-allowed">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                            </path>
                                        </svg>
                                        Claimed
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-end py-5 px-6">
                        <nav class="flex items-center space-x-1">
                            <button v-for="(link, i) in wins?.links" :key="i" @click="goTo(link.url)"
                                v-html="link.label" :disabled="!link.url" :class="[
                                    'px-3 py-1 rounded border transition-all duration-200',
                                    link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]" />
                        </nav>
                    </div>
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
                    <div class="text-center" v-if="isClaimed">
                        <div class="relative mx-auto w-16 h-16 mb-3">
                            <div class="absolute inset-0 bg-orange-100 rounded-full"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Success Title -->
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Invoice Claimed</h3>
                        <p class="text-gray-600 mb-6">Your winnings have been successfully claimed!</p>
                    </div>
                    <!-- Summary State -->
                    <div class="text-center"
                        v-if="!isChecking && !isClaimed && errors && Object.keys(errors).length == 0">
                        <!-- Success Icon -->
                        <div class="relative mx-auto w-16 h-16 mb-3">
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
                        <p class="text-gray-600 mb-6">{{ checkmsg }}</p>

                        <!-- Summary Content -->
                        <div class="bg-gray-50 rounded-lg mb-6 text-left overflow-x-auto whitespace-nowrap"
                            v-if="summaryEntries && summaryEntries.length > 0">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600 border-b border-gray-200">
                                        <th class="py-2 px-3 font-semibold">Match Type</th>
                                        <th class="py-2 px-3 font-semibold">Prize</th>
                                        <th class="py-2 px-3 font-semibold">Total Amount</th>
                                        <th class="py-2 px-3 font-semibold">Ticket No</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="([key, item], idx) in summaryEntries" :key="key"
                                        class="text-gray-800 text-center">
                                        <td class="py-2 px-3 font-semibold">
                                            {{ item.match_type }}
                                        </td>

                                        <td class="py-2 px-3">
                                            {{ item.prize_per_winner }}
                                        </td>

                                        <td class="py-2 px-3 font-bold">
                                            {{ item.number_of_ticket * item.prize_per_winner }}
                                        </td>

                                        <td class="py-2 px-3">
                                            <div v-for="ticket in item.tickets" :key="ticket" class="mb-1">{{ ticket }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="bg-gray-100 rounded-lg p-4 mt-4 text-center">
                                <span class="text-gray-600 font-semibold">Total Won Amount: </span>
                                <span class="text-green-600 font-bold text-lg">{{ totalWonAmount?.toFixed(2) }}</span>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex gap-3" v-if="summaryEntries && summaryEntries.length > 0">
                            <button @click="handleClose"
                                class="flex-1 cursor-pointer bg-white border-2 border-orange-500 text-orange-500 font-semibold py-3 px-6 rounded-lg hover:bg-orange-50 transition-colors">
                                Close
                            </button>
                            <button @click="claimWin" :disabled="isClaiming"
                                class="flex-1 cursor-pointer bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="!isClaiming">Claim</span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Claiming...
                                </span>
                            </button>
                        </div>
                    </div>

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
