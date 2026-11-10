<script setup lang="ts">
// Minimal dependency-free signature pad (pointer events => mouse, touch and pen).
// Emits a PNG data-URL ('' when empty). The server re-encodes and validates it.
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ modelValue: string; existingUrl?: string | null; error?: string; disabled?: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>();

const canvas = ref<HTMLCanvasElement | null>(null);
const wrap = ref<HTMLDivElement | null>(null);
let drawing = false;
let dirty = false;
const hasInk = ref(false);

const ctx = () => canvas.value!.getContext('2d')!;

function resize() {
    const c = canvas.value;
    const w = wrap.value;
    if (!c || !w) return;
    // keep drawn content when the container resizes
    const snapshot = dirty ? c.toDataURL() : null;
    const ratio = window.devicePixelRatio || 1;
    c.width = Math.max(10, Math.floor(w.clientWidth * ratio));
    c.height = Math.max(10, Math.floor(w.clientHeight * ratio));
    const g = ctx();
    g.lineWidth = 2.2 * ratio;
    g.lineCap = 'round';
    g.lineJoin = 'round';
    g.strokeStyle = '#111c5c';
    if (snapshot) {
        const img = new Image();
        img.onload = () => ctx().drawImage(img, 0, 0, c.width, c.height);
        img.src = snapshot;
    }
}

const pos = (e: PointerEvent) => {
    const r = canvas.value!.getBoundingClientRect();
    const ratio = canvas.value!.width / r.width;
    return { x: (e.clientX - r.left) * ratio, y: (e.clientY - r.top) * ratio };
};

function down(e: PointerEvent) {
    if (props.disabled) return;
    drawing = true;
    canvas.value!.setPointerCapture(e.pointerId);
    const { x, y } = pos(e);
    const g = ctx();
    g.beginPath();
    g.moveTo(x, y);
    g.lineTo(x + 0.01, y + 0.01); // a tap leaves a dot
    g.stroke();
    dirty = true;
}
function move(e: PointerEvent) {
    if (!drawing) return;
    const { x, y } = pos(e);
    const g = ctx();
    g.lineTo(x, y);
    g.stroke();
}
function up() {
    if (!drawing) return;
    drawing = false;
    hasInk.value = true;
    emit('update:modelValue', exportPng());
}

// Export on a white background so the server-side "blank" check and the PDF look right.
function exportPng(): string {
    const c = canvas.value!;
    const out = document.createElement('canvas');
    out.width = c.width;
    out.height = c.height;
    const g = out.getContext('2d')!;
    g.fillStyle = '#fff';
    g.fillRect(0, 0, out.width, out.height);
    g.drawImage(c, 0, 0);
    return out.toDataURL('image/png');
}

function clear() {
    const c = canvas.value!;
    ctx().clearRect(0, 0, c.width, c.height);
    dirty = false;
    hasInk.value = false;
    emit('update:modelValue', '');
}

let ro: ResizeObserver | null = null;
onMounted(async () => {
    await nextTick();
    resize();
    ro = new ResizeObserver(resize);
    if (wrap.value) ro.observe(wrap.value);
});
onBeforeUnmount(() => ro?.disconnect());
</script>

<template>
    <div>
        <div
            ref="wrap"
            class="relative h-[72px] rounded-md border-[1.6px] bg-white md:h-[84px]"
            :class="error ? 'border-red-500' : 'border-[#243475]'"
        >
            <img
                v-if="existingUrl && !hasInk"
                :src="existingUrl"
                alt="Saved signature"
                class="pointer-events-none absolute inset-0 h-full w-full object-contain p-1 opacity-70"
            />
            <span v-if="!hasInk && !existingUrl" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-[#9d9d9c]"
                >Sign here (use finger or mouse)</span
            >
            <canvas
                ref="canvas"
                class="absolute inset-0 h-full w-full touch-none"
                @pointerdown.prevent="down"
                @pointermove.prevent="move"
                @pointerup="up"
                @pointercancel="up"
                @pointerleave="up"
            />
            <button
                v-if="hasInk && !disabled"
                type="button"
                class="absolute top-1 right-1 rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700 hover:bg-gray-200"
                @click="clear"
            >
                Clear
            </button>
        </div>
        <p v-if="existingUrl && !hasInk" class="mt-1 text-[11px] text-gray-500">Saved signature shown. Sign again only to replace it.</p>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
