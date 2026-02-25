<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Check } from 'lucide-vue-next';
import { Permission } from '@/types/permission';
import { toast } from 'vue-sonner';
import { can } from '@/helpers/permissions';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
];

const props = defineProps<{
    permissions: Permission[]
    roles: []
}>();

const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const editingRole = ref(null);
const deletingRole = ref(null);
const form = useForm({
    name: '',
    permissions: []
});

function openModal() {
    showModal.value = true;
}

const editModal = (role) => {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = role.permissions.map(p => p.name);
    isEditing.value = true;
    showModal.value = true;
}

function closeModal() {
    editingRole.value = null;
    form.name = '';
    form.permissions = [];
    isEditing.value = false;
    showModal.value = false;
}

const togglePermission = (permission) => {
    if (form.permissions.includes(permission.name)) {
        form.permissions = form.permissions.filter(p => p !== permission.name);
    } else {
        form.permissions.push(permission.name);
    }
};

function openDeleteModal(role) {
    deletingRole.value = role;
    showDeleteModal.value = true;
}

const confirmDelete = () => {
    if (deletingRole.value) {
        router.delete(route('roles.delete', deletingRole.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                deletingRole.value = null;
                toast.success('Role deleted successfully.')
                // router.reload({ only: ['permissions'] });
            },
        });
    }
}

function handleSubmit() {
    if (isEditing && editingRole.value) {
        form.post(route('roles.update', editingRole.value.id), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                editingRole.value = null;
                closeModal();
                toast.success('Role updated successfully.')
                router.reload({ only: ['roles'] });
            },
        })
    }
    else {
        form.post(route('roles.store'), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                toast.success('Role created successfully.')
                router.reload({ only: ['roles'] });
                closeModal();
            },
        })
    }
}
</script>

<template>
    <Head title="Roles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="lg:text-4xl font-bold text-gray-900 mb-2">Roles</h1>
                </div>
                <button v-if="can('role create')" @click="openModal()"
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
                <div v-for="role in props.roles" :key="role.id"
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gray-400 rounded-xl flex items-center justify-center mr-4">
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

                        <div class="mb-4">
                            <h4 class="font-medium text-gray-700 mb-2">Permissions:</h4>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="permission in (role.permissions || []).slice(0, 3)" :key="permission"
                                    class="px-2 py-1 bg-gray-100 text-black rounded-full text-xs font-medium">
                                    {{ permission.name }}
                                </span>
                                <span v-if="(role.permissions || []).length > 3"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                    +{{ (role.permissions || []).length - 3 }} more
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button v-if="can('role update')" @click="editModal(role)"
                                class="text-blue-600 hover:text-blue-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-blue-50">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Edit
                            </button>
                            <button v-if="can('role delete')" @click="openDeleteModal(role)"
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


            <!-- Modal -->
            <Dialog v-model:open="showModal" @update:open="(open) => !open && closeModal()" class="">
                <DialogContent class="max-w-[93%] max-h-[90vh] overflow-y-auto">
                    <DialogHeader
                        class="bg-gradient-to-r from-green-50 to-emerald-50 -m-6 mb-2 p-4 rounded-t-lg w-full">
                        <DialogTitle class="text-2xl">
                            {{ isEditing ? 'Edit Role' : 'Add New Role' }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name" class="text-sm font-semibold">Role Name</Label>
                            <Input v-model="form.name" id="name"
                                placeholder="Enter role name (e.g., Admin, Manager, User)" class="border-2" />
                            <p v-if="form.errors.name" class="text-red-600 text-sm">
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold">Permissions</Label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div @click="togglePermission(permission)" v-for="permission in props.permissions"
                                    :key="permission.id" :class="[
                                        'flex items-center space-x-3 p-3 border-2 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50',
                                        form.permissions.includes(permission.name)
                                            ? 'border-green-500 bg-green-50'
                                            : 'border-gray-200'
                                    ]">
                                    <input type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                        :value="permission.name" v-model="form.permissions" />
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ permission.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="border-t pt-6 mt-8">
                        <Button type="button" variant="outline" @click="closeModal" class="px-6">
                            Cancel
                        </Button>
                        <Button type="button" @click="handleSubmit"
                            class="px-8 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700">
                            <Check class="w-5 h-5 mr-2" />
                            {{ isEditing ? 'Update Role' : 'Create Role' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteModal">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Are you sure?</DialogTitle>
                        <DialogDescription>
                            This role
                            <span v-if="deletingRole" class="font-semibold">"{{ deletingRole?.name }}"</span>
                            will delete permanently.
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
