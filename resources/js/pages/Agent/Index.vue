<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { can } from '@/helpers/permissions';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Agent List',
        href: '/agents',
    },
];

const { users } = defineProps<{
    users: Array<any>;
}>();

const showModal = ref(false);
const showDeleteModal = ref(false);
const deletingUser = ref(null);
const isEditing = ref(false);
const editingUser = ref(null);
const form = useForm({
    user_type: 'agent',
    role: 'Agent',
    name: '',
    email: '',
    phone: '',
    address: '',
    commission: '',
    trn: '',
    password_confirmation: '',
    photo: null,
    join_date: ''
});

function openModal() {
    showModal.value = true;
}

const editModal = (user) => {
    isEditing.value = true;
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone;
    form.trn = user.agent?.trn;
    form.commission = user.agent?.commission;
    form.address = user.address;
    form.join_date = user.join_date;
    showModal.value = true;
}

function closeModal() {
    editingUser.value = null;
    isEditing.value = false;
    form.name = '';
    form.email = '';
    form.phone = '';
    form.join_date = '';
    form.commission = '';
    form.address = '';
    form.trn = '';
    form.password_confirmation = '';
    showModal.value = false;
}

function handleFileUpload(event) {
    form.photo = event.target.files[0];
}

function submitForm() {
    if (isEditing && editingUser.value) {
        form.post(route('agents.update', editingUser.value.id), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                editingUser.value = null;
                closeModal();
                toast.success('Agent updated successfully.')
            },
        })
    }
    else {
        form.post(route('agents.store'), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                toast.success('Agent created successfully.')
                // router.reload({ only: ['users'] });
                closeModal();
            },
        })
    }
}

function deleteUser(user) {
    showDeleteModal.value = true;
    deletingUser.value = user;
}
const confirmDelete = () => {
    if (deletingUser.value) {
        router.delete(route('agents.delete', deletingUser.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                deletingUser.value = null;
                toast.success('Agent deleted successfully.')
            },
        });
    }
}

function toggleUserStatus(user) {
    router.get(
        route('users.status-change', user.id),
        {},
        {
            preserveScroll: true,
            replace: true,
            onSuccess: () => {
                toast.success('User status updated.');
            },
        }
    );
}
</script>

<template>

    <Head title="Agent List" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="lg:text-4xl font-bold text-gray-900 mb-2">Agents</h1>
                </div>
                <button v-if="can('agent create')" @click="openModal()"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm md:text-md px-3 py-3 rounded-xl shadow-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Agent
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
                                <div class="absolute bottom-1 right-3 w-4 h-4 rounded-full border-2 border-white"
                                    :class="user.is_active === true ? 'bg-green-500' : 'bg-gray-400'"></div>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">{{ user.name }}</h3>
                                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium text-gray-700">Phone:</span> {{ user.phone || 'N/A' }}</p>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <button @click="toggleUserStatus(user)"
                                :class="user.status === 'active' ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50'"
                                class="font-medium transition duration-200 px-3 py-1 rounded-lg">
                                {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                            <div class="space-x-2">
                                <button v-if="can('agent update')" @click="editModal(user)"
                                    class="text-blue-600 hover:text-blue-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-blue-50">
                                    Edit
                                </button>
                                <button v-if="can('agent delete')" @click="deleteUser(user.id)"
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
                                    Agent</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Contact</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Address</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    join Date</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    TRN</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Commission</th>
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
                                <td class="px-6 py-4">
                                    <div class="text-gray-900">{{ user.email }}</div>
                                    <div class="text-gray-500 text-sm">{{ user.phone || 'No phone' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <button @click="can('user status change') ? toggleUserStatus(user) : ''"
                                            :class="user.status !== 'active' ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50'"
                                            class="font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:shadow-md text-sm">
                                            {{ user.status === 'active' ? 'Active' : 'Deactive' }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ user.address || 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-700"> {{
                                    user.join_date
                                        ? new Date(user.join_date).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: '2-digit'
                                        })
                                        : 'N/A'
                                }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ user.agent?.trn || 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ user.agent?.commission+' %' || 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button v-if="can('agent update')" @click="editModal(user)"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:bg-blue-50 hover:shadow-md">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button v-if="can('agent delete')" @click="deleteUser(user)"
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
                        :class="showModal ? 'opacity-50' : 'opacity-0'"></div>

                    <!-- Modal Container -->
                    <div class="flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all duration-300 ease-out"
                            :class="showModal ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-3 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit User' : 'Add New User' }}
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
                            <p v-if="form.errors.role" class="text-red-600 text-sm text-center py-3">
                                {{ form.errors.role }}
                            </p>

                            <!-- Modal Body -->
                            <form @submit.prevent="submitForm" class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name
                                            *</label>
                                        <input v-model="form.name" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter full name" required />
                                        <p v-if="form.errors.name" class="text-red-600 text-sm">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address
                                            *</label>
                                        <input v-model="form.email" type="email"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter email address" required />
                                        <p v-if="form.errors.email" class="text-red-600 text-sm">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone
                                            Number</label>
                                        <input v-model="form.phone" type="tel"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter phone number" />
                                        <p v-if="form.errors.phone" class="text-red-600 text-sm">
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Join Date</label>
                                        <input v-model="form.join_date" type="date"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter phone number" />
                                        <p v-if="form.errors.join_date" class="text-red-600 text-sm">
                                            {{ form.errors.join_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">TRN</label>
                                        <input v-model="form.trn" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter TRN" />
                                        <p v-if="form.errors.trn" class="text-red-600 text-sm">
                                            {{ form.errors.trn }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Commission
                                            (%)</label>
                                        <select v-model="form.commission" name="commission" id="commission" class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300">
                                            <option value="" disabled>Select commission percentage</option>

                                            <option v-for="i in 100" :key="i" :value="i">
                                                {{ i }}%
                                            </option>
                                        </select>
                                        <p v-if="form.errors.commission" class="text-red-600 text-sm">
                                            {{ form.errors.commission }}
                                        </p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                        <textarea v-model="form.address" rows="2"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300 resize-none"
                                            placeholder="Enter full address"></textarea>
                                        <p v-if="form.errors.address" class="text-red-600 text-sm">
                                            {{ form.errors.address }}
                                        </p>
                                    </div>

                                    <!-- Password Section -->
                                    <!-- <div class="md:col-span-2 border-t border-gray-200 pt-3">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                                </path>
                                            </svg>
                                            {{ isEditing ? 'Change Password (optional)' : 'Set Password' }}
                                        </h3>
                                    </div> -->

                                    <!-- <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Password {{ !isEditing ? '*' : '' }}
                                        </label>
                                        <input v-model="form.password" type="password"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter password" :required="!isEditing" />
                                        <p v-if="form.errors.password" class="text-red-600 text-sm">
                                            {{ form.errors.password }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Confirm Password {{ !isEditing ? '*' : '' }}
                                        </label>
                                        <input v-model="form.password_confirmation" type="password"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Confirm password" />
                                        <p v-if="form.errors.password_confirmation" class="text-red-600 text-sm">
                                            {{ form.errors.password_confirmation }}
                                        </p>
                                    </div> -->

                                    <!-- Profile Photo -->
                                    <div class="md:col-span-2 border-t border-gray-200 pt-6">
                                        <p v-if="form.errors.photo" class="text-red-600 text-sm">
                                            {{ form.errors.photo }}
                                        </p>
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
            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Are you sure?</DialogTitle>
                        <DialogDescription>
                            This user
                            <span v-if="deletingUser" class="font-semibold">"{{ deletingUser?.name }}"</span>
                            will be deleted.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteModal = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="confirmDelete">
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
