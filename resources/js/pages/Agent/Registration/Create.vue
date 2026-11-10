<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Form from './Form.vue';

defineProps<{ documentTypes: Record<string, string>; terms: string[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Registration & Agreement', href: '/agents/registrations' },
    { title: 'New Registration', href: '/agents/registrations/create' },
];
</script>

<template>
    <Head title="New Registration" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-3 md:p-6">
            <Form :document-types="documentTypes" :terms="terms" />
        </div>
    </AppLayout>
</template>
