<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lock Ticket',
        href: '/lock-tickets',
    },
];

const { lockedTickets, heldTickets, products, users, filters } = defineProps<{
    lockedTickets: Array<any>;
    heldTickets: Array<any>;
    products: Array<any>;
    users: Array<any>;
    filters: Record<string, any>;
}>();

const filter = ref({
    date_from: filters?.date_from ?? '',
    time_from: filters?.time_from ?? '',
    date_to: filters?.date_to ?? '',
    time_to: filters?.time_to ?? '',
    product_id: filters?.product_id ?? '',
    user_id: filters?.user_id ?? '',
});

const selectedTicketIds = ref<number[]>([]);
const selectedHeldTicketIds = ref<number[]>([]);
const isHolding = ref(false);
const isReleasing = ref(false);
const isCancelling = ref(false);
const isApplyingCap = ref(false);
const isReleasingCap = ref(false);

const flatTickets = computed(() => {
    return (lockedTickets ?? []).flatMap((item: any) => {
        const groupKey = `${item.product_id}-${item.draw_number ?? 'no-draw'}-${item.agent_id}`;

        return (item.tickets ?? []).map((ticket: any) => ({
            ...ticket,
            group_key: groupKey,
            product_id: item.product_id,
            product_name: item.product_name,
            draw_number: item.draw_number,
            agent_id: item.agent_id,
            agent_name: item.agent_name,
            agent_phone: item.agent_phone,
        }));
    });
});

const allTicketIds = computed(() => flatTickets.value.map((ticket: any) => Number(ticket.id)));
const allHeldTicketIds = computed(() => (heldTickets ?? []).map((ticket: any) => Number(ticket.id)));
const allBreakSuggestionIds = computed(() => {
    return Array.from(new Set((lockedTickets ?? []).flatMap((item: any) => breakTicketIds(item))));
});

const totalOrders = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.order_count ?? 0), 0));
const totalTickets = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.ticket_count ?? 0), 0));
const totalSale = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.total_price ?? 0), 0));
const totalBreakTickets = computed(() => allBreakSuggestionIds.value.length);

function handleSearch() {
    selectedTicketIds.value = [];
    selectedHeldTicketIds.value = [];

    router.get(route('lock-tickets.index'), { ...filter.value }, {
        preserveScroll: true,
        replace: true,
        preserveState: true,
        showProgress: false,
    });
}

function resetFilters() {
    selectedTicketIds.value = [];
    selectedHeldTicketIds.value = [];

    filter.value = {
        date_from: '',
        time_from: '',
        date_to: '',
        time_to: '',
        product_id: '',
        user_id: '',
    };

    router.get(route('lock-tickets.index'));
}

