<script setup lang="ts">
defineProps<{ en: string; ar: string; required?: boolean; for?: string }>();
</script>

<template>
    <label :for="$props.for" class="mb-1 block text-[13px] leading-tight text-[#1d1d1b]">
        {{ en }} <span class="text-gray-400">/</span> <span dir="rtl" class="font-medium">{{ ar }}</span
        ><span v-if="required" class="text-[#e6007e]">*</span>
    </label>
</template>
