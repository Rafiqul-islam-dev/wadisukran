<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

const { companies } = defineProps<{
    companies: Array<any>;
}>();

const showModal = ref(false);
const isEditing = ref(false);
const modalVisible = ref(false);
const logoPreview = ref(null);
const form = ref({
    id: null,
    name: '',
    address: '',
    phone: '',
    whatsapp: '',
    trn_no: '',
    currency: 'USD',
    email: '',
    website: '',
    licence_no: '',
    bank_account: '',
    details: '',
    logo: null,
});

const currencies = [
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
    { value: 'GBP', label: 'GBP - British Pound' },
    { value: 'AED', label: 'AED - UAE Dirham' },
    { value: 'SAR', label: 'SAR - Saudi Riyal' },
    { value: 'INR', label: 'INR - Indian Rupee' },
];

async function openModal(company) {
    if (company) {
        isEditing.value = true;
        form.value = {
            id: company.id,
            name: company.name || '',
            address: company.address || '',
            phone: company.phone || '',
            whatsapp: company.whatsapp || '',
            trn_no: company.trn_no || '',
            currency: company.currency || 'USD',
            email: company.email || '',
            website: company.website || '',
            licence_no: company.licence_no || '',
            bank_account: company.bank_account || '',
            details: company.details || '',
            logo: null,
        };
        logoPreview.value = company.logo_url || null;
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            name: '',
            address: '',
            phone: '',
            whatsapp: '',
            trn_no: '',
            currency: 'USD',
            email: '',
            website: '',
            licence_no: '',
            bank_account: '',
            details: '',
            logo: null,
        };
        logoPreview.value = null;
    }
    showModal.value = true;
    await nextTick();
    modalVisible.value = true;
}

async function closeModal() {
    modalVisible.value = false;
    await new Promise(resolve => setTimeout(resolve, 300));
    showModal.value = false;
    logoPreview.value = null;
}

function handleLogoUpload(event) {
    const file = event.target.files[0];
    if (file) {
        form.value.logo = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function removeLogo() {
    form.value.logo = null;
    logoPreview.value = null;
    // Clear the file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

function submitForm() {
    const formData = new FormData();

    // Append all form fields
    Object.keys(form.value).forEach(key => {
        if (key !== 'id' && key !== 'logo' && form.value[key] !== null) {
            formData.append(key, form.value[key]);
        }
    });

    if (form.value.logo) {
        formData.append('logo', form.value.logo);
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(`/company-settings/${form.value.id}`, formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post('/company-settings', formData, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteCompany(id) {
    if (confirm('Are you sure you want to delete this company setting?')) {
        router.delete(`/company-settings/${id}`);
    }
}
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-4xl font-bold text-gray-900 mb-2">Company Settings</h3>
                </div>
                <button @click="openModal(null)"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 font-semibold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Company
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
                                    Logo
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Company Info
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Business Details
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
                            <tr v-for="company in companies" :key="company.id"
                                class="hover:bg-gray-50 transition-colors duration-200">

                                <!-- Logo -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div
                                            class="h-16 w-16 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100 flex-shrink-0">
                                            <img v-if="company.logo_url" :src="company.logo_url" :alt="company.name"
                                                class="w-full h-full object-cover" />
                                            <div v-else class="flex items-center justify-center h-full">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                    </path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Company Info -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                                    <div class="text-sm text-gray-500" v-if="company.address">{{ company.address }}
                                    </div>
                                    <div class="text-sm text-gray-500">Currency: {{ company.currency }}</div>
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900" v-if="company.phone">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                        {{ company.phone }}
                                    </div>
                                    <div class="text-sm text-gray-500" v-if="company.email">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        {{ company.email }}
                                    </div>
                                    <div class="text-sm text-gray-500" v-if="company.whatsapp">
                                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.106" />
                                        </svg>
                                        {{ company.whatsapp }}
                                    </div>
                                </td>

                                <!-- Business Details -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900" v-if="company.trn_no">TRN: {{ company.trn_no }}
                                    </div>
                                    <div class="text-sm text-gray-500" v-if="company.licence_no">Licence: {{
                                        company.licence_no }}</div>
                                    <div class="text-sm text-gray-500" v-if="company.bank_account">Bank: {{
                                        company.bank_account }}</div>
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
                                        {{ new Date(company.created_at).toLocaleDateString() }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button @click="openModal(company)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteCompany(company.id)"
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
                    <div v-if="!companies || companies.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No company settings found</h3>
                        <p class="text-gray-500 mb-4">Get started by adding your company information.</p>
                        <button @click="openModal(null)"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Company
                        </button>
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
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform transition-all duration-300 ease-out"
                            :class="modalVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

                            <!-- Modal Header -->
                            <div
                                class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ isEditing ? 'Edit Company Settings' : 'Add Company Settings' }}
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
                                    <!-- Logo Upload -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Company
                                            Logo</label>

                                        <!-- Logo Preview -->
                                        <div v-if="logoPreview" class="mb-4 relative">
                                            <img :src="logoPreview" alt="Logo Preview"
                                                class="w-32 h-32 object-cover rounded-xl border-2 border-gray-200" />
                                            <button type="button" @click="removeLogo"
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
                                            <input type="file" accept="image/*" @change="handleLogoUpload"
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                            <div
                                                class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-200">
                                                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                    </path>
                                                </svg>
                                                <p class="text-gray-600 font-medium">Click to upload logo</p>
                                                <p class="text-gray-400 text-sm mt-1">PNG, JPG, GIF up to 2MB</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Company Name -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Company Name
                                            *</label>
                                        <input v-model="form.name" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter company name" required />
                                    </div>

                                    <!-- Currency -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Currency</label>
                                        <select v-model="form.currency"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300">
                                            <option v-for="currency in currencies" :key="currency.value"
                                                :value="currency.value">
                                                {{ currency.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Address -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                        <textarea v-model="form.address" rows="3"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter company address"></textarea>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                                        <input v-model="form.phone" type="tel"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter phone number" />
                                    </div>

                                    <!-- WhatsApp -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp</label>
                                        <input v-model="form.whatsapp" type="tel"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter WhatsApp number" />
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                        <input v-model="form.email" type="email"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter email address" />
                                    </div>

                                    <!-- Website -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Website</label>
                                        <input v-model="form.website" type="url"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter website URL" />
                                    </div>

                                    <!-- TRN No -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">TRN No</label>
                                        <input v-model="form.trn_no" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter TRN number" />
                                    </div>

                                    <!-- Licence No -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Licence No</label>
                                        <input v-model="form.licence_no" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter licence number" />
                                    </div>

                                    <!-- Bank Account -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Bank
                                            Account</label>
                                        <input v-model="form.bank_account" type="text"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter bank account details" />
                                    </div>

                                    <!-- Details -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Additional
                                            Details</label>
                                        <textarea v-model="form.details" rows="4"
                                            class="w-full border-2 border-gray-200 px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                            placeholder="Enter additional company details"></textarea>
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
                                        {{ isEditing ? 'Update Company' : 'Create Company' }}
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
