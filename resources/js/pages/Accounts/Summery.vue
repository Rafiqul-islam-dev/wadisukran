<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Download, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import dayjs from 'dayjs';
import { can } from '@/helpers/permissions';
import Multiselect from '@vueform/multiselect';
import { toast } from 'vue-sonner';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts Summery',
        href: '/accounts/summery',
    },
];

const { company_setting } = usePage().props;
const formattedNow = dayjs().format('DD MMM, YY hh:mm A');
const { users, from_date } = defineProps<{
    users: Array<any>;
    agents: Array<any>;
    from_date: string;
}>();

const form = useForm({
    from: from_date,
    to: '',
    agent_id: '',
});

const showModal = ref(false);
const generateModal = ref(false);
const selectedUser = ref<any>(null);

const openModal = (user: any) => {
    selectedUser.value = user;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};
const closeGenerateModal = () => {
    generateModal.value = false;
};

const openGenerateModal = () => {
    generateModal.value = true;
};

const handleSearch = () => {
    form.get(route('accounts.summery'), {
        preserveScroll: true,
        replace: true,
        preserveState: true
    });
}

const generateBill = async () => {
    try {
        const response = await axios.post(
            route('accounts.generate-bill'),
            {
                from: form.from,
                to: form.to
            },
            {
                responseType: 'blob'
            }
        );

        const blob = new Blob([response.data], { type: 'application/zip' });
        const url = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = 'agent-bills.zip'; // ZIP file
        link.click();

        window.URL.revokeObjectURL(url);

        toast.success('Bills downloaded successfully!');

    } catch (error) {
        toast.error('Failed to generate bill');
        console.error(error);
    }
};
</script>
<template>

    <Head title="Agent Histories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:px-4">
            <div class="flex flex-col md:flex-row gap-5 justify-between items-center p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="group">
                        <label for="agent">Select Agent</label>
                        <div class="mt-2"></div>
                        <Multiselect v-model="form.agent_id" :options="agents" valueProp="id" label="name"
                            placeholder="Agent..." :searchable="true"
                            class="w-full border-2 border-gray-200 px-2 py-1 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200" />
                    </div>
                    <div class="group">
                        <label for="from" class="mb-1">From Date</label>
                        <input v-model="form.from" type="date" id="from" disabled="true"
                            class="disabled:bg-gray-200 cursor-not-allowed mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                            placeholder="Enter agent name/email/username">
                    </div>
                    <div class="group">
                        <label for="to" class="mb-1">To Date</label>
                        <input v-model="form.to" type="date" id="to"
                            class="mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                            placeholder="Enter agent name/email/username">
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
                <button v-if="can('generate bill')" @click="openGenerateModal()"
                    class="cursor-pointer bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm md:text-md px-3 py-3 rounded-xl shadow-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 font-semibold flex items-center gap-2">
                    <Download :size="16" />
                    Generate Bill
                </button>
            </div>
            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <!-- Head -->
                        <thead>
                            <tr class="bg-gradient-to-r from-orange-500 to-amber-400 text-white">
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Vendor
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Total Sale
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Commission
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Winning Amount
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Claim Prize
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Old Due
                                </th>
                                <th
                                    class="px-6 py-4 text-left font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Net Amount
                                </th>
                                <th
                                    class="px-6 py-4 text-center font-semibold text-xs uppercase tracking-wider whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <!-- Body -->
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(user, index) in users" :key="user.id"
                                class="hover:bg-orange-50 transition-colors duration-200 group">
                                <!-- Vendor -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold text-gray-800">{{ user.name }}</span>
                                    </div>
                                </td>

                                <!-- Total Sale -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-800">{{ user.total_sell }}</span>
                                </td>

                                <!-- Commission -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">
                                        {{ user.total_commission }}
                                    </span>
                                </td>

                                <!-- Winning Amount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full font-medium bg-blue-100 text-blue-700">
                                        {{ user.total_win }}
                                    </span>
                                </td>

                                <!-- Claim Prize -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full font-medium bg-purple-100 text-purple-700">
                                        {{ user.total_claim }}
                                    </span>
                                </td>

                                <!-- Old Balance -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="Number(user.old_due) < 0
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-gray-100 text-gray-600'"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full font-medium">
                                        {{ user.old_due }}
                                    </span>
                                </td>

                                <!-- Net Amount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Commission</span>
                                            <span class="text-green-600 font-medium"> {{ user.total_commission
                                            }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Claim</span>
                                            <span class="text-green-600 font-medium"> {{ user.total_claim }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Old Due</span>
                                            <span class="text-green-600 font-medium"> {{ user.old_due }}</span>
                                        </div>
                                        <div
                                            class="border-t border-dashed border-gray-200 pt-1 flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Net</span>
                                            <span class="text-orange-600 font-bold">
                                                {{ (Number(user.total_commission) + Number(user.total_claim)) +
                                                    Number(user.old_due) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="px-6 py-4 text-center">
                                    <button @click="openModal(user)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-100 hover:text-orange-700 transition-colors duration-200 group-hover:shadow-sm">
                                        <Eye :size="16" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

                <!-- Empty state (optional) -->
                <div v-if="!users || users.length === 0" class="text-center py-16 text-gray-400">
                    <p class="text-sm">No vendor data available.</p>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-500/40 bg-opacity-50 flex items-center justify-center z-50">
            <div id="printDiv"
                class="bg-white w-[900px] max-h-[97vh] overflow-y-auto rounded-lg shadow-lg p-6 relative">

                <!-- Close Button -->
                <button @click="closeModal"
                    class="absolute top-3 right-3 text-gray-600 hover:text-red-500 hidden-print">
                    ✕
                </button>

                <!-- Company Info -->
                <div class="mb-4 text-sm flex gap-5">
                    <div class="image-fit ">
                        <img :src="company_setting?.logo_url" class="h-26 w-26 object-contain" alt="">
                    </div>
                    <div>
                        <p><strong>{{ company_setting?.name }}</strong></p>
                        <p><strong>Address:</strong> {{ company_setting?.address }}</p>
                        <p><strong>Email:</strong> {{ company_setting?.email }}</p>
                        <p><strong>TRN:</strong> {{ company_setting?.trn_no }}</p>
                        <p><strong>Agent:</strong> {{ selectedUser?.name }}</p>
                    </div>
                </div>

                <!-- Header -->
                <div class="text-center mb-4">
                    <h2 class="text-xl font-bold">Account Statement</h2>
                    <p class="text-sm text-gray-600">
                        Statement Date: <strong>{{ formattedNow }}</strong>
                    </p>
                </div>
                <div class="mb-4 text-sm">
                    <p><strong>Vendor Name:</strong> {{ selectedUser?.name }}</p>
                    <p><strong>Vendor Address:</strong> {{ selectedUser?.address }}</p>
                </div>

                <!-- Statement Table -->
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left">Description</th>
                            <th class="border px-3 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-3 py-2">Total Sale</td>
                            <td class="border px-3 py-2 text-right">
                                {{ selectedUser?.total_sell }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Commission</td>
                            <td class="border px-3 py-2 text-right">
                                {{ selectedUser?.total_commission }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Ammount paid for
                                raffle redeem directly by agent</td>
                            <td class="border px-3 py-2 text-right">
                                {{ selectedUser?.total_claim }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Agent payment received
                            </td>
                            <td class="border px-3 py-2 text-right">
                                0
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2"><strong class="font-bold">Add:</strong> Prize reimbursement
                                paid to agent </td>
                            <td class="border px-3 py-2 text-right">
                                0
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Other incentives </td>
                            <td class="border px-3 py-2 text-right">
                                0
                            </td>
                        </tr>
                        <!-- Net Calculation -->
                        <tr class="bg-gray-100 font-bold">
                            <td class="border px-3 py-2">Net Amount</td>
                            <td class="border px-3 py-2 text-right">
                                {{
                                    Number(selectedUser?.total_sell) - (Number(selectedUser?.total_commission) +
                                        Number(selectedUser?.total_claim))

                                }}
                            </td>
                        </tr>

                        <tr>
                            <td class="border px-3 py-2">Old Balance Pending</td>
                            <td class="border px-3 py-2 text-right">
                                {{ selectedUser?.old_due }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border px-3 py-2">Other charges (If Any)</td>
                            <td class="border px-3 py-2 text-right">
                                0
                            </td>
                        </tr>

                        <!-- Net Calculation -->
                        <tr class="bg-gray-100 font-bold">
                            <td class="border px-3 py-2">Total Due As per Statement</td>
                            <td class="border px-3 py-2 text-right">
                                {{
                                    (Number(selectedUser?.total_sell) + Number(selectedUser?.old_due)) -
                                    (Number(selectedUser?.total_commission) +
                                        Number(selectedUser?.total_claim))
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer Section -->
                <div class="mt-6 text-sm">
                    <h3 class="font-semibold mb-2">To be filled by Representative</h3>

                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2">Date of Collection</th>
                                <th class="border px-3 py-2">Cash Received</th>
                                <th class="border px-3 py-2">No Of Tickets Redeemed</th>
                                <th class="border px-3 py-2">Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border px-3 py-4"></td>
                                <td class="border px-3 py-4"></td>
                                <td class="border px-3 py-4"></td>
                                <td class="border px-3 py-4"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- <div class="hidden-print m-auto w-full text-center mt-3">
                    <button v-print="'#printDiv'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-center m-auto hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        <span>Print</span>
                    </button>
                </div> -->
            </div>
        </div>

        <Teleport to="body">
            <div v-if="generateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="closeGenerateModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-5 rounded-t-2xl text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold">Generate Bill</h2>
                            </div>
                            <button @click="closeGenerateModal"
                                class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-700 mb-1">Disclaimer</p>
                            <span class=" py-1 rounded-full text-sm font-semibold text-red-500">
                                This will generate bill for all agents based on the current statement. Please make sure to generate bill.
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                            <div class="group">
                                <label for="from" class="mb-1">From Date</label>
                                <input v-model="form.from" type="date" id="from" disabled="true"
                                    class="disabled:bg-gray-200 cursor-not-allowed mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                                    placeholder="Enter agent name/email/username">
                            </div>
                            <div class="group">
                                <label for="to" class="mb-1">To Date</label>
                                <input v-model="form.to" type="date" id="to"
                                    class="mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                                    placeholder="Enter agent name/email/username">
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pb-6 flex justify-end gap-3">
                        <button @click="closeGenerateModal"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                            Cancel
                        </button>
                        <button @click="generateBill"
                            class="px-4 py-2 bg-gradient-to-r cursor-pointer from-orange-500 to-amber-600 text-white rounded-xl hover:from-orange-600 hover:to-amber-700 transition-all duration-200 font-bold shadow-md hover:shadow-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Generate
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
@media print {
    .hidden-print {
        display: none !important;
    }
}
</style>
