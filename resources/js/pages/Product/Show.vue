<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
});

function editProduct() {
    router.get(`/products/${props.product.id}/edit`);
}

function deleteProduct() {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/products/${props.product.id}`);
    }
}

function formatPrizes(prizes) {
    return Object.entries(prizes).map(([key, value]) => ({ key, value }));
}
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ product.title }}</h1>
                    <p class="text-gray-600">Product Details</p>
                </div>
               
            </div>

            <!-- Product Details Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                    <!-- Left Column - Image and Basic Info -->
                    <div class="space-y-6">
                        <!-- Product Image -->
                        <div class="relative">
                            <img v-if="product.image_url" :src="product.image_url" :alt="product.title"
                                class="w-full h-64 object-cover rounded-xl border-2 border-gray-200" />
                            <div v-else
                                class="w-full h-64 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-xl border-2 border-gray-200 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Price:</span>
                                    <span class="font-semibold text-green-600">{{ product.price }} AED</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Type:</span>
                                    <span class="font-medium text-gray-900 capitalize">{{ product.type }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span v-if="product.is_active"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Active
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactive
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="font-medium text-gray-900">{{ new
                                        Date(product.created_at).toLocaleDateString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Game Details -->
                    <div class="space-y-6">
                        <!-- Draw Information -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Draw Information</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Draw Date:</span>
                                    <span class="font-semibold text-gray-900">{{ new
                                        Date(product.draw_date).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Draw Time:</span>
                                    <span class="font-semibold text-gray-900">{{ product.draw_time.substring(0, 8)
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Game Configuration -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Game Configuration</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pick Number:</span>
                                    <span class="font-semibold text-gray-900">{{ product.pick_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Type Number:</span>
                                    <span class="font-semibold text-gray-900">{{ product.type_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Showing Type:</span>
                                    <span class="font-semibold text-gray-900 capitalize">{{ product.showing_type
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Prizes -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Prizes</h3>
                            <div class="space-y-3">
                                <div v-for="prize in formatPrizes(product.prizes)" :key="prize.key"
                                    class="flex justify-between items-center bg-white p-3 rounded-lg border">
                                    <span class="font-medium text-gray-700">{{ prize.key }}:</span>
                                    <span class="font-semibold text-green-600">{{ prize.value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="mt-8 flex justify-center">
                <button @click="$inertia.visit('/products')"
                    class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Products
                </button>
            </div>
        </div>
    </AppLayout>
</template>