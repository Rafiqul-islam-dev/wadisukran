<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const _props = defineProps({
    agents: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const form = ref({
    id: null,
    name: '',
    join_date: '',
    address: '',
    trn: '',
    username: '',
    email: '',
    photo: null,
});

async function openModal(agent) {
    if (agent) {
        isEditing.value = true;
        form.value = { ...agent, photo: null };
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            name: '',
            join_date: '',
            address: '',
            trn: '',
            username: '',
            email: '',
            photo: null,
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
        } else if (form.value[key] !== null) {
            formData.append(key, form.value[key]);
        }
    });

    if (isEditing.value) {
        router.put(`/agents/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/agents', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteAgent(id) {
    if (confirm('Are you sure you want to delete this agent?')) {
        router.delete(`/agents/${id}`);
    }
}
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Agents</h1>
                    <p class="text-gray-600">Manage your agent network</p>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Agent
                </button>
            </div>

            <!-- Agents Cards Grid (Mobile) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 md:hidden">
                <div v-for="agent in agents" :key="agent.id"
                    class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <img v-if="agent.photo" :src="agent.photo" alt="Agent Photo"
                                class="w-16 h-16 object-cover rounded-full border-4 border-blue-100 mr-4" />
                            <div v-else
                                class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center mr-4">
                                <span class="text-white font-bold text-xl">{{ agent.name.charAt(0) }}</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">{{ agent.name }}</h3>
                                <p class="text-gray-500 text-sm">{{ agent.email }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium text-gray-700">Join Date:</span> {{ agent.join_date }}</p>
                            <p><span class="font-medium text-gray-700">TRN:</span> {{ agent.trn }}</p>
                            <p><span class="font-medium text-gray-700">Username:</span> {{ agent.username }}</p>
                        </div>
                        <div class="flex justify-end space-x-3 mt-4">
                            <button @click="openModal(agent)"
                                class="text-blue-600 hover:text-blue-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-blue-50">
                                Edit
                            </button>
                            <button @click="deleteAgent(agent.id)"
                                class="text-red-600 hover:text-red-800 font-medium transition duration-200 px-3 py-1 rounded-lg hover:bg-red-50">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agents Table (Desktop) -->
            <div class="hidden md:block overflow-hidden bg-white rounded-2xl shadow-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Photo</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Name</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Join Date</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Address</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    TRN</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Username</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Email</th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="agent in agents" :key="agent.id"
                                class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all duration-300">
                                <td class="px-6 py-4">
                                    <img v-if="agent.photo" :src="agent.photo" alt="Agent Photo"
                                        class="w-14 h-14 object-cover rounded-full border-4 border-blue-100 shadow-md" />
                                    <div v-else
                                        class="w-14 h-14 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center shadow-md">
                                        <span class="text-white font-bold text-lg">{{ agent.name.charAt(0) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ agent.name }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ agent.join_date }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ agent.address }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ agent.trn }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ agent.username }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ agent.email }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-3">
                                        <button @click="openModal(agent)"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-all duration-200 px-4 py-2 rounded-lg hover:bg-blue-50 hover:shadow-md">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteAgent(agent.id)"
                                            class="text-red-600 hover:text-red-800 font-medium transition-all duration-200 px-4 py-2 rounded-lg hover:bg-red-50 hover:shadow-md">
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

            <!-- Enhanced Modal with Bootstrap-like Animation -->
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
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit Agent' : 'Add New Agent' }}
                                    </h2>
                                    <p class="text-gray-600 text-sm">Fill in the details below</p>
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
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                                        <input v-model="form.name" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter full name" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Join Date</label>
                                        <input v-model="form.join_date" type="date"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            required />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                        <textarea v-model="form.address" rows="2"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300 resize-none"
                                            placeholder="Enter full address" required></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">TRN Number</label>
                                        <input v-model="form.trn" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter TRN number" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                                        <input v-model="form.username" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter username" required />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email
                                            Address</label>
                                        <input v-model="form.email" type="email"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter email address" required />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Profile
                                            Photo</label>
                                        <div
                                            class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors duration-200">
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
                                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 font-semibold shadow-lg">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ isEditing ? 'Update Agent' : 'Create Agent' }}
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

<style scoped>
/* Custom scrollbar for the modal */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Custom animation for modal entrance */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-enter-active {
    animation: modalSlideIn 0.3s ease-out;
}
</style>