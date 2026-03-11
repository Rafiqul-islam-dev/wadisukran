<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Multiselect from '@vueform/multiselect';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Agent Histories',
        href: '/agents/history',
    },
];

const { company_setting } = usePage().props;

const props = defineProps<{
    agent_histories: Array<any>;
    agents: Array<any>;
    to_date: string | null;
    from_date: string | null;
    selected_agent_id: string | number | null;
}>();

const form = useForm({
    from: props.from_date || '',
    to: props.to_date || '',
    agent_id: props.selected_agent_id || '',
});

const errors = ref<Record<string, string>>({});

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

    if (!valid) {
        toast.error('Please fill in required fields.');
        errors.value = messages;
        return;
    }

    errors.value = {};

    form.get(route('agents.history'), {
        preserveScroll: true,
        replace: true,
        preserveState: true,
        only: ['agent_histories', 'from_date', 'to_date', 'selected_agent_id'],
    });
};

const openPdf = () => {
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

    if (!valid) {
        toast.error('Please select from date and to date.');
        errors.value = messages;
        return;
    }

    errors.value = {};

    const url = route('agents.history.pdf', {
        from: form.from,
        to: form.to,
        agent_id: form.agent_id || '',
    });

    window.open(url, '_blank');
};

function truncateTwo(num: number | string | null | undefined) {
    const value = Number(num || 0);
    return (Math.floor(value * 100) / 100).toFixed(2);
}

const totals = computed(() => {
    const rows = props.agent_histories || [];

    return rows.reduce(
        (acc, item) => {
            acc.total_sell += Number(item.total_sell || 0);
            acc.total_commission += Number(item.total_commission || 0);
            acc.total_win += Number(item.total_win || 0);
            acc.total_claim += Number(item.total_claim || 0);
            acc.total_posting += Number(item.total_posting || 0);
            acc.total_cancel += Number(item.total_cancel || 0);
            acc.old_balance += Number(item.old_balance || 0);
            acc.net_amount += Number(item.net_amount || 0);
            acc.total_due += Number(item.total_due || 0);
            return acc;
        },
        {
            total_sell: 0,
            total_commission: 0,
            total_win: 0,
            total_claim: 0,
            total_posting: 0,
            total_cancel: 0,
            old_balance: 0,
            net_amount: 0,
            total_due: 0,
        }
    );
});
</script>

<template>
    <Head title="Agent Histories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:px-4">
            <!-- Filter Section -->
            <div class="flex flex-col md:flex-row gap-5 justify-between items-center p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                    <div class="group">
                        <label for="agent">Select Agent</label>
                        <div class="mt-2"></div>
                        <Multiselect
                            v-model="form.agent_id"
                            :options="agents"
                            valueProp="id"
                            label="name"
                            placeholder="All Agents"
                            :searchable="true"
                            class="w-full border-2 border-gray-200 px-2 py-1 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                        />
                    </div>

                    <div class="group">
                        <label for="from" class="mb-1">From Date <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.from"
                            type="date"
                            id="from"
                            :class="errors.from ? 'border-red-400' : ''"
                            class="mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                        />
                        <p class="text-red-500 text-sm mt-1" v-if="errors.from">{{ errors.from }}</p>
                    </div>

                    <div class="group">
                        <label for="to" class="mb-1">To Date <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.to"
                            type="date"
                            id="to"
                            :class="errors.to ? 'border-red-400' : ''"
                            class="mt-2 w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200"
                        />
                        <p class="text-red-500 text-sm mt-1" v-if="errors.to">{{ errors.to }}</p>
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            @click="handleSearch"
                            class="px-4 cursor-pointer py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2 text-center w-full"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                            Search
                        </button>

                        <button
                            type="button"
                            @click="openPdf"
                            class="px-4 cursor-pointer py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2 text-center w-full"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Header -->
            <div
                v-if="agent_histories.length"
                class="px-4 pb-2  md:items-center md:justify-between gap-3"
            >
                <div class="text-center">
                    <h2 class="text-xl font-bold text-gray-800">Agent History Report</h2>
                    <p class="text-sm text-gray-500">
                        From <span class="font-medium">{{ form.from }}</span>
                        to <span class="font-medium">{{ form.to }}</span>
                    </p>
                </div>
            </div>

            <!-- Table -->
            <div v-if="agent_histories.length" class="p-4">
                <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm bg-white">
                    <table class="w-full text-sm text-left min-w-[1400px]">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-3 border-b">SL</th>
                                <th class="px-4 py-3 border-b">Agent</th>
                                <th class="px-4 py-3 border-b text-right">Total Sale</th>
                                <th class="px-4 py-3 border-b text-right">Commission</th>
                                <th class="px-4 py-3 border-b text-right">Winning Amount</th>
                                <th class="px-4 py-3 border-b text-right">Claim Prize</th>
                                <th class="px-4 py-3 border-b text-right">Posting</th>
                                <th class="px-4 py-3 border-b text-right">Cancel</th>
                                <th class="px-4 py-3 border-b text-right">Old Balance</th>
                                <th class="px-4 py-3 border-b text-right">Net Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(item, index) in agent_histories"
                                :key="item.agent_id ?? index"
                                class="border-t border-gray-200 hover:bg-orange-50/40 transition"
                            >
                                <td class="px-4 py-3">{{ index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <div>{{ item.agent_name }}</div>
                                    <div v-if="item.agent_address" class="text-xs text-gray-500 mt-1">
                                        {{ item.agent_address }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_sell) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_commission) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_win) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_claim) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_posting) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.total_cancel) }}</td>
                                <td class="px-4 py-3 text-right">{{ truncateTwo(item.old_balance) }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ truncateTwo(item.net_amount) }}</td>
                                
                            </tr>
                        </tbody>

                        <tfoot class="bg-orange-50 font-bold text-gray-800">
                            <tr>
                                <td colspan="2" class="px-4 py-3 border-t">Grand Total</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_sell) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_commission) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_win) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_claim) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_posting) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.total_cancel) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.old_balance) }}</td>
                                <td class="px-4 py-3 border-t text-right">{{ truncateTwo(totals.net_amount) }}</td>
                               
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="form.from && form.to"
                class="mx-4 mb-4 rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500"
            >
                No data found for selected date range.
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