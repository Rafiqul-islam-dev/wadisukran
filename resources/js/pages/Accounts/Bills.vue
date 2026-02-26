<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts Bills',
        href: '/accounts/bills',
    },
];

const { bills } = defineProps<{
    bills: Array<any>;
}>();

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

function formatDateTime(dateString: string | null) {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

function formatDate(dateString: string | null) {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function getFileName(url) {
    return url.split('/').pop(); // gets the last part of the URL
  }
</script>

<template>
    <Head title="Accounts Bills" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="md:p-4 p-2 md:space-y-6">
            <div class="border rounded-lg overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-sm font-semibold text-gray-700 border-r text-left">
                                Created By
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               From
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               To
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                               Zip
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                Created At
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(bill, index) in bills.data" :key="bill.id"
                            class="hover:bg-orange-50 transition-colors duration-200">
                            <td class="px-6 py-4 text-gray-700">{{ bill.creator?.name || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ formatDate(bill.from_date) }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ formatDate(bill.to_date) }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                <a :href="bill.zip_link">
                                    <Download />
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ bill.created_at ? formatDateTime(bill.created_at) : "N/A" }}</td>
                        </tr>
                    </tbody>
                </table>
                 <div class="flex justify-end py-2 px-6">
                    <nav class="flex items-center space-x-1">
                        <button v-for="(link, i) in bills?.links" :key="i" @click="goTo(link.url)"
                            v-html="link.label" :disabled="!link.url" :class="[
                                'px-3 py-1 rounded border transition-all duration-200',
                                link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]" />
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
