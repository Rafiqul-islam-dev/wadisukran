<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { can } from '@/helpers/permissions';
import { Rotate3D } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [

    {
        title: 'Trashed Users',
        href: '/users/trashed',
    },
];

const { users } = defineProps<{
    users: Array<any>;
    roles: Array<any>;
}>();

const showDeleteModal = ref(false);
const deletingUser = ref(null);

function deleteUser(user) {
    showDeleteModal.value = true;
    deletingUser.value = user;
}
const confirmDelete = () => {
    if (deletingUser.value) {
        router.delete(route('users.permanent-delete', deletingUser.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                deletingUser.value = null;
                toast.success('User deleted successfully.')
            },
        });
    }
}
const restoreUser = (id) => {
    if (!confirm('Are you sure you want to restore this user?')) {
        return;
    }

    router.get(route('users.restore', id), {}, {
        onSuccess: () => {
            toast.success('User restored successfully.');
        }
    });
};

</script>

<template>

    <Head title="Trashed Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Header -->
            <!-- <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="lg:text-4xl font-bold text-gray-900 mb-2">Trashed Users</h1>
                </div>
            </div> -->

            <!-- Users Cards Grid (Mobile) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 md:hidden">
                <div v-for="user in users" :key="user.id"
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="relative">
                                <img v-if="user.avatar" :src="user.avatar" alt="User Photo"
                                    class="w-16 h-16 object-cover rounded-full border-4 border-indigo-100 mr-4" />
                                <div v-else
                                    class="w-16 h-16 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center mr-4">
                                    <span class="text-white font-bold text-xl">{{ user.name.charAt(0) }}</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">{{ user.name }}</h3>
                                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium border"
                                    v-for="role in user.roles" :key="role.id">
                                    {{ role?.name || 'No Role' }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium text-gray-700">Phone:</span> {{ user.phone || 'N/A' }}</p>
                        </div>
                        <div v-if="user.roles.length && user.roles[0].name != 'Super Admin'"
                            class="flex justify-between items-center mt-4">
                            <div class="space-x-2">
                                <button v-if="can('user restore')" @click="restoreUser(user.id)"
                                    class="text-green-600 hover:text-red-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-green-50 bg-gray-200">
                                    Restore
                                </button>
                                <button v-if="can('user permanent delete')" @click="deleteUser(user.id)"
                                    class="text-red-600 hover:text-red-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-red-50 bg-gray-200">
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
                                    Address</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    join Date</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Deleted at</th>
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
                                        v-for="role in user.roles" :key="role.id">
                                        {{ role?.name || 'No Role' }}
                                    </span>
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
                                <td class="px-6 py-4 text-gray-700"> {{
                                    user.deleted_at
                                        ? new Date(user.deleted_at).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: '2-digit'
                                        })
                                        : 'N/A'
                                }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button v-if="can('user restore')" @click="restoreUser(user.id)"
                                            class="flex gap-2 text-green-500 hover:text-blue-800 font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:bg-blue-50 hover:shadow-md">
                                            <Rotate3D />
                                            Restore
                                        </button>
                                        <button v-if="can('user permanent delete')" @click="deleteUser(user)"
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
