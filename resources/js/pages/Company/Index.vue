<script setup lang="ts">
import { ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Company Settings',
        href: '/company-settings',
    },
];

const { company_setting } = usePage().props;
const form = useForm({
    name: company_setting?.name ?? '',
    logo: null as File | null,
    email: company_setting?.email ?? '',
    phone: company_setting?.phone ?? '',
    whatsapp: company_setting?.whatsapp ?? '',
    address: company_setting?.address ?? '',
    trn_no: company_setting?.trn_no ?? '',
    currency: company_setting?.currency ?? '',
    website: company_setting?.website ?? '',
    licence_no: company_setting?.licence_no ?? '',
    bank_account: company_setting?.bank_account ?? '',
    vat: company_setting?.vat ?? '',
});
const handleLogoUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo = target.files[0];
    }
};

const saveChanges = () => {
    form.post(route('company-settings.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Company information updated successfully!');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-50 p-2">
            <div class="mx-auto">
                <Card>
                    <CardHeader>
                        <CardTitle>Company Information</CardTitle>
                        <CardDescription>Update your company's basic information and contact details
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Shop Name -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div class="space-y-2">
                                <Label for="shopName">Name</Label>
                                <Input id="shopName" v-model="form.name" placeholder="Enter company name"
                                    class="max-w-4xl" />
                            </div>
                            <!-- Email Address -->
                            <div class="space-y-2">
                                <Label for="email">Email Address</Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="your@email.com"
                                    class="max-w-4xl" />
                            </div>
                            <!-- Trn Address -->
                            <div class="space-y-2">
                                <Label for="trn">TRN</Label>
                                <Input id="trn" v-model="form.trn_no" type="text" placeholder="Enter TRN"
                                    class="max-w-4xl" />
                            </div>

                            <!-- License -->
                            <div class="space-y-2">
                                <Label for="license">License</Label>
                                <Input id="license" v-model="form.licence_no" type="text" placeholder="License Number"
                                    class="max-w-4xl" />
                            </div>

                            <!-- Phone Number -->
                            <div class="space-y-2">
                                <Label for="phone">Phone Number</Label>
                                <Input id="phone" v-model="form.phone" type="tel" placeholder="+1234567890"
                                    class="max-w-4xl" />
                            </div>
                            <!-- Whatsapp Number -->
                            <div class="space-y-2">
                                <Label for="whatsapp">WhatsApp Number</Label>
                                <Input id="whatsapp" v-model="form.whatsapp" type="tel" placeholder="+1234567890"
                                    class="max-w-4xl" />
                            </div>

                            <!-- Currency -->
                            <div class="space-y-2">
                                <Label for="currency">Currency</Label>
                                <Input id="currency" v-model="form.currency" type="text" placeholder="Currency"
                                    class="max-w-4xl" />
                            </div>

                            <!-- Bank  -->
                            <div class="space-y-2">
                                <Label for="bank">Bank Account</Label>
                                <Input id="bank" v-model="form.bank_account" type="text" placeholder="Bank Name"
                                    class="max-w-4xl" />
                            </div>
                            <div class="space-y-2">
                                <Label for="vat">Vat (%)</Label>

                                <select name="vat" id="vat" v-model="form.vat" class="w-full border rounded py-2 px-2">
                                    <option value="">Select VAT</option>

                                    <option v-for="i in 99" :key="i" :value="i">
                                        {{ i }}%
                                    </option>
                                </select>
                            </div>


                            <!-- Shop Logo -->
                            <div class="space-y-2">
                                <img :src="company_setting?.logo_url" v-if="company_setting?.logo_url" alt="Logo"
                                    class="w-24 h-24 object-contain">
                                <Label for="shopLogo">Logo</Label>
                                <div class="flex items-center gap-4">
                                    <Input id="shopLogo" type="file" @change="handleLogoUpload" accept="image/*"
                                        class="max-w-4xl" />
                                </div>
                            </div>
                            <!-- Website -->
                            <div class="space-y-2">
                                <Label for="website">Website</Label>
                                <Input id="website" v-model="form.website" type="text" placeholder="https://example.com"
                                    class="max-w-4xl" />
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <Label for="address">Address</Label>
                            <Textarea id="address" v-model="form.address" placeholder="123 Main Street, City, Country"
                                rows="3" class="max-w-4xl" />
                        </div>

                        <!-- Save Button -->
                        <div class="pt-4">
                            <Button @click="saveChanges" class="bg-black hover:bg-gray-800 text-white">
                                Save Changes
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
