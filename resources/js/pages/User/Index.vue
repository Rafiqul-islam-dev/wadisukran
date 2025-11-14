<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const _props = defineProps({
    users: Array,
    roles: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const form = ref({
    id: null,
    name: '',
    email: '',
    phone: '',
    address: '',
    role_id: '',
    password: '',
    password_confirmation: '',
    photo: null,
    status: 'active',
});

async function openModal(user) {
    if (user) {
        isEditing.value = true;
        form.value = {
            ...user,
            photo: null,
            password: '',
            password_confirmation: '',
            role_id: user.role?.id || ''
        };
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            name: '',
            email: '',
            phone: '',
            address: '',
            role_id: '',
            password: '',
            password_confirmation: '',
            photo: null,
            status: 'active',
        };
    }
    showModal.value = true;
    await nextTick();
    modalVisible.value = true;
}

async function closeModal() {
    modalVisible.value = false;
    await new Promise(resolve => setTimeout(resolve, 300));
    showModal.value = false;
}

function handleFileUpload(event) {
    form.value.photo = event.target.files[0];
}

function submitForm() {
    const formData = new FormData();
    Object.keys(form.value).forEach((key) => {
        if (key === 'photo' && form.value.photo) {
            formData.append('photo', form.value.photo);
        } else if (form.value[key] !== null && form.value[key] !== '') {
            formData.append(key, form.value[key]);
        }
    });

    if (isEditing.value) {
        router.put(`/users/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/users', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${id}`);
    }
}

function toggleUserStatus(user) {
    const newStatus = user.status === 'active' ? 'inactive' : 'active';
    router.patch(`/users/${user.id}/status`, { status: newStatus });
}

function getRoleColor(roleName) {
    const colors = {
        'Admin': 'bg-red-100 text-red-700 border-red-200',
        'Manager': 'bg-blue-100 text-blue-700 border-blue-200',
        'User': 'bg-green-100 text-green-700 border-green-200',
        'Editor': 'bg-purple-100 text-purple-700 border-purple-200',
        'Viewer': 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-700 border-gray-200';
}
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Users Management</h1>
                    <p class="text-gray-600">Manage system users and their roles</p>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New User
                </button>
            </div>

            <!-- Users Cards Grid (Mobile) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 md:hidden">
                <div v-for="user in users" :key="user.id"
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="relative">
                                <img v-if="user.photo" :src="user.photo" alt="User Photo"
                                    class="w-16 h-16 object-cover rounded-full border-4 border-indigo-100 mr-4" />
                                <div v-else
                                    class="w-16 h-16 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center mr-4">
                                    <span class="text-white font-bold text-xl">{{ user.name.charAt(0) }}</span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full border-2 border-white"
                                    :class="user.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></div>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">{{ user.name }}</h3>
                                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium border mt-1"
                                    :class="getRoleColor(user.role?.name)">
                                    {{ user.role?.name || 'No Role' }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium text-gray-700">Phone:</span> {{ user.phone || 'N/A' }}</p>
                            <p><span class="font-medium text-gray-700">Status:</span>
                                <span :class="user.status === 'active' ? 'text-green-600' : 'text-red-600'"
                                    class="font-medium">
                                    {{ user.status }}
                                </span>
                            </p>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <button @click="toggleUserStatus(user)"
                                :class="user.status === 'active' ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50'"
                                class="font-medium transition duration-200 px-3 py-1 rounded-lg">
                                {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                            <div class="space-x-2">
                                <button @click="openModal(user)"
                                    class="text-blue-600 hover:text-blue-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-blue-50">
                                    Edit
                                </button>
                                <button @click="deleteUser(user.id)"
                                    class="text-red-600 hover:text-red-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table (Desktop) -->
            <div class="hidden md:block overflow-hidden bg-white rounded-2xl shadow-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    User</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Contact</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Role</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Address</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id"
                                class="hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-300">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="relative">
                                            <img v-if="user.photo" :src="user.photo" alt="User Photo"
                                                class="w-12 h-12 object-cover rounded-full border-4 border-indigo-100 shadow-md" />
                                            <div v-else
                                                class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center shadow-md">
                                                <span class="text-white font-bold text-lg">{{ user.name.charAt(0)
                                                }}</span>
                                            </div>
                                            <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white"
                                                :class="user.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-semibold text-gray-900">{{ user.name }}</div>
                                            <div class="text-gray-500 text-sm">ID: {{ user.id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900">{{ user.email }}</div>
                                    <div class="text-gray-500 text-sm">{{ user.phone || 'No phone' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium border"
                                        :class="getRoleColor(user.role?.name)">
                                        {{ user.role?.name || 'No Role' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-2"
                                            :class="user.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></div>
                                        <span class="font-medium"
                                            :class="user.status === 'active' ? 'text-green-700' : 'text-red-700'">
                                            {{ user.status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ user.address || 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button @click="toggleUserStatus(user)"
                                            :class="user.status === 'active' ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50'"
                                            class="font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:shadow-md text-sm">
                                            {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button @click="openModal(user)"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:bg-blue-50 hover:shadow-md">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteUser(user.id)"
                                            class="text-red-600 hover:text-red-800 font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:bg-red-50 hover:shadow-md">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
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
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all duration-300 ease-out"
                            :class="modalVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit User' : 'Add New User' }}
                                    </h2>
                                    <p class="text-gray-600 mt-1">{{ isEditing ? 'Update user information and role' :
                                        'Create a new user account with role assignment' }}</p>
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
                                    <!-- Personal Information -->
                                    <div class="md:col-span-2">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                            Personal Information
                                        </h3>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name
                                            *</label>
                                        <input v-model="form.name" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter full name" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address
                                            *</label>
                                        <input v-model="form.email" type="email"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter email address" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone
                                            Number</label>
                                        <input v-model="form.phone" type="tel"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter phone number" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                        <select v-model="form.status"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                        <textarea v-model="form.address" rows="2"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 resize-none"
                                            placeholder="Enter full address"></textarea>
                                    </div>

                                    <!-- Role Assignment -->
                                    <div class="md:col-span-2 border-t border-gray-200 pt-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                                </path>
                                            </svg>
                                            Role Assignment
                                        </h3>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select Role
                                            *</label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            <div v-for="role in roles" :key="role.id" class="relative cursor-pointer"
                                                @click="form.role_id = role.id">
                                                <input type="radio" :value="role.id" v-model="form.role_id"
                                                    :id="`role_${role.id}`" class="sr-only" />
                                                <label :for="`role_${role.id}`"
                                                    class="block p-4 border-2 rounded-xl transition-all duration-200 hover:bg-gray-50 cursor-pointer"
                                                    :class="form.role_id == role.id ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200'">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h4 class="font-semibold text-gray-900">{{ role.name }}</h4>
                                                        <div v-if="form.role_id == role.id" class="text-indigo-600">
                                                            <svg class="w-5 h-5" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                    clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-2">{{ role.description }}</p>
                                                    <div class="text-xs text-gray-500">
                                                        {{ (role.permissions || []).length }} permissions
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password Section -->
                                    <div class="md:col-span-2 border-t border-gray-200 pt-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                                </path>
                                            </svg>
                                            {{ isEditing ? 'Change Password (optional)' : 'Set Password' }}
                                        </h3>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Password {{ !isEditing ? '*' : '' }}
                                        </label>
                                        <input v-model="form.password" type="password"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter password" :required="!isEditing" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Confirm Password {{ !isEditing ? '*' : '' }}
                                        </label>
                                        <input v-model="form.password_confirmation" type="password"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Confirm password" :required="!isEditing || form.password" />
                                    </div>

                                    <!-- Profile Photo -->
                                    <div class="md:col-span-2 border-t border-gray-200 pt-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Profile
                                            Photo</label>
                                        <div
                                            class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-indigo-400 transition-colors duration-200">
                                            <input type="file" @change="handleFileUpload" accept="image/*"
                                                class="hidden" id="photo-upload" />
                                            <label for="photo-upload" class="cursor-pointer">
                                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                    </path>
                                                </svg>
                                                <p class="text-gray-600">Click to upload photo or drag and drop</p>
                                                <p class="text-gray-400 text-sm mt-1">PNG, JPG up to 10MB</p>
                                            </label>
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
                                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ isEditing ? 'Update User' : 'Create User' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template> 0