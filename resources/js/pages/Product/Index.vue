<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { BreadcrumbItem } from '@/types';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product List',
        href: '/products',
    },
];

const { products, categories } = defineProps<{
    products: Array<any>;
    categories: Array<any>;
}>();
const { company_setting } = usePage().props;

const showModal = ref(false);
const deleteModal = ref(false);
const deletingProduct = ref(null);
const isEditing = ref(false);
const editingProduct = ref(null);
const imagePreview = ref(null);
const form = useForm({
    title: '',
    category_id: '',
    price: '',
    draw_date: '',
    draw_time: '',
    draw_type: 'once',
    regular_type: '',
    image: null,
    pick_number: '',
    prize_type: 'bet',
    type_number: '',
    bet_prizes: [
        { type: 'bet', name: 'straight', prize: 0, chance_number: null },
        { type: 'bet', name: 'rumble', prize: 0, chance_number: null },
        { type: 'bet', name: 'chance', prize: 0, chance_number: 1 }
    ],
    number_prizes: []
});

const editProduct = (product) => {
    isEditing.value = true;
    editingProduct.value = product;
    form.title = product.title;
    form.category_id = product.category_id;
    form.price = product.price;
    form.draw_type = product.draw_type === 'once' ? 'once' : 'regular';
    form.draw_date = product.draw_date
        ? product.draw_date.substring(0, 10)
        : '';
    form.draw_time = product.draw_time;
    form.regular_type = product.draw_type === 'hourly' || product.draw_type === 'daily' ? product.draw_type : '';
    form.image = null;
    imagePreview.value = product.image_url;
    form.pick_number = product.pick_number;
    form.prize_type = product.prize_type;
    form.type_number = product.type_number;
    form.bet_prizes = product.prize_type === 'bet' ? product.prizes : [
        { type: 'bet', name: 'Straight', prize: 0, chance_number: null },
        { type: 'bet', name: 'Rumble', prize: 0, chance_number: null },
        { type: 'bet', name: 'Chance', prize: 0, chance_number: 1 }
    ];
    form.number_prizes = product.prize_type === 'number' ? product.prizes : [];
    showModal.value = true;
}

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    imagePreview.value = null;
    form.reset();
}

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

function addPrize() {
    const name = document.getElementById('prize-key').value;
    const prize = document.getElementById('prize-value').value;

    const exists = form.number_prizes.some(p => p.name === name);

    if (exists) {
        toast.error("This prize is already added!");
        return;
    }

    if (!form.number_prizes) {
        form.number_prizes = [];
    }

    if (name && prize) {
        form.number_prizes.push({
            type: 'number',
            name: name,
            prize: prize
        });

        // optional: clear inputs
        document.getElementById('prize-key').value = '';
        document.getElementById('prize-value').value = '';
    }
}


function removePrize(name) {
    if (!form.number_prizes) return;

    form.number_prizes = form.number_prizes.filter(prize => prize.name !== name);
}


function submitForm() {
    if (editingProduct.value) {
        form.post(route('products.update', editingProduct.value.id), {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Product updated successfully.')
                closeModal();
            }
        })
    }
    else {
        form.post(route('products.store'), {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Product created successfully.')
                closeModal();
            }
        })
    }
}

function deleteProduct(product) {
    deleteModal.value = true;
    deletingProduct.value = product;
}

