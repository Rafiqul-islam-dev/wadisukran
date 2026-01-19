<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Trashed Products',
        href: '/trashed-products',
    },
];

const { products } = defineProps<{
    products: Array<any>;
}>();


function restoreProduct(id) {
    if (confirm('Are you sure you want to restore this product?')) {
        router.get(route('products.restore', id), {}, {
            onSuccess: () => {
                toast.success('Product restored successfully.');
            }
        });
    }
}
function deleteProduct(id) {
    if (confirm('Are you sure you want to permanent delete this product?')) {
        router.get(route('products.permanent-delete', id),{}, {
            onSuccess: () => {
                toast.success('Product permanently deleted successfully.');
            }
        });
    }
}


function formatDrawTime(time) {
    if (!time) return '';
    // Assuming time is in HH:mm format (e.g., "23:59")
    const [hours, minutes] = time.split(':').map(Number);
    const date = new Date();
    date.setHours(hours, minutes);

    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    }); // Returns e.g., "11:59 PM"
}


</script>

<template>
    <Head title="Trashed Products" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <!-- <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-xl lg:text-4xl font-bold text-gray-900 mb-2">Trashed Products</h1>
                </div>
            </div> -->

            <!-- Table View -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <!-- Table Header -->
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Image
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Title
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Price
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Draw
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="product in products.data" :key="product.id"
                                class="hover:bg-gray-50 transition-colors duration-200">
                                <!-- Product Image -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div
                                            class="h-16 w-24 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100 flex-shrink-0">
                                            <img v-if="product.image_url" :src="product.image_url" :alt="product.title"
                                                class="w-full h-full object-cover" />
                                            <div v-else class="flex items-center justify-center h-full">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Title -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ product.title }}</div>
                                    <div class="text-sm text-gray-500">{{ product.showing_type }} | Pick {{
                                        product.pick_number }}</div>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-green-600">{{ product.price }} AED</div>
                                </td>

                                <!-- Draw Date and Time -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="flex items-center" v-if="product.draw_type">
                                        Draw Type : <span class="text-teal-500 font-bold"> {{ product.draw_type
                                            }}</span>
                                    </div>
                                    <div class="flex items-center" v-if="product.draw_date">
                                        Date :
                                        <span class="text-teal-500">{{ new
                                            Date(product.draw_date).toLocaleDateString('en-US', {
                                                day: '2-digit', month:
                                                    'short', year: '2-digit'
                                            }) }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500" v-if="product.draw_time">Time: <span
                                            class="text-teal-500">{{
                                                formatDrawTime(product.draw_time) }}</span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <!-- View Button -->
                                        <button @click="restoreProduct(product.id)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 hover:text-green-700 transition-all duration-200">
                                            <RotateCcw class="w-4 h-4 mr-1" />
                                            Restore
                                        </button>
                                        <!-- Delete Button -->
                                        <button @click="deleteProduct(product.id)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                           Permanent Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-end py-5">
                        <nav class="flex items-center space-x-1">
                            <button v-for="(link, i) in products.links" :key="i"
                                @click="link.url && router.visit(link.url)" v-html="link.label" :disabled="!link.url"
                                :class="[
                                    'px-3 py-1 rounded border',
                                    link.active
                                        ? 'bg-gray-800 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]" />
                        </nav>
                    </div>
                    <!-- Empty State -->
                    <div v-if="!products || products.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