function formatMoney(value: number | string | null) {
    const amount = Number(value ?? 0);
    return amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function ticketIds(item: any): number[] {
    return (item?.tickets ?? []).map((ticket: any) => Number(ticket.id));
}

function breakTicketIds(item: any): number[] {
    return (item?.break_suggestions?.ticket_ids ?? []).map((id: any) => Number(id));
}

function isTicketSelected(ticketId: number) {
    return selectedTicketIds.value.includes(Number(ticketId));
}

function isBreakSuggested(ticketId: number) {
    return allBreakSuggestionIds.value.includes(Number(ticketId));
}

function isAllGroupSelected(item: any) {
    const ids = ticketIds(item);
    return ids.length > 0 && ids.every((id) => selectedTicketIds.value.includes(id));
}

function isAllTicketsSelected() {
    return allTicketIds.value.length > 0 && allTicketIds.value.every((id: number) => selectedTicketIds.value.includes(id));
}

function isAllHeldTicketsSelected() {
    return allHeldTicketIds.value.length > 0 && allHeldTicketIds.value.every((id: number) => selectedHeldTicketIds.value.includes(id));
}

function toggleGroupSelection(item: any, event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    const ids = ticketIds(item);

    if (checked) {
        selectedTicketIds.value = Array.from(new Set([...selectedTicketIds.value, ...ids]));
    } else {
        selectedTicketIds.value = selectedTicketIds.value.filter((id) => !ids.includes(id));
    }
}

function toggleAllTickets(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedTicketIds.value = checked ? Array.from(new Set(allTicketIds.value)) : [];
}

function toggleAllHeldTickets(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedHeldTicketIds.value = checked ? Array.from(new Set(allHeldTicketIds.value)) : [];
}

function selectedCountInGroup(item: any) {
    const ids = ticketIds(item);
    return ids.filter((id) => selectedTicketIds.value.includes(id)).length;
}

function chanceLocks(item: any) {
    return (item?.locks ?? []).filter((lock: any) => String(lock.type ?? '').startsWith('Chance'));
}

function otherLocks(item: any) {
    return (item?.locks ?? []).filter((lock: any) => !String(lock.type ?? '').startsWith('Chance'));
}

function selectBreakSuggestions(item: any) {
    const ids = breakTicketIds(item);

    if (!ids.length) {
        toast.error('No break suggestion found for this group.');
        return;
    }

    selectedTicketIds.value = Array.from(new Set([...selectedTicketIds.value, ...ids]));
    toast.success(`${ids.length} minimum break ticket(s) selected.`);
}

function selectAllBreakSuggestions() {
    if (!allBreakSuggestionIds.value.length) {
        toast.error('No break suggestion found.');
        return;
    }

    selectedTicketIds.value = Array.from(new Set([...selectedTicketIds.value, ...allBreakSuggestionIds.value]));
    toast.success(`${allBreakSuggestionIds.value.length} minimum break ticket(s) selected.`);
}

function holdSelectedTickets() {
    if (!selectedTicketIds.value.length) {
        toast.error('Please select ticket first.');
        return;
    }

    const message = `Hold ${selectedTicketIds.value.length} selected ticket(s)?\n\nHeld tickets will NOT count in Probable Wins, Draw winner calculation, or Claim. Other agents/customers with the same number will remain valid.`;

    if (!window.confirm(message)) {
        return;
    }

    router.post(route('lock-tickets.hold'), {
        ticket_ids: selectedTicketIds.value,
        reason: 'Risk hold / Break Lock from Lock Ticket page',
    }, {
        preserveScroll: true,
        onBefore: () => {
            isHolding.value = true;
        },
        onSuccess: () => {
            selectedTicketIds.value = [];
            toast.success('Selected ticket(s) held successfully.');
        },
        onError: () => {
            toast.error('Unable to hold selected ticket.');
        },
        onFinish: () => {
            isHolding.value = false;
        },
    });
}

function releaseSelectedHeldTickets() {
    if (!selectedHeldTicketIds.value.length) {
        toast.error('Please select held ticket first.');
        return;
    }

    if (!window.confirm(`Release ${selectedHeldTicketIds.value.length} held ticket(s)? They will become valid again.`)) {
        return;
    }

    router.post(route('lock-tickets.release'), {
        ticket_ids: selectedHeldTicketIds.value,
    }, {
        preserveScroll: true,
        onBefore: () => {
            isReleasing.value = true;
        },
        onSuccess: () => {
            selectedHeldTicketIds.value = [];
            toast.success('Held ticket(s) released successfully.');
        },
        onError: () => {
            toast.error('Unable to release held ticket.');
        },
        onFinish: () => {
            isReleasing.value = false;
        },
    });
}

function cancelSelectedTickets() {
    if (!selectedTicketIds.value.length) {
        toast.error('Please select ticket first.');
        return;
    }

    const message = `Are you sure you want to cancel ${selectedTicketIds.value.length} selected ticket(s)?\n\nImportant: because your project cancellation is invoice/order based, the parent invoice/order will be cancelled.`;

    if (!window.confirm(message)) {
        return;
    }

    router.post(route('lock-tickets.cancel'), {
        ticket_ids: selectedTicketIds.value,
    }, {
        preserveScroll: true,
        onBefore: () => {
            isCancelling.value = true;
        },
        onSuccess: () => {
            selectedTicketIds.value = [];
            toast.success('Selected invoice/order cancelled successfully.');
        },
        onError: () => {
            toast.error('Unable to cancel selected ticket.');
        },
        onFinish: () => {
            isCancelling.value = false;
        },
    });
}

function applyRiskCap(item: any) {
    const defaultPercent = item?.risk_cap?.cap_percent ?? 40;
    const input = window.prompt('Risk Cap Percent দিন (30, 40, 50 or custom). Prize amount change হবে না; fixed prize winners will be allowed only up to this cap budget.', String(defaultPercent));

    if (input === null) {
        return;
    }

    const percent = Number(input);

    if (!percent || percent <= 0 || percent > 100) {
        toast.error('Please enter a valid percentage between 1 and 100.');
        return;
    }

    const message = `Apply ${percent}% Risk Cap for ${item.agent_name} / ${item.product_name}?\n\nFixed prize amount will NOT change. Suspicious group winners will be limited within cap budget. Normal agents/customers remain valid.`;

    if (!window.confirm(message)) {
        return;
    }

    router.post(route('lock-tickets.apply-risk-cap'), {
        product_id: item.product_id,
        draw_number: item.draw_number,
        user_id: item.agent_id,
        cap_percent: percent,
        reason: 'Risk payout cap from Lock Ticket page',
    }, {
        preserveScroll: true,
        onBefore: () => {
            isApplyingCap.value = true;
        },
        onSuccess: () => {
            toast.success('Risk cap applied successfully.');
        },
        onError: () => {
            toast.error('Unable to apply risk cap.');
        },
        onFinish: () => {
            isApplyingCap.value = false;
        },
    });
}

function releaseRiskCap(item: any) {
    if (!window.confirm(`Release Risk Cap for ${item.agent_name} / ${item.product_name}? This group will calculate normally again.`)) {
        return;
    }

    router.post(route('lock-tickets.release-risk-cap'), {
        product_id: item.product_id,
        draw_number: item.draw_number,
        user_id: item.agent_id,
    }, {
        preserveScroll: true,
        onBefore: () => {
            isReleasingCap.value = true;
        },
        onSuccess: () => {
            toast.success('Risk cap released successfully.');
        },
        onError: () => {
            toast.error('Unable to release risk cap.');
        },
        onFinish: () => {
            isReleasingCap.value = false;
        },
    });
}

</script>

<template>
    <Head title="Lock Ticket" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Lock Ticket</h1>
                        
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        <button
                            v-if="allBreakSuggestionIds.length"
                            @click="selectAllBreakSuggestions"
                            class="rounded-lg bg-amber-100 px-4 py-2 text-amber-800 text-sm font-semibold hover:bg-amber-200"
                        >
                            Break Lock Suggest ({{ totalBreakTickets }})
                        </button>
                        <button
                            v-if="selectedTicketIds.length"
                            @click="holdSelectedTickets"
                            :disabled="isHolding"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-60"
                        >
                            {{ isHolding ? 'Holding...' : `Hold Selected (${selectedTicketIds.length})` }}
                        </button>
                        <button
                            v-if="selectedTicketIds.length"
                            @click="cancelSelectedTickets"
                            :disabled="isCancelling"
                            class="rounded-lg bg-red-600 px-4 py-2 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
                        >
                            {{ isCancelling ? 'Cancelling...' : 'Cancel Invoice' }}
                        </button>
                        <div class="rounded-lg bg-red-50 px-4 py-2 text-red-700 text-sm font-semibold">
                            Locked Found: {{ lockedTickets?.length ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input v-model="filter.date_from" type="date" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Time</label>
                        <input v-model="filter.time_from" type="time" step="1" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input v-model="filter.date_to" type="date" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Time</label>
                        <input v-model="filter.time_to" type="time" step="1" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <select v-model="filter.product_id" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Products</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.title }} {{ product.product_number ?? '' }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                        <select v-model="filter.user_id" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">All Agents</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-5">
                    <button @click="handleSearch" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                        Search
                    </button>
                    <button @click="resetFilters" class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold">
                        Reset
                    </button>
                </div>
            </div>

            <div v-if="lockedTickets?.length" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-4">
                        <div class="text-sm text-gray-500">Locked Groups</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ lockedTickets.length }}</div>
                    </div>
                    <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-4">
                        <div class="text-sm text-gray-500">Total Orders</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ totalOrders }}</div>
                    </div>
                    <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-4">
                        <div class="text-sm text-gray-500">Total Tickets</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ totalTickets }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-100 shadow-sm p-4">
                        <div class="text-sm text-amber-700">Minimum Break Hold</div>
                        <div class="text-2xl font-bold text-amber-800 mt-1">{{ totalBreakTickets }}</div>
                    </div>
                    <div class="rounded-xl bg-red-50 border border-red-100 shadow-sm p-4">
                        <div class="text-sm text-red-700">Total Sale</div>
                        <div class="text-2xl font-bold text-red-800 mt-1">{{ formatMoney(totalSale) }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Lock Summary</h2>
                        </div>
                    </div>

                    <div class="overflow-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">Select</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product / Draw</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Orders</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tickets</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Chance Coverage</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Straight / Rumble</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Break Suggest</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Risk Cap</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in lockedTickets" :key="`${item.product_id}-${item.draw_number}-${item.agent_id}`" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="isAllGroupSelected(item)"
                                            @change="toggleGroupSelection(item, $event)"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">{{ item.product_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">{{ item.agent_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.order_count }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.ticket_count }}</td>
                                    <td class="px-4 py-3 min-w-[220px]">
                                        <div v-if="chanceLocks(item).length" class="space-y-1">
                                            <div v-for="lock in chanceLocks(item)" :key="lock.type" class="text-xs">
                                                <span class="font-semibold text-red-700">{{ lock.type }}:</span>
                                                {{ lock.covered_tickets }}/{{ lock.required_tickets }} ({{ lock.coverage_percent }}%)
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </td>
                                    <td class="px-4 py-3 min-w-[220px]">
                                        <div v-if="otherLocks(item).length" class="space-y-1">
                                            <div v-for="lock in otherLocks(item)" :key="lock.type" class="text-xs">
                                                <span class="font-semibold text-red-700">{{ lock.type }}:</span>
                                                {{ lock.covered_tickets }}/{{ lock.required_tickets }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </td>
                                    <td class="px-4 py-3 min-w-[260px]">
                                        <div class="text-sm font-semibold text-amber-700">
                                            {{ item.break_suggestions?.ticket_count ?? 0 }} ticket(s)
                                        </div>
                                        <div class="mt-1 space-y-1">
                                            <div v-for="detail in item.break_suggestions?.details ?? []" :key="`${detail.lock_type}-${detail.coverage_key}`" class="text-xs text-gray-600">
                                                {{ detail.lock_type }} → {{ detail.coverage_key }}: {{ detail.ticket_count }} ticket(s)
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 min-w-[260px]">
                                        <div v-if="item.risk_cap" class="rounded-lg bg-green-50 border border-green-100 p-2 text-xs text-green-800">
                                            <div class="font-bold">Capped: {{ item.risk_cap.cap_percent }}%</div>
                                            <div>Total Sale: {{ formatMoney(item.risk_cap.total_sale) }}</div>
                                            <div>Commission: {{ item.risk_cap.commission_percent }}% = {{ formatMoney(item.risk_cap.commission_amount) }}</div>
                                            <div>Net Sale: {{ formatMoney(item.risk_cap.net_sale) }}</div>
                                            <div>Max Payable: {{ formatMoney(item.risk_cap.max_payable_amount) }}</div>
                                        </div>
                                        <div v-else class="text-xs text-gray-500">
                                            Not capped yet
                                            <div>Total Sale: {{ formatMoney(item.total_price) }}</div>
                                            <div>Commission: {{ item.commission_percent ?? 0 }}%</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col gap-2">
                                            <button
                                                @click="applyRiskCap(item)"
                                                :disabled="isApplyingCap"
                                                class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                            >
                                                {{ item.risk_cap ? 'Change Cap' : 'Apply Risk Cap' }}
                                            </button>
                                            <button
                                                v-if="item.risk_cap"
                                                @click="releaseRiskCap(item)"
                                                :disabled="isReleasingCap"
                                                class="rounded-lg bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-800 hover:bg-green-200 disabled:opacity-60"
                                            >
                                                Release Cap
                                            </button>
                                            <button
                                                @click="selectBreakSuggestions(item)"
                                                class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-200"
                                            >
                                                Break Lock
                                            </button>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">Selected: {{ selectedCountInGroup(item) }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">All Locked Ticket List</h2>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                            <span class="text-sm text-gray-500">
                                Selected: {{ selectedTicketIds.length }} / {{ flatTickets.length }}
                            </span>
                            <button
                                v-if="selectedTicketIds.length"
                                @click="holdSelectedTickets"
                                :disabled="isHolding"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-60"
                            >
                                {{ isHolding ? 'Holding...' : 'Hold Selected' }}
                            </button>
                            <button
                                v-if="selectedTicketIds.length"
                                @click="cancelSelectedTickets"
                                :disabled="isCancelling"
                                class="rounded-lg bg-red-600 px-4 py-2 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
                            >
                                {{ isCancelling ? 'Cancelling...' : 'Cancel Invoice' }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-auto max-h-[650px]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="sticky top-0 bg-white z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">
                                        <input
                                            type="checkbox"
                                            :checked="isAllTicketsSelected()"
                                            @change="toggleAllTickets($event)"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Invoice No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Play Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Order Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="ticket in flatTickets"
                                    :key="ticket.id"
                                    :class="[
                                        isTicketSelected(ticket.id) ? 'bg-red-50' : 'hover:bg-gray-50',
                                        isBreakSuggested(ticket.id) ? 'bg-amber-50' : ''
                                    ]"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="selectedTicketIds"
                                            type="checkbox"
                                            :value="Number(ticket.id)"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">
                                        <div>{{ ticket.product_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 whitespace-nowrap">
                                        <div class="font-semibold">{{ ticket.agent_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ ticket.invoice_no }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-sm font-bold text-orange-700">
                                                {{ ticket.ticket_number }}
                                            </span>
                                            <span v-if="isBreakSuggested(ticket.id)" class="rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-bold text-amber-900">
                                                Break
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ ticket.play_type_text || '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ formatMoney(ticket.order_total) }}</td>
                                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ ticket.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                            {{ ticket.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="text-4xl mb-3">✅</div>
                <h3 class="text-lg font-semibold text-gray-900">No lock ticket found</h3>
            </div>

            <div v-if="heldTickets?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Held Risk Tickets</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">Selected: {{ selectedHeldTicketIds.length }} / {{ heldTickets.length }}</span>
                        <button
                            v-if="selectedHeldTicketIds.length"
                            @click="releaseSelectedHeldTickets"
                            :disabled="isReleasing"
                            class="rounded-lg bg-green-600 px-4 py-2 text-white text-sm font-semibold hover:bg-green-700 disabled:opacity-60"
                        >
                            {{ isReleasing ? 'Releasing...' : 'Release Selected' }}
                        </button>
                    </div>
                </div>

                <div class="overflow-auto max-h-[450px]">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky top-0 bg-white z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">
                                    <input
                                        type="checkbox"
                                        :checked="isAllHeldTicketsSelected()"
                                        @change="toggleAllHeldTickets($event)"
                                        class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Held At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Held By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="ticket in heldTickets" :key="ticket.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="selectedHeldTicketIds"
                                        type="checkbox"
                                        :value="Number(ticket.id)"
                                        class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">
                                    <div>{{ ticket.product_name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ ticket.agent_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ ticket.invoice_no }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-gray-700">
                                        {{ ticket.ticket_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 min-w-[220px]">{{ ticket.risk_reason ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ ticket.risk_hold_at ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ ticket.risk_hold_by ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
