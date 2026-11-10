<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import camera from '../../../images/registration/camera.png';
import clip from '../../../images/registration/paperclip_white.png';

const props = defineProps<{ modelValue: File | null; existingUrl?: string | null; label: string; labelAr: string; error?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: File | null): void }>();

const input = ref<HTMLInputElement | null>(null);
const preview = ref<string | null>(null);

watch(
    () => props.modelValue,
    (file) => {
        if (preview.value) URL.revokeObjectURL(preview.value);
        preview.value = file ? URL.createObjectURL(file) : null;
    },
    { immediate: true },
);
onBeforeUnmount(() => preview.value && URL.revokeObjectURL(preview.value));

const MAX_BYTES = 5 * 1024 * 1024; // keep in sync with the server rule (photo max 5 MB)
const localError = ref('');

const pick = (e: Event) => {
    const el = e.target as HTMLInputElement;
    const f = el.files?.[0] ?? null;
    localError.value = '';
    if (f && f.size > MAX_BYTES) {
        localError.value = 'Photo must not be larger than 5 MB.';
        el.value = '';
        return;
    }
    emit('update:modelValue', f);
    el.value = ''; // allow picking the same file again
};

const remove = () => emit('update:modelValue', null);
</script>

<template>
    <div class="flex flex-col items-center">
        <div
            class="relative flex aspect-[162/230] w-full max-w-[200px] flex-col items-center justify-center overflow-hidden rounded-md border-[1.6px] bg-[#e9e9e9]"
            :class="error ? 'border-red-500' : 'border-[#243475]'"
        >
            <img
                v-if="preview || existingUrl"
                :src="(preview || existingUrl)!"
                alt="Authorized person"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <img v-else :src="camera" alt="" class="w-2/3 opacity-90" />
            <button
                type="button"
                class="absolute bottom-3 z-10 flex items-center gap-1 rounded bg-[#243475] px-4 py-1 text-sm font-semibold text-white shadow"
                @click="input?.click()"
            >
                <img :src="clip" alt="" class="h-4 w-4" /> {{ preview ? 'Replace' : 'Upload' }}
            </button>
            <button
                v-if="preview"
                type="button"
                class="absolute top-2 right-2 z-10 rounded bg-white/90 px-2 py-0.5 text-[11px] font-medium text-red-600 shadow hover:bg-white"
                @click="remove"
            >
                Remove
            </button>
            <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick" />
        </div>
        <p class="mt-2 text-center text-[13px] leading-tight text-[#1d1d1b]">
            <span dir="rtl" class="block font-medium">{{ labelAr }}</span>
            {{ label }}
        </p>
        <p v-if="localError || error" class="mt-1 text-xs text-red-600">{{ localError || error }}</p>
    </div>
</template>
