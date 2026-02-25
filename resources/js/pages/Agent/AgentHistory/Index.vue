<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Agent Histories',
        href: '/agents/history',
    },
];

const { users } = defineProps<{
    users: Array<any>;
}>();
</script>
<template>

    <Head title="Agent Histories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:p-6 p-2 md:space-y-6">
            <div class="border rounded-lg overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                SL
                            </th>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                Address
                            </th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">
                                Total Sales
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(user, index) in users.data" :key="user.id"
                            class="hover:bg-orange-50 transition-colors duration-200">
                            <td class="px-6">
                                {{ (users.current_page - 1) *
                                    users.per_page + index + 1 }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="relative">
                                        <img v-if="user.avatar" :src="user.avatar" alt="User Photo"
                                            class="w-12 h-12 object-cover rounded-full border-4 border-indigo-100 shadow-md" />
                                        <div v-else
                                            class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center shadow-md">
                                            <span class="text-white font-bold text-lg">{{ user.name.charAt(0)
                                                }}</span>
                                        </div>
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white"
                                            :class="user.is_active ? 'bg-green-500' : 'bg-gray-400'"></div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-semibold text-gray-900">{{ user.name }}</div>
                                        <div class="text-gray-500 text-sm">Username: {{ user.agent?.username }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ user.address || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700 text-center flex gap-5">{{ user.sales_sum_total_price || "0.00" }}
                                <button><Eye size="20" class="cursor-pointer" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
