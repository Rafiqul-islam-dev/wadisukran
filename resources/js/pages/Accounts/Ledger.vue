<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { can } from '@/helpers/permissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Multiselect from '@vueform/multiselect'
import { toast } from 'vue-sonner';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts Ledger',
        href: '/accounts/ledger',
    },
];

const { agents, ledgers } = defineProps<{
    agents: Array<any>;
    ledgers: Array<any>;
}>();
const showModal = ref(false);
const form = useForm({
    amount: '',
    agent: '',
    description: '',
    payment_type: 1,
    from_date: '',
    to_date: '',
    date: '',
});

const handleSearch = () => {
    form.get(route('accounts.ledger'), {
        preserveScroll: true,
        replace: true,
        preserveState: true
    });
}

const openModal = () => {
    showModal.value = true;
}

const closeModal = () => {
    form.agent = '';
    form.amount = '';
    showModal.value = false;
}

function formatDate(dateString: string | null) {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
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

const submitForm = () => {
    form.post(route('accounts.ledger-store'),{
        onSuccess: () => {
            toast.success('Payment added successfully');
            closeModal();
        },
        onError: (error) => {
            toast.error(error.error || 'An error occurred while adding payment');
        }
    });
}
</script>
<template>

    <Head title="Accounts Ledger" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:p-4 p-2 md:space-y-6">
            <div class="flex flex-col md:flex-row gap-5 justify-between items-center mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 items-center gap-4 mb-2 w-full p-3">
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Agent <span class="text-red-500">*</span>
                        </label>
                        <Multiselect
                            v-model="form.agent"
                            :options="agents"
                            valueProp="id"
                            label="name"
                            placeholder="Agent..."
                            :searchable="true"
                            class="w-full"
                        />

                        <p v-if="form.errors.agent" class="text-red-600 text-sm">
                            {{ form.errors.agent }}
                        </p>
                    </div>
                    <div class="group">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            From Date
                        </label>
                        <Input v-model="form.from_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                    </div>
                    <div class="group">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            To Date
                        </label>
                        <Input v-model="form.to_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                    </div>
                    <div class="flex items-center flex-col">
                        <button @click="handleSearch"
                            class="cursor-pointer px-6 py-2 bg-gradient-to-r from-teal-500 to-green-500 text-white rounded-xl hover:from-teal-600 hover:to-green-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                            Search
                        </button>
                    </div>
                </div>
                <div class="flex items-center flex-col">
                    <button v-if="can('create payment')" @click="openModal()"
                        class="cursor-pointer px-6 py-2 bg-gradient-to-r whitespace-nowrap from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-1">
                         <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Payment
                    </button>
                </div>
            </div>
            <div class="border rounded-lg overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">
                                Vendor
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               Amount
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               Payment Type
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               Description
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(ledger, index) in ledgers.data" :key="ledger.id"
                            class="hover:bg-orange-50 transition-colors duration-200">
                            <td class="px-6 py-4 text-gray-700">{{ ledger.user?.name || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ ledger.amount || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ ledger.payment_type === 1 ? 'Agent Payment Received' : 'Agent Payment Received From Company' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ ledger.description || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ ledger.created_at ? formatDate(ledger.created_at) : "N/A" }}</td>
                        </tr>
                    </tbody>
                </table>
                 <div class="flex justify-end py-2 px-6">
                    <nav class="flex items-center space-x-1">
                        <button v-for="(link, i) in ledgers.links" :key="i" @click="goTo(link.url)"
                            v-html="link.label" :disabled="!link.url" :class="[
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
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out"
                    :class="showModal ? 'opacity-50' : 'opacity-0'"></div>

                <!-- Modal Container -->
                <div class="flex items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all duration-300 ease-out"
                        :class="showModal ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                        <!-- Modal Header -->
                        <div
                            class="flex justify-between items-center p-3 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Add New Payment
                                </h2>
                            </div>
                            <button @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 transition duration-200 p-2 hover:bg-white hover:rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="submitForm" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                               <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Agent <span class="text-red-500">*</span>
                                    </label>
                                    <Multiselect
                                        v-model="form.agent"
                                        :options="agents"
                                        valueProp="id"
                                        label="name"
                                        placeholder="Search Agent..."
                                        :searchable="true"
                                        class="w-full"
                                    />

                                    <p v-if="form.errors.agent" class="text-red-600 text-sm">
                                        {{ form.errors.agent }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date
                                        <span class="text-red-500">*</span></label>
                                    <input v-model="form.date" type="date" placeholder="Write date here"
                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                         />
                                    <p v-if="form.errors.date" class="text-red-600 text-sm">
                                        {{ form.errors.date }}
                                    </p>
                                </div>
                                 <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Amount
                                        <span class="text-red-500">*</span></label>
                                    <input v-model="form.amount" type="text" placeholder="Write amount here"
                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                         />
                                    <p v-if="form.errors.amount" class="text-red-600 text-sm">
                                        {{ form.errors.amount }}
                                    </p>
                                </div>
                                 <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Type <span class="text-red-500">*</span></label>
                                    <select name="" v-model="form.payment_type" id="" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300">
                                        <option value="">Select Payment Type</option>
                                        <option value="1">Agent Payment Received</option>
                                        <option value="2">Agent Payment Received From Company</option>
                                    </select>
                                    <p v-if="form.errors.payment_type" class="text-red-600 text-sm">
                                        {{ form.errors.payment_type }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-3 mt-2">
                                 <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                        <textarea placeholder="Write description here" name="" v-model="form.description" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"></textarea>
                                    <p v-if="form.errors.description" class="text-red-600 text-sm">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                                <button type="button" @click="closeModal"
                                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Add Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
