<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const { categories } = usePage().props;
console.log(categories)
const form = useForm({
    name: '',
    description: ''
});

const addCategoryModal = ref(false);

const closeModal = () => {
    addCategoryModal.value = false;
}

const addCategory = () => {
    addCategoryModal.value = true;
}
const handleSubmit = () => {
    form.post(route('categories.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
            toast.success('Category created successfully.')
            closeModal();
        },
    })
}

</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Category Management</h1>
                </div>
                <button @click="addCategory"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Category
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
                                    #
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Description
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(category, index) in categories.data" :key="category.id"
                                class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    {{ (categories?.current_page - 1) *
                                        categories?.per_page + index + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ category.name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ category.description }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <button
                                            :class="category.status !== 1 ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50'"
                                            class="font-medium transition-all duration-200 px-3 py-1 rounded-lg hover:shadow-md text-sm">
                                            {{ category.status === 1 ? 'Active' : 'Inactive' }}
                                        </button>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button
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
                    <!-- <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No banners found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first banner.</p>
                        <button
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Category
                        </button>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Add Category Dialog -->
        <Dialog v-model:open="addCategoryModal">
            <DialogTrigger as-child>
                <Button
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Category
                </button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-bold">Add New Category</DialogTitle>
                </DialogHeader>

                <div class="grid gap-6 py-4">
                    <div class="grid gap-2">
                        <Label for="name" class="text-sm font-semibold">
                            Category Name <span class="text-red-500">*</span>
                        </Label>
                        <Input id="name" v-model="form.name" placeholder="Enter category name" class="w-full" />
                        <p v-if="form.errors.name" class="text-red-600 text-sm">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description" class="text-sm font-semibold">
                            Description
                        </Label>
                        <Textarea id="description" v-model="form.description"
                            placeholder="Enter category description (optional)" class="min-h-[100px] resize-none" />
                        <p v-if="form.errors.description" class="text-red-600 text-sm">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">
                        Cancel
                    </Button>
                    <Button type="submit" @click="handleSubmit"
                        class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                        Save Category
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
