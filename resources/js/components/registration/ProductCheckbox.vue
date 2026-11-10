<script setup lang="ts">
import { Check } from 'lucide-vue-next';

defineProps<{ modelValue: boolean; label: string; labelAr: string; icon?: string; disabled?: boolean }>();
defineEmits<{ (e: 'update:modelValue', v: boolean): void }>();
</script>

<template>
    <label class="flex cursor-pointer items-center gap-2 select-none" :class="disabled ? 'cursor-default' : ''">
        <input
            type="checkbox"
            class="sr-only"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        />
        <span v-if="icon" class="relative">
            <img :src="icon" alt="" class="h-9 w-auto" />
            <span v-if="modelValue" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#e6007e] text-white">
                <Check class="h-3 w-3" :stroke-width="4" />
            </span>
        </span>
        <span v-else class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded border-[1.8px] border-[#243475] bg-white">
            <Check v-if="modelValue" class="h-4 w-4 text-[#e6007e]" :stroke-width="4" />
        </span>
        <span class="text-[13px] text-[#1d1d1b]"
            >{{ label }} <span class="text-gray-400">/</span> <span dir="rtl">{{ labelAr }}</span></span
        >
    </label>
</template>
