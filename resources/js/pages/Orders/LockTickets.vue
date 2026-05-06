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

const { lockedTickets, products, users, filters } = defineProps<{
    lockedTickets: Array<any>;
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
const isCancelling = ref(false);

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

const totalOrders = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.order_count ?? 0), 0));
const totalTickets = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.ticket_count ?? 0), 0));
const totalSale = computed(() => (lockedTickets ?? []).reduce((sum: number, item: any) => sum + Number(item.total_price ?? 0), 0));

function handleSearch() {
    selectedTicketIds.value = [];

    router.get(route('lock-tickets.index'), { ...filter.value }, {
        preserveScroll: true,
        replace: true,
        preserveState: true,
        showProgress: false,
    });
}

function resetFilters() {
    selectedTicketIds.value = [];

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

function isTicketSelected(ticketId: number) {
    return selectedTicketIds.value.includes(Number(ticketId));
}

function isAllGroupSelected(item: any) {
    const ids = ticketIds(item);
    return ids.length > 0 && ids.every((id) => selectedTicketIds.value.includes(id));
}

function isAllTicketsSelected() {
    return allTicketIds.value.length > 0 && allTicketIds.value.every((id: number) => selectedTicketIds.value.includes(id));
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

function cancelSelectedTickets() {
    if (!selectedTicketIds.value.length) {
        toast.error('Please select ticket first.');
        return;
    }

    const message = `Are you sure you want to cancel ${selectedTicketIds.value.length} selected lock ticket(s)?\n\nNote: ticket cancel means its parent invoice/order will be cancelled.`;

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
            toast.success('Selected lock ticket cancelled successfully.');
        },
        onError: () => {
            toast.error('Unable to cancel selected ticket.');
        },
        onFinish: () => {
            isCancelling.value = false;
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
                            v-if="selectedTicketIds.length"
                            @click="cancelSelectedTickets"
                            :disabled="isCancelling"
                            class="rounded-lg bg-red-600 px-4 py-2 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
                        >
                            {{ isCancelling ? 'Cancelling...' : `Cancel Selected (${selectedTicketIds.length})` }}
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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <div class="rounded-xl bg-red-50 border border-red-100 shadow-sm p-4">
                        <div class="text-sm text-red-600">Total Sale</div>
                        <div class="text-2xl font-bold text-red-700 mt-1">{{ formatMoney(totalSale) }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-red-50 px-5 py-4 border-b border-red-100">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Lock Summary</h2>
                           
                        </div>
                        <button
                            v-if="selectedTicketIds.length"
                            @click="cancelSelectedTickets"
                            :disabled="isCancelling"
                            class="rounded-lg bg-red-600 px-4 py-2 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
                        >
                            {{ isCancelling ? 'Cancelling...' : `Cancel Selected (${selectedTicketIds.length})` }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
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
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Draw No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Orders</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tickets</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Sale</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Chance Coverage</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Straight / Rumble Coverage</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in lockedTickets" :key="`${item.product_id}-${item.draw_number}-${item.agent_id}`" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            type="checkbox"
                                            :checked="isAllGroupSelected(item)"
                                            @change="toggleGroupSelection(item, $event)"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3 align-top font-semibold text-gray-900 whitespace-nowrap">{{ item.product_name }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700 whitespace-nowrap">{{ item.draw_number ?? '-' }}</td>
                                    <td class="px-4 py-3 align-top text-gray-900 whitespace-nowrap">
                                        <div class="font-semibold">{{ item.agent_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700 font-semibold">{{ item.order_count }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700 font-semibold">{{ item.ticket_count }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700 whitespace-nowrap">{{ formatMoney(item.total_price) }}</td>
                                    <td class="px-4 py-3 align-top min-w-[230px]">
                                        <div v-if="chanceLocks(item).length" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="lock in chanceLocks(item)"
                                                :key="lock.type"
                                                class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
                                            >
                                                {{ lock.type }}: {{ lock.covered_tickets }}/{{ lock.required_tickets }} ({{ lock.coverage_percent }}%)
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 align-top min-w-[180px]">
                                        <div v-if="otherLocks(item).length" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="lock in otherLocks(item)"
                                                :key="lock.type"
                                                class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700"
                                            >
                                                {{ lock.type }}: {{ lock.covered_tickets }}/{{ lock.required_tickets }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700 whitespace-nowrap">
                                        {{ item.first_ticket_at }} - {{ item.last_ticket_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="sticky top-0 z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-gray-50 px-5 py-4 border-b border-gray-200">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">All Locked Ticket List</h2>
                            <p class="text-sm text-gray-500 mt-0.5">
                                All locked tickets are listed together with product and agent name. Select tickets to cancel.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">
                                Selected: {{ selectedTicketIds.length }} / {{ flatTickets.length }}
                            </span>
                            <button
                                v-if="selectedTicketIds.length"
                                @click="cancelSelectedTickets"
                                :disabled="isCancelling"
                                class="rounded-lg bg-red-600 px-4 py-2 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
                            >
                                {{ isCancelling ? 'Cancelling...' : 'Cancel Selected' }}
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
                                <tr v-for="ticket in flatTickets" :key="ticket.id" :class="isTicketSelected(ticket.id) ? 'bg-red-50' : 'hover:bg-gray-50'">
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
                                        <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-sm font-bold text-orange-700">
                                            {{ ticket.ticket_number }}
                                        </span>
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
                <p class="text-gray-500 mt-1">Only suspicious full-coverage tickets will appear here. Straight, Rumble and Chance coverage are checked separately.</p>
            </div>
        </div>
    </AppLayout>
</template>
