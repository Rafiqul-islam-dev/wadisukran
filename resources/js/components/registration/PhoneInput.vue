<script setup lang="ts">
import flag from '../../../images/registration/flag_uae.png';

defineProps<{ code: string; modelValue: string; placeholder?: string; invalid?: boolean; id?: string }>();
defineEmits<{ (e: 'update:code', v: string): void; (e: 'update:modelValue', v: string): void }>();
</script>

<template>
    <div
        class="flex h-[38px] overflow-hidden rounded-md border-[1.6px] bg-white focus-within:ring-2 focus-within:ring-[#e6007e]/40"
        :class="invalid ? 'border-red-500' : 'border-[#243475]'"
    >
        <div class="flex shrink-0 items-center gap-2 border-r-[1.6px] border-[#243475] px-2">
            <img :src="flag" alt="UAE" class="h-4 w-5 object-cover" />
            <input
                :value="code"
                maxlength="5"
                inputmode="tel"
                class="w-11 bg-transparent text-sm focus:outline-none"
                @input="$emit('update:code', ($event.target as HTMLInputElement).value)"
            />
        </div>
        <input
            :id="id"
            :value="modelValue"
            inputmode="tel"
            :placeholder="placeholder"
            class="min-w-0 flex-1 bg-transparent px-3 text-sm placeholder:text-[#9d9d9c] focus:outline-none"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
    </div>
</template>
