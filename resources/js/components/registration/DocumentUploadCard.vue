<script setup lang="ts">
import { FileText, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ modelValue: File | null; label: string; labelAr: string; uploaded?: boolean; viewUrl?: string; error?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: File | null): void }>();
const input = ref<HTMLInputElement | null>(null);

const MAX_BYTES = 8 * 1024 * 1024; // keep in sync with the server rule (document max 8 MB)
const localError = ref('');

const pick = (e: Event) => {
    const el = e.target as HTMLInputElement;
    const f = el.files?.[0] ?? null;
    localError.value = '';
    if (f && f.size > MAX_BYTES) {
        localError.value = 'File must not be larger than 8 MB.';
        el.value = '';
        return;
    }
    emit('update:modelValue', f);
    el.value = ''; // allow picking the same file again
};
</script>

<template>
    <div class="flex flex-col items-center rounded-lg bg-white p-2 text-center shadow" :class="error ? 'ring-2 ring-red-500' : ''">
        <FileText class="h-6 w-6 text-[#1d1d1b]" />
        <p class="mt-1 text-[12px] leading-tight font-medium text-[#1d1d1b]">{{ label }}</p>
        <p dir="rtl" class="text-[11px] leading-tight text-gray-600">{{ labelAr }}</p>

        <p v-if="props.modelValue" class="mt-1 w-full truncate text-[11px] font-medium text-emerald-700" :title="props.modelValue.name">
            {{ props.modelValue.name }}
        </p>
        <a
            v-else-if="uploaded && viewUrl"
            :href="viewUrl"
            target="_blank"
            rel="noopener"
            class="mt-1 text-[11px] font-medium text-emerald-700 underline"
            >Uploaded - view</a
        >

        <button
            type="button"
            class="mt-2 flex w-full items-center justify-center gap-1 rounded-md bg-gradient-to-b from-[#243475] to-[#1a2556] px-2 py-1 text-[12px] font-semibold text-white"
            @click="input?.click()"
        >
            <Upload class="h-3.5 w-3.5 text-[#e6007e]" /> {{ props.modelValue || uploaded ? 'Replace' : 'Upload' }}
        </button>
        <input ref="input" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="pick" />
        <button v-if="props.modelValue" type="button" class="mt-1 text-[11px] text-red-600 underline" @click="emit('update:modelValue', null)">
            Remove
        </button>
        <p v-if="localError || error" class="mt-1 text-[11px] text-red-600">{{ localError || error }}</p>
    </div>
</template>
