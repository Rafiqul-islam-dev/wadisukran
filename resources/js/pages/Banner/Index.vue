<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Banners',
        href: '/banners',
    },
];

const { banners } = defineProps<{
    banners: Array<any>;
}>();

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const imagePreview = ref(null);
const form = ref({
    id: null,
    title: '',
    image: null,
});

async function openModal(banner) {
    if (banner) {
        isEditing.value = true;
        form.value = {
            id: banner.id,
            title: banner.title,
            image: null,
        };
        imagePreview.value = banner.image_url || null;
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            title: '',
            image: null,
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

        // Create preview
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
    // Clear the file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

function submitForm() {
    const formData = new FormData();
    formData.append('title', form.value.title);

    if (form.value.image) {
        formData.append('image', form.value.image);
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(`/banners/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/banners', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteBanner(id) {
    if (confirm('Are you sure you want to delete this banner?')) {
        router.delete(`/banners/${id}`);
    }
}
</script>

<template>

    <Head title="Banners" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Banner Management</h1>
                    <p class="text-gray-600">Manage website banners and promotional content</p>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Banner
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
                                    Banner
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Title
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Created Date
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="banner in banners" :key="banner.id"
                                class="hover:bg-gray-50 transition-colors duration-200">

                                <!-- Banner Image -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div
                                            class="h-16 w-24 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100 flex-shrink-0">
                                            <img v-if="banner.image_url" :src="banner.image_url" :alt="banner.title"
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
                                    <div class="text-sm font-medium text-gray-900">{{ banner.title }}</div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        {{ banner.status || ' ' }}
                                    </span>
                                </td>

                                <!-- Created Date -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        {{ new Date(banner.created_at).toLocaleDateString() }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button @click="openModal(banner)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteBanner(banner.id)"
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
                    <div v-if="!banners || banners.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No banners found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first banner.</p>
                        <button @click="openModal(null)"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Banner
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Modal (unchanged) -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black transition-opacity duration-300 ease-out"
                        :class="modalVisible ? 'opacity-50' : 'opacity-0'"></div>

                    <!-- Modal Container -->
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all duration-300 ease-out"
                            :class="modalVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit Banner' : 'Add New Banner' }}
                                    </h2>
                                    <p class="text-gray-600 mt-1">{{ isEditing ? 'Update banner information and image' :
                                        'Create a new banner with title and image' }}</p>
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
                                <div class="space-y-6">
                                    <!-- Title Input -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Banner
                                            Title</label>
                                        <input v-model="form.title" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter banner title" required />
                                    </div>

                                    <!-- Image Upload -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Banner
                                            Image</label>

                                        <!-- Image Preview -->
                                        <div v-if="imagePreview" class="mb-4 relative">
                                            <img :src="imagePreview" alt="Preview"
                                                class="w-full h-40 object-cover rounded-xl border-2 border-gray-200" />
                                            <button type="button" @click="removeImage"
                                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- File Input -->
                                        <div class="relative">
                                            <input type="file" accept="image/*" @change="handleImageUpload"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                :required="!isEditing && !imagePreview" />
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
                                        {{ isEditing ? 'Update Banner' : 'Create Banner' }}
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
