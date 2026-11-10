<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Form from './Form.vue';

const props = defineProps<{ registration: any; documentTypes: Record<string, string>; terms: string[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Registration & Agreement', href: '/agents/registrations' },
    { title: `Edit ${props.registration.reference_no}`, href: `/agents/registrations/${props.registration.id}/edit` },
];
</script>

<template>
    <Head :title="`Edit ${registration.reference_no}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-3 md:p-6">
            <Form :key="registration.id" :registration="registration" :document-types="documentTypes" :terms="terms" />
        </div>
    </AppLayout>
</template>
