<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    roles: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const form = ref({
    id: null,
    name: '',
    description: '',
    permissions: [],
});

const availablePermissions = [
    'create_users',
    'edit_users',
    'delete_users',
    'view_users',
    'create_agents',
    'edit_agents',
    'delete_agents',
    'view_agents',
    'manage_roles',
    'view_reports',
    'system_settings',
    'daily_reports',
    'daily_summary',
    'cancle_orders',
    'check_winners',
];

async function openModal(role) {
    if (role) {
        isEditing.value = true;
        form.value = {
            ...role,
            permissions: role.permissions || []
        };
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            name: '',
            description: '',
            permissions: [],
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

function togglePermission(permission) {
    const index = form.value.permissions.indexOf(permission);
    if (index > -1) {
        form.value.permissions.splice(index, 1);
    } else {
        form.value.permissions.push(permission);
    }
}

function submitForm() {
    const formData = {
        name: form.value.name,
        description: form.value.description,
        permissions: form.value.permissions,
    };

    if (isEditing.value) {
        router.put(`/roles/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/roles', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteRole(id) {
    if (confirm('Are you sure you want to delete this role?')) {
        router.delete(`/roles/${id}`);
    }
}
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Roles & Permissions</h1>
                    <p class="text-gray-600">Manage user roles and their permissions</p>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-green-700 hover:to-emerald-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Role
                </button>
            </div>

            <!-- Roles Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="role in roles" :key="role.id"
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div
                                    class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900">{{ role.name }}</h3>
                                    <p class="text-gray-600 text-sm">{{ role.users_count || 0 }} users</p>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-4">{{ role.description }}</p>

                        <div class="mb-4">
                            <h4 class="font-medium text-gray-700 mb-2">Permissions:</h4>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="permission in (role.permissions || []).slice(0, 3)" :key="permission"
                                    class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                    {{ permission.replace('_', ' ') }}
                                </span>
                                <span v-if="(role.permissions || []).length > 3"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                    +{{ (role.permissions || []).length - 3 }} more
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button @click="openModal(role)"
                                class="text-blue-600 hover:text-blue-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-blue-50">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Edit
                            </button>
                            <button @click="deleteRole(role.id)"
                                class="text-red-600 hover:text-red-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-red-50">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                Delete
                            </button>
                        </div>
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
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all duration-300 ease-out"
                            :class="modalVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit Role' : 'Add New Role' }}
                                    </h2>
                                    <!-- <p class="text-gray-600 mt-1">{{ isEditing ? 'Update role information and
                                        permissions' : 'Create a new role with permissions' }}</p> -->
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
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Role Name</label>
                                        <input v-model="form.name" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter role name (e.g., Admin, Manager, User)" required />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                        <textarea v-model="form.description" rows="3"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 hover:border-gray-300 resize-none"
                                            placeholder="Describe what this role can do" required></textarea>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-3">Permissions</label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div v-for="permission in availablePermissions" :key="permission"
                                                class="flex items-center p-3 border-2 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50"
                                                :class="form.permissions.includes(permission) ? 'border-green-500 bg-green-50' : 'border-gray-200'"
                                                @click="togglePermission(permission)">
                                                <input type="checkbox" :checked="form.permissions.includes(permission)"
                                                    class="w-4 h-4 text-green-600 rounded focus:ring-green-500 mr-3"
                                                    @change="togglePermission(permission)" />
                                                <div>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{permission.replace(/_/g, ' ').replace(/\b\w/g, l =>
                                                        l.toUpperCase()) }}
                                                    </span>
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
                                        class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ isEditing ? 'Update Role' : 'Create Role' }}
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