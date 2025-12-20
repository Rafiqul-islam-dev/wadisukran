<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const { products } = defineProps<{
    products: Array<any>;
}>();

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const imagePreview = ref(null);
const form = ref({
    id: null,
    title: '',
    price: '',
    draw_date: '',
    draw_time: '',
    draw_type: 'once',
    regular_type: '',
    image: null,
    pick_number: '',
    prize_type: 'bet',
    type_number: '',
    prizes: {},
    is_active: true,
    is_daily: true
});

async function openModal(product) {
    if (product) {
        isEditing.value = true;
        form.value = {
            id: product.id,
            title: product.title,
            price: product.price,
            draw_date: product.draw_date, // Already in YYYY-MM-DD
            draw_time: product.draw_time.substring(0, 5), // Ensure HH:mm format
            image: null,
            pick_number: product.pick_number,
            prize_type: product.showing_type,
            type_number: product.type_number,
            prizes: product.prizes,
            is_active: product.is_active,
            is_daily: product.is_daily
        };
        imagePreview.value = product.image_url || null;
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            title: '',
            price: '',
            draw_date: '',
            draw_time: '',
            image: null,
            pick_number: '',
            draw_type: 'once',
            prize_type: 'bet',
            type_number: '',
            prizes: {},
            is_active: true,
            is_daily: true
        };
        imagePreview.value = null;
    }
    showModal.value = true;
    await nextTick();
    modalVisible.value = true;
}

async function closeModal() {
    modalVisible.value = false;
    await new Promise(resolve => setTimeout(resolve, 300));
    showModal.value = false;
    imagePreview.value = null;
}

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.value.image = null;
    imagePreview.value = null;
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

function addPrize() {
    const key = document.getElementById('prize-key').value;
    const value = document.getElementById('prize-value').value;

    if (key && value) {
        form.value.prizes[key] = value;
        document.getElementById('prize-key').value = '';
        document.getElementById('prize-value').value = '';
    }
}

function removePrize(key) {
    delete form.value.prizes[key];
}

function submitForm() {
    const formData = new FormData();

    Object.keys(form.value).forEach(key => {
        if (key === 'prizes') {
            formData.append(key, JSON.stringify(form.value[key]));
        } else if (key === 'image' && form.value[key]) {
            formData.append(key, form.value[key]);
        } else if (key !== 'image' && key !== 'id') {
            formData.append(key, form.value[key]);
        }
    });

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(`/products/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/products', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/products/${id}`);
    }
}


