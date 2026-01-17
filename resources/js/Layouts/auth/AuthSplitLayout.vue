<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <!-- LEFT SIDE -->
        <div class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
            <!-- Background -->
            <div class="absolute inset-0 bg-zinc-900"></div>

            <!-- Centered Content -->
            <div class="relative z-20 flex h-full flex-col items-center justify-center text-center">
                <Link :href="route('home')" class="flex flex-col items-center justify-center">
                    <!-- Responsive Logo size -->
                    <AppLogoIcon class="size-16 sm:size-20 md:size-24 fill-current text-white" />
                    <!-- Name under logo -->
                    <div class="mt-4 text-lg sm:text-xl font-semibold">
                        WADI SHUKRAN
                    </div>
                </Link>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
