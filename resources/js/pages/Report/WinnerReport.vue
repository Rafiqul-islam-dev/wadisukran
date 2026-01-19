<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Winner Report',
        href: '/reports/winner-report',
    },
];
</script>

<template>
    <Head title="Winner Report" />
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Winner Report</h4>
                    </div>
                    <div class="card-body">
                        <p>This is the Winner Report page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