function getEffectiveDrawDate(product) {
    if (!product.is_daily) {
        return product.draw_date; // Already in YYYY-MM-DD format
    }
    const today = new Date();
    return today.toISOString().split('T')[0]; // Returns YYYY-MM-DD
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
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Product Management</h1>
                    <p class="text-gray-600">Manage daily games and lottery products</p>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Product
                </button>
            </div>

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
                                    Draw Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Daily
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="product in products" :key="product.id"
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
                                    <div class="flex items-center" v-if="product.is_daily == 1">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        {{ getEffectiveDrawDate(product) }}
                                    </div>
                                    <div class="flex items-center" v-else>
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        {{ product.draw_date }}
                                    </div>
                                    <div class="text-sm text-gray-500">Time: {{ formatDrawTime(product.draw_time) }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span v-if="product.is_daily == 1"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Active
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactive
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span v-if="product.is_active"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Active
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactive
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <!-- View Button -->
                                        <button @click="router.visit(`/products/${product.id}`)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 hover:text-green-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <!-- Edit Button -->
                                        <button @click="openModal(product)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
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
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="!products || products.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first product.</p>
                        <button @click="openModal(null)"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Product
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Modal -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out"
                        :class="modalVisible ? 'opacity-50' : 'opacity-0'"></div>

                    <!-- Modal Container -->
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform transition-all duration-300 ease-out max-h-[90vh] overflow-y-auto"
                            :class="modalVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit Product' : 'Add New Product' }}
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Left Column -->
                                    <div class="space-y-2">
                                        <!-- Title Input -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product
                                                Title</label>
                                            <input v-model="form.title" type="text"
                                                class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                placeholder="Enter product title" required />
                                        </div>

                                        <!-- Price Input -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price
                                                (AED)</label>
                                            <input v-model="form.price" type="number" step="0.01" min="0"
                                                class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                placeholder="0.00" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                Type</label>
                                            <select v-model="form.draw_type" name="draw_type"
                                                class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                id="">
                                                <option value="once">Once</option>
                                                <option value="regular">Regular</option>
                                            </select>
                                        </div>
                                        <template v-if="form.draw_type === 'regular'">
                                            <div class="grid grid-cols-2 gap-4 mt-5 mb-5">
                                                <div>
                                                    <label class="flex items-center">
                                                        <input v-model="form.regular_type" type="radio" value="daily"
                                                            name="regular_type"
                                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
                                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                                            Daily Draw
                                                        </span>
                                                    </label>
                                                </div>

                                                <div>
                                                    <label class="flex items-center">
                                                        <input v-model="form.regular_type" type="radio"
                                                            name="regular_type" value="hourly"
                                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
                                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                                            Hourly Draw
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-if="form.draw_type === 'once'">
                                            <!-- Draw Date and Time -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                        Date</label>
                                                    <input v-model="form.draw_date" type="date"
                                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                        required />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                        Time</label>
                                                    <input v-model="form.draw_time" type="time"
                                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                        required />
                                                </div>
                                            </div>
                                        </template>


                                        <!-- Game Settings -->
                                        <div class="grid grid-cols-3 gap-1">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pick
                                                    Number</label>
                                                <input v-model="form.pick_number" type="number" min="1"
                                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                    placeholder="3" required />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Type
                                                    Number</label>
                                                <input v-model="form.type_number" type="number" min="1"
                                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                    placeholder="9" required />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Prize
                                                    Type</label>
                                                <select v-model="form.prize_type"
                                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                    required>
                                                    <option value="bet">Bet Based</option>
                                                    <option value="number">Number Based</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="space-y-6">
                                        <!-- Image Upload -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product
                                                Image</label>
                                            <div v-if="imagePreview" class="mb-4 relative">
                                                <img :src="imagePreview" alt="Preview"
                                                    class="w-full h-40 object-cover rounded-xl border-2 border-gray-200" />
                                                <button type="button" @click="removeImage"
                                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="relative">
                                                <input type="file" accept="image/*" @change="handleImageUpload"
                                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                                <div
                                                    class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-200">
                                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                        </path>
                                                    </svg>
                                                    <p class="text-gray-600 font-medium">Click to upload image</p>
                                                    <p class="text-gray-400 text-sm mt-1">PNG, JPG, GIF up to 10MB</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Prizes Section -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Prizes
                                                Configuration</label>
                                            <div v-if="form.prize_type === 'bet'">
                                                <div class="mb-2">
                                                    <div class="flex justify-between">
                                                        <label for="">STRAIGHT</label>
                                                        <input type="number"
                                                            class=" border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <div class="flex justify-between">
                                                        <label for="">RUMBLE</label>
                                                        <input type="number"
                                                            class=" border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex justify-between">
                                                        <label for="">CHANCE</label>
                                                        <input type="number"
                                                            class=" border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="form.prize_type === 'number'" class="mb-4">
                                                <div class="grid grid-cols-3 gap-3 mb-3">
                                                    <select name=""
                                                        class="border-2 border-gray-200 px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        id="prize-key">
                                                        <option disabled value="">Select number</option>

                                                        <option v-for="n in form.pick_number" :key="n" :value="n">
                                                            {{ n }}
                                                        </option>
                                                    </select>
                                                    <input id="prize-value" type="number"
                                                        placeholder="Prize Value (e.g., 3000.00 AED)"
                                                        class="border-2 border-gray-200 px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                                    <button type="button" @click="addPrize"
                                                        class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition-colors duration-200">
                                                        Add Prize
                                                    </button>
                                                </div>
                                            </div>
                                            <div v-if="form.prize_type === 'number'"
                                                class="space-y-2 max-h-48 overflow-y-auto">
                                                <div v-for="(value, key) in form.prizes" :key="key"
                                                    class="flex justify-between items-center bg-white p-3 rounded-lg border">
                                                    <div>
                                                        <span class="font-medium text-gray-700">{{ key }} Numbers : </span>
                                                        <span class="text-gray-600 ml-2">{{ value }}</span>
                                                    </div>
                                                    <button type="button" @click="removePrize(key)"
                                                        class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div v-if="Object.keys(form.prizes).length === 0"
                                                    class="text-center text-gray-500 py-4">
                                                    No prizes added yet
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                                    <button type="button" @click="closeModal"
                                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-semibold">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ isEditing ? 'Update Product' : 'Create Product' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
