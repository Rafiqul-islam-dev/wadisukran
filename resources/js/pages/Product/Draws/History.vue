<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { can } from '@/helpers/permissions';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Draw History',
        href: '/draws/histories',
    },
];

const deleteModal = ref(false);
const deletingHistory = ref(null);

const { wins, products } = defineProps<{
    wins: Array<any>;
    products: Array<any>;
}>();

const form = useForm({
    product_id: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: ''
});

const formatDate = (date) => {
    const parsedDate = new Date(date);
    const formattedDate = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
    }).format(parsedDate);

    return formattedDate.replace(' at', '');
};

const formatTime = (time) => {
    const date = new Date(time);
    return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    }).format(date);
};

const deleteHistory = (id) => {
    deletingHistory.value = id;
    deleteModal.value = true;
}

const confirmDelete = () => {
    router.get(route('draws.histories-delete', deletingHistory.value), {}, {
        onSuccess: () => {
            deletingHistory.value = null;
            deleteModal.value = false;
            toast.success('History deleted successfully.');
        },
        onError: () => {
            toast.error('Something went wrong when deleting History');
        }
    });
}

const handleSearch = () => {
    form.get(route('draws.histories'), {
        preserveScroll: true,
        replace: true,
        showProgress: false,
        preserveState: true
    });
}

const handleDownload = () => {
    toast.error('Download is not available here.');
}

</script>

<template>

    <Head title="Draw History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <!-- Date & Time Selection -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Product
                                </label>
                                <select v-model="form.product_id"
                                    class="w-full border-2 border-gray-200 px-2 py-1.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                    <option value="">Select Product</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.title }}
                                    </option>
                                </select>
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Start Date
                                </label>
                                <Input v-model="form.start_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Start Time
                                </label>
                                <Input v-model="form.start_time" type="time" class="w-full" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    End Date
                                </label>
                                <Input v-model="form.end_date" type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    End Time
                                </label>
                                <Input v-model="form.end_time" type="time" class="w-full" />
                            </div>
                            <div class="relative flex items-center flex-col justify-center">
                                <Button @click="handleSearch" variant="destructive" size="lg"
                                    class="cursor-pointer px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                                    Search
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Draw Table -->
                    <div class="border rounded-lg overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                        SL
                                    </th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-center">
                                        Product
                                    </th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">
                                        Win Number
                                    </th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(win, index) in wins?.data" :key="index">
                                    <td
                                        class="px-3 text-sm md:text-md md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r">
                                        {{ (wins?.current_page - 1) * wins?.per_page + index + 1 }}
                                    </td>
                                    <td
                                        class="px-3 text-sm md:text-md md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r text-center">
                                        {{ win?.product?.title }}
                                    </td>
                                    <td class="px-6 py-4 border-r text-center">
                                        <p class="text-lg">
                                            {{ formatDate(win.draw_time) }}
                                        </p>
                                        <p class="font-bold text-md" v-if="win.from_time && win.to_time">
                                            {{ formatTime(win.from_time) }} - {{ formatTime(win.to_time) }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 border-r">
                                        <div class="flex gap-2 justify-center">
                                            <div v-for="(number, idx) in win.win_number" :key="idx"
                                                class="w-10 h-10 rounded-lg flex flex-col items-center justify-center text-center font-bold border-orange-700 text-black opacity-100 bg-orange-100 border-2">
                                                {{ number }}</div>
                                        </div>

                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 justify-center">
                                            <Button @click="handleDownload" variant="default" size="lg"
                                                class="cursor-pointer px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-xl hover:from-blue-600 hover:to-cyan-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                                </svg>
                                                Download
                                            </Button>

                                            <Button v-if="can('draw history delete')" @Click="deleteHistory(win.id)"
                                                size="sm" class="bg-red-500 hover:bg-red-700 cursor-pointer">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="deleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This History will delete permanently.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteModal = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
