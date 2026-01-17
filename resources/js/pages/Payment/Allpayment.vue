<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, nextTick, watch, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

</script>

<template>
    <AppLayout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">All Payment</h4>
                    </div>
                    <div class="card-body">
                        <p>This is the All Payment page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>