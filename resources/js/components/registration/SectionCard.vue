<script setup lang="ts">
// Numbered, bilingual section container (pink badge + navy title tab) — mirrors the sample form design.
defineProps<{
    number: number;
    title: string;
    titleAr?: string;
    dark?: boolean; // navy striped background (Attach Documents)
}>();
</script>

<template>
    <section class="relative mt-5 rounded-xl border-[1.4px] border-[#243475] pt-6 pb-4" :class="dark ? 'bg-[#243475]' : 'bg-white'">
        <div class="absolute -top-3 left-0 flex items-stretch">
            <span
                class="z-10 -mt-1 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-[#e6007e] text-base font-bold text-white"
                >{{ number }}</span
            >
            <span
                class="-ml-3 flex h-6 items-center rounded-tr-2xl bg-[#243475] pr-5 pl-5 text-[13px] font-bold tracking-wide text-white"
                :class="dark ? 'ring-1 ring-white/30' : ''"
            >
                {{ title
                }}<template v-if="titleAr"
                    >&nbsp;/&nbsp;<span dir="rtl" class="font-semibold">{{ titleAr }}</span></template
                >
            </span>
        </div>
        <div class="px-4 pt-2 md:px-5">
            <slot />
        </div>
    </section>
</template>
