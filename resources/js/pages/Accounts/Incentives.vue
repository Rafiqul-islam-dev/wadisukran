<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { can } from '@/helpers/permissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Incentives',
        href: '/accounts/incentives',
    },
];

const props = defineProps<{
    agents: Array<any>;
    incentives: any;
    agent: any;
    filters: {
        agent?: string | number | null;
        from_date?: string | null;
        to_date?: string | null;
    };
}>();

const showModal = ref(false);
const modalType = ref<'add' | 'edit' | 'view'>('add');
const selectedIncentive = ref<any>(null);

const form = useForm({
    id: '',
    agent: props.filters?.agent || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
    date: '',
    amount: '',
    incentive_type: '',
    description: '',
});

const openModal = (type: 'add' | 'edit' | 'view' = 'add', item: any = null) => {
    modalType.value = type;
    selectedIncentive.value = item;
    form.clearErrors();

    if (type === 'edit' || type === 'view') {
        form.id = item.id;
        form.agent = item.user_id;
        form.date = item.incentive_date ? String(item.incentive_date).split('T')[0] : '';
        form.amount = item.amount ? String(item.amount) : '';
        form.incentive_type = item.incentive_type || '';
        form.description = item.description || '';
    } else {
        form.reset('id', 'date', 'amount', 'incentive_type', 'description');
    }

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        form.reset('id', 'date', 'amount', 'incentive_type', 'description');
        form.clearErrors();
        selectedIncentive.value = null;
        modalType.value = 'add';
    }, 250);
};

