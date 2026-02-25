<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts Summery',
        href: '/accounts/summery',
    },
];

const { company_setting } = usePage().props;
const formattedNow = dayjs().format('DD MMM, YY hh:mm A');
const { users } = defineProps<{
    users: Array<any>;
}>();

const form = useForm({
    from: '',
    to: ''
});

const showModal = ref(false);
const selectedUser = ref<any>(null);

const openModal = (user: any) => {
    selectedUser.value = user;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const handleSearch = () => {
    form.get(route('accounts.summery'), {
        preserveScroll: true,
        replace: true,
        preserveState: true
    });
}
</script>
<template>

    <Head title="Agent Histories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:px-4">
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
                                    Old Balance
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
                                        + {{ user.total_commission }}
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
                                        + {{ user.total_claim }}
                                    </span>
                                </td>

                                <!-- Old Balance -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="Number(user.old_balance) < 0
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-gray-100 text-gray-600'"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full font-medium">
                                        {{ user.old_balance }}
                                    </span>
                                </td>

                                <!-- Net Amount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Commission</span>
                                            <span class="text-green-600 font-medium">+ {{ user.total_commission
                                                }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Claim</span>
                                            <span class="text-green-600 font-medium">+ {{ user.total_claim }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Old Balance</span>
                                            <span v-if="Number(user.old_balance) < 0"
                                                class="text-green-600 font-medium">+ {{ user.old_balance }}</span>
                                            <span v-else class="text-red-500 font-medium">- {{ user.old_balance
                                                }}</span>
                                        </div>
                                        <div
                                            class="border-t border-dashed border-gray-200 pt-1 flex items-center justify-between gap-4">
                                            <span class="text-gray-600 font-semibold">Net</span>
                                            <span class="text-orange-600 font-bold">
                                                {{ (Number(user.total_commission) + Number(user.total_claim)) -
                                                Number(user.old_balance) }}
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
                                    (Number(selectedUser?.total_commission) +
                                        Number(selectedUser?.total_claim)) -
                                    Number(selectedUser?.old_balance)
                                }}
                            </td>
                        </tr>

                        <tr>
                            <td class="border px-3 py-2">Old Balance Pending</td>
                            <td class="border px-3 py-2 text-right">
                                {{ selectedUser?.old_balance }}
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
                                    (Number(selectedUser?.total_commission) +
                                        Number(selectedUser?.total_claim)) -
                                    Number(selectedUser?.old_balance)
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
                <div class="hidden-print m-auto w-full text-center mt-3">
                    <button v-print="'#printDiv'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-center m-auto hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        <span>Print</span>
                    </button>
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
}
</style>
