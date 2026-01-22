<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { can } from '@/helpers/permissions';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Draw History',
        href: '/draws/histories',
    },
];

const { wins } = defineProps<{
    wins: Array<any>;
}>();

const formatDate = (date) => {
    const parsedDate = new Date(date);
    const formattedDate = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
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
    if(confirm('Are you sure to delete this History?')){
        router.get(route('draws.histories-delete', id), {}, {
            onSuccess: () => {
                toast.success('History deleted successfully.');
            },
            onError: () => {
                toast.error('Something went wrong when deleting History');
            }
        });
    }
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
                                <select
                                    class="w-full border-2 border-gray-200 px-2 py-1.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                    <option value="">Select Product</option>
                                </select>
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Start Date
                                </label>
                                <Input type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Start Time
                                </label>
                                <Input type="time" class="w-full" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    End Date
                                </label>
                                <Input type="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    End Time
                                </label>
                                <Input type="time" class="w-full" />
                            </div>
                            <div class="relative flex items-center flex-col justify-center">
                                <Button variant="destructive" size="lg"
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
                                            <!-- <Button variant="outline" size="sm"
                                                class="cursor-pointer px-8 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl hover:from-orange-600 hover:to-amber-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2 hover:text-white">
                                                Edit
                                            </Button> -->

                                            <Button v-if="can('draw history delete')" @Click="deleteHistory(win.id)" size="sm" class="bg-red-500 hover:bg-red-700 cursor-pointer">
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
    </AppLayout>
</template>