function confirmDelete(){
    router.delete(`/products/${deletingProduct?.value?.id}`, {
        onSuccess: () => {
            deleteModal.value = false;
            toast.success('Product deleted successfully.');
        }
    });
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

// Watch pick_number changes to update bet_prizes array
watch(() => form.pick_number, (newPickNumber) => {
    if (form.prize_type === 'bet' && newPickNumber) {
        const pickNum = parseInt(newPickNumber);

        // Always keep straight and rumble
        const basePrizes = [
            {
                type: 'bet',
                name: 'Straight',
                prize: form.bet_prizes.find(p => p.name === 'Straight')?.prize || 0,
                chance_number: null
            },
            {
                type: 'bet',
                name: 'Rumble',
                prize: form.bet_prizes.find(p => p.name === 'Rumble')?.prize || 0,
                chance_number: null
            }
        ];

        // Add chance prizes based on pick_number
        for (let i = 1; i <= pickNum; i++) {
            const existingPrize = form.bet_prizes.find(p => p.name === 'Chance' && p.chance_number === i);
            basePrizes.push({
                type: 'bet',
                name: 'Chance',
                prize: existingPrize?.prize || 0,
                chance_number: i
            });
        }

        form.bet_prizes = basePrizes;
    }
});

// Computed property to get chance prizes dynamically
const chancePrizes = computed(() => {
    if (!form.pick_number) return [];
    const pickNum = parseInt(form.pick_number);
    return Array.from({ length: pickNum }, (_, i) => i + 1);
});

function updateBetPrize(name, chanceNumber, value) {
    const prizeIndex = form.bet_prizes.findIndex(p => {
        if (name === 'Chance') {
            return p.name === name && p.chance_number === chanceNumber;
        }
        return p.name === name;
    });

    if (prizeIndex !== -1) {
        form.bet_prizes[prizeIndex].prize = parseFloat(value) || 0;
    } else {
        // Add new prize if it doesn't exist
        form.bet_prizes.push({
            type: 'bet',
            name: name,
            prize: parseFloat(value) || 0,
            chance_number: name === 'Chance' ? chanceNumber : null
        });
    }
}

const statusChange = (product) => {
    router.put(route('products.status-change', product.id), {}, {
        onSuccess: () => {
            toast.success('Product status changed')
        }
    })
}

const handleCategoryChange = (e: Event) => {
  const id = (e.target as HTMLSelectElement).value;

  const category = categories?.find((c: any) => String(c.id) === String(id));
  if (!category) return;

  // category.draw_type: 'once' | 'daily' | 'hourly'
  if (category.draw_type && category.draw_type !== 'once') {
    form.draw_type = 'regular';
    form.regular_type = category.draw_type;
  } else {
    form.draw_type = 'once';
    form.regular_type = '';
  }
};

</script>

<template>
    <Head title="Product List" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-xl lg:text-4xl font-bold text-gray-900 mb-2">Products</h1>
                </div>
                <button @click="openModal()"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold text-sm lg:text-lg">
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
                                    Category
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
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ product.category?.name }}</div>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-green-600">{{ product.price }} {{ company_setting?.currency }}</div>
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

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <div @click="statusChange(product)" class="cursor-pointer">
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
                                    </div>
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
                                        <button @click="editProduct(product)"
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
                                        <button @click="deleteProduct(product)"
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
                        <p class="text-gray-500 mb-4">Get started by creating your first product.</p>
                        <button @click="openModal()"
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
                    <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out opacity-50"></div>

                    <!-- Modal Container -->
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div
                            class="opacity-100 scale-100 translate-y-0 relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform transition-all duration-300 ease-out max-h-[90vh] overflow-y-auto">

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
                                            <p v-if="form.errors.title" class="text-red-600 text-sm">
                                                {{ form.errors.title }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Category
                                            </label>

                                            <select v-model="form.category_id" v-on:change="handleCategoryChange" placeholder="Select a category" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl
               focus:ring-2 focus:ring-blue-500 focus:border-blue-500
               transition-all duration-200 hover:border-gray-300" required>
                                                <option value="">Select a category</option>
                                                <option v-for="category in categories" :key="category.id"
                                                    :value="category.id">
                                                    {{ category.name }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.category_id" class="text-red-600 text-sm">
                                                {{ form.errors.category_id }}
                                            </p>
                                        </div>

                                        <!-- Price Input -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price
                                                (AED)</label>
                                            <input v-model="form.price" type="number" step="0.01" min="0"
                                                class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                placeholder="0.00" required />
                                            <p v-if="form.errors.price" class="text-red-600 text-sm">
                                                {{ form.errors.price }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                Type</label>
                                            <select v-model="form.draw_type" name="draw_type" disabled="true"
                                                class="disabled:bg-gray-200 disabled:cursor-not-allowed w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                id="">
                                                <option value="once">Once</option>
                                                <option value="regular">Regular</option>
                                            </select>
                                            <p v-if="form.errors.draw_type" class="text-red-600 text-sm">
                                                {{ form.errors.draw_type }}
                                            </p>
                                        </div>
                                        <template v-if="form.draw_type === 'regular'">
                                            <div class="grid grid-cols-2 gap-4 mt-5 mb-5">
                                                <div>
                                                    <label class="flex items-center">
                                                        <input v-model="form.regular_type" type="radio" value="daily" :disabled="form.regular_type !== 'daily'"
                                                            name="regular_type"
                                                            class="w-4 h-4 text-blue-600 bg-gray-100 disabled:cursor-not-allowed border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
                                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                                            Daily Draw
                                                        </span>
                                                    </label>
                                                </div>

                                                <div>
                                                    <label class="flex items-center">
                                                        <input v-model="form.regular_type" type="radio" :disabled="form.regular_type !== 'hourly'"
                                                            name="regular_type" value="hourly"
                                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
                                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                                            Hourly Draw
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <p v-if="form.errors.regular_type" class="text-red-600 text-sm">
                                                {{ form.errors.regular_type }}
                                            </p>
                                        </template>
                                        <template v-if="form.draw_type === 'once' || form.regular_type === 'daily'">
                                            <!-- Draw Date and Time -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                        Date</label>
                                                    <input v-model="form.draw_date" type="date"
                                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                        required />
                                                    <p v-if="form.errors.draw_date" class="text-red-600 text-sm">
                                                        {{ form.errors.draw_date }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Draw
                                                        Time</label>
                                                    <input v-model="form.draw_time" type="time"
                                                        class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                        required />
                                                    <p v-if="form.errors.draw_time" class="text-red-600 text-sm">
                                                        {{ form.errors.draw_time }}
                                                    </p>
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
                                                <p v-if="form.errors.pick_number" class="text-red-600 text-sm">
                                                    {{ form.errors.pick_number }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Max Type
                                                    Number</label>
                                                <input v-model="form.type_number" type="number" min="1"
                                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                                    placeholder="9" required />
                                                <p v-if="form.errors.type_number" class="text-red-600 text-sm">
                                                    {{ form.errors.type_number }}
                                                </p>
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
                                                <p v-if="form.errors.prize_type" class="text-red-600 text-sm">
                                                    {{ form.errors.prize_type }}
                                                </p>
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
                                            <p v-if="form.errors.image" class="text-red-600 text-sm">
                                                {{ form.errors.image }}
                                            </p>
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
                                                <p v-if="form.errors.bet_prizes" class="text-red-600 text-sm">
                                                    {{ form.errors.bet_prizes }}
                                                </p>
                                                <div class="mb-2">
                                                    <div class="flex justify-between">
                                                        <label>STRAIGHT</label>
                                                        <input type="number"
                                                            :value="form.bet_prizes.find(p => p.name === 'Straight')?.prize || 0"
                                                            @input="updateBetPrize('Straight', null, $event.target.value)"
                                                            class="border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <div class="flex justify-between">
                                                        <label>RUMBLE</label>
                                                        <input type="number"
                                                            :value="form.bet_prizes.find(p => p.name === 'Rumble')?.prize || 0"
                                                            @input="updateBetPrize('Rumble', null, $event.target.value)"
                                                            class="border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                                    </div>
                                                </div>
                                                <!-- Dynamic Chance Prizes -->
                                                <div v-for="i in chancePrizes" :key="i" class="mb-2">
                                                    <div class="flex justify-between">
                                                        <label>CHANCE {{ i }}</label>
                                                        <input type="number"
                                                            :value="form.bet_prizes.find(p => p.name === 'Chance' && p.chance_number === i)?.prize || 0"
                                                            @input="updateBetPrize('Chance', i, $event.target.value)"
                                                            class="border-1 border-gray-200 px-4 py-1 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
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
                                                <p v-if="form.errors.number_prizes" class="text-red-600 text-sm">
                                                    {{ form.errors.number_prizes }}
                                                </p>
                                                <div v-for="(value, key) in form.number_prizes" :key="key"
                                                    class="flex justify-between items-center bg-white p-3 rounded-lg border">
                                                    <div>
                                                        <span class="font-medium text-gray-700">{{ value.name }} Numbers
                                                            :
                                                        </span>
                                                        <span class="text-gray-600 ml-2">{{ value.prize }}</span>
                                                    </div>
                                                    <button type="button" @click="removePrize(value.name)"
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
                                                <div v-if="Object.keys(form.number_prizes).length === 0"
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

         <Dialog v-model:open="deleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This Product
                        <span v-if="deletingProduct" class="font-semibold">"{{ deletingProduct?.title }}"</span>
                        will delete permanently.
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
