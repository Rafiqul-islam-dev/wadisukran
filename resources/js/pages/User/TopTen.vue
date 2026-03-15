<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Top Ten Agents',
        href: '/agents/top-ten',
    },
];

const { company_setting } = usePage().props;

const props = defineProps<{
    agents: Array<any>;
    filters: {
        from_date: string | null;
        to_date: string | null;
    };
}>();

const filter = reactive({
    from_date: props.filters?.from_date ?? '',
    to_date: props.filters?.to_date ?? '',
});

const submitFilter = () => {
    router.get('/agents/top-ten', filter, {
        preserveState: true,
        replace: true,
    });
};

const resetFilter = () => {
    filter.from_date = '';
    filter.to_date = '';

    router.get('/agents/top-ten', {}, {
        preserveState: true,
        replace: true,
    });
};

const formatDateTime = (date: string, isStart: boolean = true) => {
    if (!date) return '';

    const d = new Date(date);
    const day = d.getDate().toString().padStart(2, '0');
    const month = d.toLocaleString('en-US', { month: 'short' });
    const year = d.getFullYear();

    return isStart
        ? `${day} ${month} ${year} 12:00 AM`
        : `${day} ${month} ${year} 11:59 PM`;
};

const dateRangeText = computed(() => {
    if (filter.from_date && filter.to_date) {
        return `Top Agent: ${formatDateTime(filter.from_date, true)} TO ${formatDateTime(filter.to_date, false)}`;
    }

    if (filter.from_date) {
        return `Top Agent: ${formatDateTime(filter.from_date, true)} TO ${formatDateTime(filter.from_date, false)}`;
    }

    if (filter.to_date) {
        return `Top Agent: ${formatDateTime(filter.to_date, true)} TO ${formatDateTime(filter.to_date, false)}`;
    }

    return 'Top Agent';
});
</script>

<template>
    <Head title="Top Ten Agents" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

            <!-- Filter Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            v-model="filter.from_date"
                            type="date"
                            class="w-full md:w-48 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-200 focus:border-orange-400 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            v-model="filter.to_date"
                            type="date"
                            class="w-full md:w-48 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-200 focus:border-orange-400 outline-none"
                        />
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="submitFilter"
                            type="button"
                            class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-medium transition"
                        >
                            Search
                        </button>

                        <button
                            @click="resetFilter"
                            type="button"
                            class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-medium transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Date Range Title -->
            <div class="mb-4">
                <h2 class="text-[18px] font-bold text-gray-800 text-center">
                    {{ dateRangeText }}
                </h2>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-orange-50 border-b-2 border-orange-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Orders</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="(agent, index) in agents"
                                :key="agent.id"
                                class="hover:bg-orange-50 transition-colors duration-200"
                            >
                                <td class="px-6 py-4">
                                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-bold">
                                        {{ index + 1 }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="relative flex items-center gap-3">
                                        <img
                                            v-if="agent.photo"
                                            :src="agent.photo"
                                            alt="User Photo"
                                            class="w-12 h-12 object-cover rounded-full border-4 border-indigo-100 shadow-md"
                                        />

                                        <div
                                            v-else
                                            class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center shadow-md"
                                        >
                                            <span class="text-white font-bold text-lg">
                                                {{ agent.name?.charAt(0) }}
                                            </span>
                                        </div>

                                        <div>
                                            <p class="truncate text-sm font-semibold text-gray-900">
                                                {{ agent.name }}
                                            </p>
                                            <p class="truncate text-sm text-gray-500">
                                                {{ agent.address }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ agent.orders_count }}
                                    </p>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700">
                                        {{ agent.total_sale }} {{ company_setting?.currency }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-center py-16" v-if="agents?.length === 0">
                        <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No agents found</h3>
                        <p class="text-gray-400 text-sm">Try adjusting your date filter.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>