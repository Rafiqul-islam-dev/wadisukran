<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import dayjs from 'dayjs';
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
const formatNow = dayjs().format('YYYY-MM-DD');
const { agent, agents } = defineProps<{
    agent: Array<any>;
    agents: Array<any>;
    to_date: string;
    from_date: string;
}>();

console.log(agent);

const form = useForm({
    from: '',
    to: '',
    agent_id: '',
});

const showModal = ref(false);
const selectedUser = ref<any>(null);
const errors = ref<Record<string, string>>({});

const openModal = (user: any) => {
    selectedUser.value = user;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};


const handleSearch = () => {
    const messages: Record<string, string> = {};
    let valid = true;
    if (!form.from) {
        messages.from = 'Please select from date.';
        valid = false;
    }

    if (!form.to) {
        messages.to = 'Please select to date.';
        valid = false;
    }

    if (!form.agent_id) {
        messages.agent_id = 'Please select an agent.';
        valid = false;
    }

    if (!valid) {
        toast.error('Please fill in all required fields.');
        errors.value = messages;
        return;
    }

    form.get(route('accounts.summery'), {
        preserveScroll: true,
        replace: true,
        preserveState: true,
        only: ['agent']
    });
}

</script>
<template>

    <Head title="Accounts Summery" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:px-4">
            <div class="flex flex-col md:flex-row gap-5 justify-between items-center p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="group">
                        <label for="agent">Select Agent</label>
                        <div class="mt-2"></div>
                        <Multiselect v-model="form.agent_id" :options="agents" valueProp="id" label="name"
                            placeholder="Agent..." :searchable="true" :class="errors.agent_id ? 'border-red-400' : ''"
                            class="w-full border-2 border-gray-200 px-2 py-1 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200" />
                            <p class="text-red-500 text-sm mt-1" v-if="errors.agent_id">{{ errors.agent_id }}</p>
                    </div>
                    <div class="group">
                        <label for="from" class="mb-1">From Date</label>
                        <input v-model="form.from" type="date" id="from" :class="errors.from ? 'border-red-400' : ''"
                            class="disabled:bg-gray-200 cursor-not-allowed mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                            placeholder="Enter agent name/email/username">
                        <p class="text-red-500 text-sm mt-1" v-if="errors.from">{{ errors.from }}</p>
                    </div>
                    <div class="group">
                        <label for="to" class="mb-1">To Date</label>
                        <input v-model="form.to" type="date" id="to" :class="errors.to ? 'border-red-400' : ''"
                            class="mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                            placeholder="Enter agent name/email/username">
                        <p class="text-red-500 text-sm mt-1" v-if="errors.to">{{ errors.to }}</p>
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
            <div  class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-5 m-auto">
                <div v-if="agent" class="overflow-x-auto text-center p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-bold">Account Statement</h2>
                        <p class="text-sm text-gray-600">
                            Statement Date: <strong>{{ from_date }}</strong> to <strong > {{ to_date }} </strong>
                        </p>
                    </div>
                    <div class="mb-4">
                        <p><strong>Vendor Name:</strong> {{ agent.agent_name }}</p>
                        <p><strong>Vendor Address:</strong> {{ agent.agent_address }}</p>
                    </div>

                    <!-- Statement Table -->
                    <table class="w-full border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">Description</th>
                                <th class="border px-3 py-2 text-left">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border px-3 py-2">Total Sale</td>
                                <td class="border px-3 py-2 text-left">
                                    {{ Number(agent.total_sell) - Number(agent.total_cancel) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2">Total Cancel</td>
                                <td class="border px-3 py-2 text-left">
                                    - {{ agent.total_cancel }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Commission</td>
                                <td class="border px-3 py-2 text-left">
                                    {{ agent.total_commission }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Ammount paid for
                                    raffle redeem directly by agent</td>
                                <td class="border px-3 py-2 text-left">
                                    {{ agent.total_claim }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Agent payment received
                                </td>
                                <td class="border px-3 py-2 text-left">
                                    {{ agent.total_posting }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2"><strong class="font-bold">Add:</strong> Prize reimbursement
                                    paid to agent </td>
                                <td class="border px-3 py-2 text-left">
                                    0
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2"><strong class="font-bold">Less:</strong> Other incentives </td>
                                <td class="border px-3 py-2 text-left">
                                    0
                                </td>
                            </tr>
                            <!-- Net Calculation -->
                            <tr class="bg-gray-100 font-bold">
                                <td class="border px-3 py-2">Net Amount</td>
                                <td class="border px-3 py-2 text-left">
                                  {{ agent.net_amount }}
                                </td>
                            </tr>

                            <tr>
                                <td class="border px-3 py-2">Old Balance Pending</td>
                                <td class="border px-3 py-2 text-left">
                                    {{ agent.old_balance }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-3 py-2">Other charges (If Any)</td>
                                <td class="border px-3 py-2 text-left">
                                    0
                                </td>
                            </tr>

                            <!-- Net Calculation -->
                            <tr class="bg-gray-100 font-bold">
                                <td class="border px-3 py-2">Total Due As per Statement</td>
                                <td class="border px-3 py-2 text-left">
                                    {{ agent.total_due }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                        Statement Date: <strong>{{ from_date }}</strong> to <strong v-if="!form.to">{{ formatNow }}</strong> <strong v-else>{{ form.to }}</strong>
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

    </AppLayout>
</template>

<style scoped>
@media print {
    .hidden-print {
        display: none !important;
    }
}
</style>
