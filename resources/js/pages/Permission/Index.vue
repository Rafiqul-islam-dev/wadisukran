<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DropdownMenu from '@/components/ui/dropdown-menu/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/DropdownMenuTrigger.vue';
import { Button } from '@/components/ui/button';
import { MoreHorizontal } from 'lucide-vue-next';
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';

const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const deletingPermission = ref(null);
const props = defineProps<{
    permissions: {
        data: Array<{
            id: string;
            name: string;
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();

const editingPermission = ref<number | null>(null);

const form = useForm({
    name: ''
})

const openModal = () => {
    isModalOpen.value = true;
};
const deletePermissionModal = (permission) => {
    isDeleteModalOpen.value = true;
    deletingPermission.value = permission;
};

const editPermission = (permission: { id: number, name: string }) => {
    editingPermission.value = permission.id;
    form.name = permission.name;
    openModal();
}

const closeModal = () => {
    editingPermission.value = null;
    form.reset();
    isModalOpen.value = false;
};

const handleSubmit = () => {
    if (!editingPermission.value) {
        form.post(route('permissions.store'), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                router.reload({ only: ['permissions'] });
                closeModal();
            },
        })
    }
    else {
        form.post(route('permissions.update', editingPermission.value), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                editingPermission.value = null;
                router.reload({ only: ['permissions'] });
                closeModal();
            },
        })
    }
};

const confirmDelete = () => {
        if (deletingPermission.value) {
            router.delete(route('permissions.delete', deletingPermission.value.id), {
                onSuccess: () => {
                    isDeleteModalOpen.value = false;
                    deletingPermission.value = null;
                    // router.reload({ only: ['permissions'] });
                },
            });
        }
    };

const buttonText = computed(() =>
    editingPermission.value ? "Update Permission" : "Create Permission"
);

</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Permissions</h1>
                </div>
                <button @click="openModal"
                    class="bg-gradient-to-r cursor-pointer from-green-600 to-emerald-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-green-700 hover:to-emerald-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Permission
                </button>
            </div>
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">#</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(permission, index) in props.permissions.data" :key="permission.id">
                            <TableCell class="font-medium">{{ (props.permissions.current_page - 1) *
                                props.permissions.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ permission.name }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="editPermission(permission)">Edit</DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="text-destructive" @click="deletePermissionModal(permission)">
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div class="mt-4 flex justify-end py-5">
                    <nav class="flex items-center space-x-1">
                        <button v-for="(link, i) in props.permissions.links" :key="i"
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

            </div>
        </div>
        <!-- Create Permission Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-bold">Create New Permission</DialogTitle>
                    <DialogDescription>
                        Add a new permission to your system. Fill in the details below.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-6 py-4">
                    <div class="grid gap-2">
                        <Label htmlFor="name" class="text-sm font-medium">
                            Permission Name
                        </Label>
                        <Input id="name" v-model="form.name" placeholder="e.g., manage-users" class="col-span-3" />
                        <p v-if="form.errors.name" class="text-red-600 text-sm">
                            {{ form.errors.name }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <button type="button" @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" @click="handleSubmit"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-md hover:from-green-700 hover:to-emerald-700 transition-all">
                        {{ buttonText }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

         <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This permission
                        <span v-if="deletingPermission" class="font-semibold">"{{ deletingPermission?.name }}"</span>
                        will delete permanently.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isDeleteModalOpen = false">
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