const handleSearch = () => {
    form.get(route('accounts.incentives'), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearSearch = () => {
    form.agent = '';
    form.from_date = '';
    form.to_date = '';

    router.get(route('accounts.incentives'), {}, {
        preserveScroll: true,
        replace: true,
    });
};

const submitForm = () => {
    if (modalType.value === 'view') return;

    const url = modalType.value === 'edit'
        ? route('accounts.incentives-update', form.id)
        : route('accounts.incentives-store');

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Incentive ${modalType.value === 'edit' ? 'updated' : 'added'} successfully.`);
            closeModal();
        },
        onError: () => {
            toast.error('Please check the required fields.');
        },
    });
};

const deleteIncentive = (item: any) => {
    if (!confirm('Are you sure you want to delete this incentive?')) return;

    router.delete(route('accounts.incentives-delete', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Incentive deleted successfully.');
        },
        onError: () => {
            toast.error('Unable to delete incentive.');
        },
    });
};

function formatAmount(amount: string | number | null) {
    const value = Number(amount || 0);
    return value.toFixed(2);
}

function formatDate(dateString: string | null) {
    if (!dateString) return 'N/A';

    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function goTo(url: string | null) {
    if (!url) return;

    const page = new URL(url).searchParams.get('page');
    const params = new URLSearchParams(window.location.search);

    if (page) params.set('page', page);

    router.visit(`${window.location.pathname}?${params.toString()}`, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
    });
}
</script>

<template>
    <Head title="Incentives" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:p-4 p-2 md:space-y-6">
            <div class="flex flex-col gap-4 rounded-2xl border bg-white p-4 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Incentives List</h1>
                    </div>

                    <button v-if="can('create incentives')" @click="openModal('add')"
                        class="cursor-pointer px-6 py-2 bg-gradient-to-r whitespace-nowrap from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-1">
                        <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Incentives
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 items-end gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Agent</label>
                        <Multiselect v-model="form.agent" :options="agents" valueProp="id" label="name"
                            placeholder="Select Agent..." :searchable="true" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <Input v-model="form.from_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <Input v-model="form.to_date" type="date" class="w-full" />
                    </div>

                    <div class="flex gap-2">
                        <button @click="handleSearch"
                            class="cursor-pointer px-5 py-2 bg-gradient-to-r from-teal-500 to-green-500 text-white rounded-xl hover:from-teal-600 hover:to-green-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl">
                            Search
                        </button>
                        <button @click="clearSearch"
                            class="cursor-pointer px-5 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-bold">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="border rounded-lg overflow-x-auto bg-white shadow-sm">
                <div class="hidden print:block p-4 text-center">
                    <h2 class="text-xl font-bold">Incentives Report</h2>
                    <p v-if="agent?.name" class="text-sm text-gray-600">Agent: {{ agent.name }}</p>
                    <p class="text-sm text-gray-600">
                        Date: <strong>{{ form.from_date || 'All' }}</strong> to <strong>{{ form.to_date || 'All' }}</strong>
                    </p>
                </div>

                <table class="w-full min-w-[950px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">SL</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">Agent</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">Date</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">Amount</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">Incentives Type</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">Description</th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="!incentives.data.length">
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">No incentives found.</td>
                        </tr>

                        <tr v-for="(incentive, index) in incentives.data" :key="incentive.id"
                            class="hover:bg-orange-50 transition-colors duration-200">
                            <td class="px-6 py-4 text-gray-700">
                                {{ (incentives.current_page - 1) * incentives.per_page + index + 1 }}
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ incentive.user?.name || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ formatDate(incentive.incentive_date) }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ formatAmount(incentive.amount) }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ incentive.incentive_type || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700 max-w-xs truncate">{{ incentive.description || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                <div class="flex items-center gap-2">
                                    <button @click="openModal('view', incentive)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200"
                                        title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button v-if="can('update incentives')" @click="openModal('edit', incentive)"
                                        class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors duration-200"
                                        title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button v-if="can('delete incentives')" @click="deleteIncentive(incentive)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                        title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end py-3 px-6">
                    <nav class="flex items-center space-x-1">
                        <button v-for="(link, i) in incentives.links" :key="i" @click="goTo(link.url)" v-html="link.label"
                            :disabled="!link.url" :class="[
                                'px-3 py-1 rounded border transition-all duration-200',
                                link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]" />
                    </nav>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out opacity-50"></div>

                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all duration-300 ease-out">
                        <div
                            class="flex justify-between items-center p-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ modalType === 'add' ? 'Add Incentives' : (modalType === 'edit' ? 'Edit Incentives' : 'View Incentives Details') }}
                            </h2>
                            <button @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 transition duration-200 p-2 hover:bg-white hover:rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Agent <span v-if="modalType !== 'view'" class="text-red-500">*</span>
                                    </label>
                                    <Multiselect v-model="form.agent" :options="agents" valueProp="id" label="name"
                                        placeholder="Search Agent..." :searchable="true" class="w-full" :disabled="modalType === 'view'" />
                                    <p v-if="form.errors.agent" class="text-red-600 text-sm mt-1">{{ form.errors.agent }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Date <span v-if="modalType !== 'view'" class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.date" type="date" :disabled="modalType === 'view'"
                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 disabled:bg-gray-50" />
                                    <p v-if="form.errors.date" class="text-red-600 text-sm mt-1">{{ form.errors.date }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Amount <span v-if="modalType !== 'view'" class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.amount" type="number" min="1" step="0.01" placeholder="Write amount here"
                                        :disabled="modalType === 'view'"
                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 disabled:bg-gray-50" />
                                    <p v-if="form.errors.amount" class="text-red-600 text-sm mt-1">{{ form.errors.amount }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Incentives Type <span v-if="modalType !== 'view'" class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.incentive_type" type="text" placeholder="Example: Sales Bonus"
                                        :disabled="modalType === 'view'"
                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 disabled:bg-gray-50" />
                                    <p v-if="form.errors.incentive_type" class="text-red-600 text-sm mt-1">{{ form.errors.incentive_type }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                <textarea v-model="form.description" rows="4" placeholder="Write description here"
                                    :disabled="modalType === 'view'"
                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 disabled:bg-gray-50"></textarea>
                                <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
                            </div>

                            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                                <button type="button" @click="closeModal"
                                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                                    {{ modalType === 'view' ? 'Close' : 'Cancel' }}
                                </button>
                                <button v-if="modalType !== 'view'" type="submit" :disabled="form.processing"
                                    class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ modalType === 'add' ? 'Add Incentives' : 'Update Incentives' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
