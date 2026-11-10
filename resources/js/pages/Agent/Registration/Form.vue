<script setup lang="ts">
import BilingualLabel from '@/components/registration/BilingualLabel.vue';
import DocumentUploadCard from '@/components/registration/DocumentUploadCard.vue';
import PhoneInput from '@/components/registration/PhoneInput.vue';
import PhotoUpload from '@/components/registration/PhotoUpload.vue';
import ProductCheckbox from '@/components/registration/ProductCheckbox.vue';
import SectionCard from '@/components/registration/SectionCard.vue';
import SignaturePad from '@/components/registration/SignaturePad.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { CalendarDays, Clock, FileSignature, MapPin } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import attar from '../../../../images/registration/attar.png';
import chocolate from '../../../../images/registration/chocolate.png';
import pen from '../../../../images/registration/pen.png';
import tasbih from '../../../../images/registration/tasbih.png';
import { inputClass, L, type RegistrationFormData } from './labels';

interface Registration extends Partial<Omit<RegistrationFormData, 'photo' | 'documents' | 'person_signature' | 'company_signature'>> {
    id: number;
    reference_no: string;
    status: string;
    has_photo?: boolean;
    document_types_uploaded?: string[];
    reject_reason?: string | null;
}

const props = defineProps<{
    registration?: Registration;
    documentTypes: Record<string, string>;
    terms: string[];
}>();

const editing = computed(() => !!props.registration);
const r = props.registration;
const page = usePage();
const logo = computed(() => (page.props as any).company_setting?.logo_url as string | undefined);

// local calendar date (toISOString() would give the UTC date, i.e. "yesterday" in UAE after midnight)
function localToday(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const docs: Record<string, File | null> = {};
Object.keys(props.documentTypes).forEach((k) => (docs[k] = null));

const form = useForm<RegistrationFormData>({
    shop_name: r?.shop_name ?? '',
    owner_name: r?.owner_name ?? '',
    passport_no: r?.passport_no ?? '',
    uid_no: r?.uid_no ?? '',
    eid_no: r?.eid_no ?? '',
    trade_license_no: r?.trade_license_no ?? '',
    email: r?.email ?? '',
    mobile_country_code: r?.mobile_country_code ?? '+971',
    mobile: r?.mobile ?? '',
    whatsapp_country_code: r?.whatsapp_country_code ?? '+971',
    whatsapp: r?.whatsapp ?? '',
    address: r?.address ?? '',
    location_map: r?.location_map ?? '',
    photo: null,
    products: { chocolate: false, attar: false, tasbih: false, pen: false, others: false, ...(r?.products ?? {}) },
    products_other_text: r?.products_other_text ?? '',
    dev_tab: !!r?.dev_tab,
    dev_pos_machine: !!r?.dev_pos_machine,
    dev_sim_card: !!r?.dev_sim_card,
    dev_active_data: !!r?.dev_active_data,
    sim_card_provided: !!r?.sim_card_provided,
    delivery_date: (r?.delivery_date ?? '').toString().slice(0, 10),
    delivery_time: r?.delivery_time ?? '',
    person_signature: '',
    company_signature: '',
    signed_date: (r?.signed_date ?? localToday()).toString().slice(0, 10),
    signed_place: r?.signed_place ?? '',
    documents: docs,
});

const fileUrl = (kind: string) => (r ? route('agents.registrations.file', { registration: r.id, kind }) : null);
const err = (k: string) => (form.errors as Record<string, string>)[k];

// Inertia would send booleans as "true"/"false"; the server expects 1/0.
const num = (v: boolean) => (v ? 1 : 0);
form.transform(
    (d) =>
        ({
            ...d,
            products: Object.fromEntries(Object.entries(d.products).map(([k, v]) => [k, num(v)])),
            dev_tab: num(d.dev_tab),
            dev_pos_machine: num(d.dev_pos_machine),
            dev_sim_card: num(d.dev_sim_card),
            dev_active_data: num(d.dev_active_data),
            sim_card_provided: num(d.sim_card_provided),
            // do not send stale text when "Others" is unchecked
            products_other_text: d.products.others ? d.products_other_text : '',
        }) as any,
);

function focusFirstError() {
    nextTick(() => {
        const el = document.querySelector<HTMLElement>('form .\\!border-red-500, form .border-red-500, form p.text-red-600');
        el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (el instanceof HTMLInputElement) el.focus({ preventScroll: true });
    });
}

function submit() {
    if (form.processing) return; // double-submit guard
    const url = editing.value ? route('agents.registrations.update', r!.id) : route('agents.registrations.store');
    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
        onError: () => {
            toast.error('Please fix the highlighted fields.');
            focusFirstError();
        },
        // success toast is shown once by the Show page (it reads the flash message)
    });
}

// Non-blocking duplicate hint (same trade licence / email on another registration).
const duplicates = ref<{ id: number; reference_no: string; shop_name: string; status: string; same: string[] }[]>([]);
async function checkDuplicates() {
    if (!form.trade_license_no.trim() && !form.email.trim()) {
        duplicates.value = [];
        return;
    }
    try {
        const { data } = await axios.get(route('agents.registrations.duplicates'), {
            params: { trade_license_no: form.trade_license_no, email: form.email, exclude: r?.id },
        });
        duplicates.value = data.matches ?? [];
    } catch {
        duplicates.value = []; // a hint only; never block the form
    }
}

const cancel = () => router.get(route('agents.registrations.index'));
</script>

<template>
    <form class="mx-auto max-w-[1000px] rounded-xl bg-white p-3 shadow-lg md:p-5" novalidate @submit.prevent="submit">
        <!-- Header: logo + title band -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <img v-if="logo" :src="logo" alt="Wadi Shukran" class="h-14 w-auto md:h-16" />
                <span class="text-2xl font-extrabold tracking-tight text-[#243475] md:text-4xl">WADI SHUKRAN</span>
            </div>
            <div class="hidden min-w-[300px] flex-1 items-stretch md:flex md:max-w-[560px]">
                <span class="-mr-6 w-16 rounded-l-full bg-[#e6007e]" />
                <div class="flex flex-1 flex-col items-center justify-center rounded-r bg-[#243475] px-4 py-2 text-center text-white">
                    <span dir="rtl" class="text-xl leading-tight font-bold">{{ L.title.ar }}</span>
                    <span class="text-sm font-bold">{{ L.title.en }}</span>
                </div>
            </div>
        </div>
        <div class="mt-3 rounded bg-[#243475] px-3 py-2 text-center text-xs font-bold text-white md:hidden">{{ L.title.en }}</div>

        <p v-if="r?.status === 'rejected' && r.reject_reason" class="mt-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">
            <b>Rejected:</b> {{ r.reject_reason }} - fix the issues and save to re-submit for approval.
        </p>

        <!-- 1 Business information -->
        <SectionCard :number="1" :title="L.sections.business.en">
            <div class="grid gap-4 md:grid-cols-[1fr_200px]">
                <div class="@container grid gap-3">
                    <div>
                        <BilingualLabel :en="L.shop_name.en" :ar="L.shop_name.ar" required for="shop_name" />
                        <input
                            id="shop_name"
                            v-model="form.shop_name"
                            :class="[inputClass, err('shop_name') && '!border-red-500']"
                            placeholder="Enter business shop name"
                        />
                        <p v-if="err('shop_name')" class="mt-1 text-xs text-red-600">{{ err('shop_name') }}</p>
                    </div>
                    <div>
                        <BilingualLabel :en="L.owner_name.en" :ar="L.owner_name.ar" required for="owner_name" />
                        <input
                            id="owner_name"
                            v-model="form.owner_name"
                            :class="[inputClass, err('owner_name') && '!border-red-500']"
                            placeholder="Enter authorized person / owner name"
                        />
                        <p v-if="err('owner_name')" class="mt-1 text-xs text-red-600">{{ err('owner_name') }}</p>
                    </div>
                    <div class="grid gap-3 @md:grid-cols-2 @xl:grid-cols-3">
                        <div>
                            <BilingualLabel :en="L.passport_no.en" :ar="L.passport_no.ar" required for="passport_no" />
                            <input
                                id="passport_no"
                                v-model="form.passport_no"
                                :class="[inputClass, err('passport_no') && '!border-red-500']"
                                placeholder="Passport number"
                            />
                            <p v-if="err('passport_no')" class="mt-1 text-xs text-red-600">{{ err('passport_no') }}</p>
                        </div>
                        <div>
                            <BilingualLabel :en="L.uid_no.en" :ar="L.uid_no.ar" required for="uid_no" />
                            <input
                                id="uid_no"
                                v-model="form.uid_no"
                                :class="[inputClass, err('uid_no') && '!border-red-500']"
                                placeholder="UID number"
                            />
                            <p v-if="err('uid_no')" class="mt-1 text-xs text-red-600">{{ err('uid_no') }}</p>
                        </div>
                        <div>
                            <BilingualLabel :en="L.eid_no.en" :ar="L.eid_no.ar" required for="eid_no" />
                            <input
                                id="eid_no"
                                v-model="form.eid_no"
                                :class="[inputClass, err('eid_no') && '!border-red-500']"
                                placeholder="784-YYYY-NNNNNNN-N"
                            />
                            <p v-if="err('eid_no')" class="mt-1 text-xs text-red-600">{{ err('eid_no') }}</p>
                        </div>
                    </div>
                    <div class="grid gap-3 @md:grid-cols-2">
                        <div>
                            <BilingualLabel :en="L.trade_license_no.en" :ar="L.trade_license_no.ar" required for="trade_license_no" />
                            <input
                                id="trade_license_no"
                                v-model="form.trade_license_no"
                                @blur="checkDuplicates"
                                :class="[inputClass, err('trade_license_no') && '!border-red-500']"
                                placeholder="Trade License Number"
                            />
                            <p v-if="err('trade_license_no')" class="mt-1 text-xs text-red-600">{{ err('trade_license_no') }}</p>
                        </div>
                        <div>
                            <BilingualLabel :en="L.email.en" :ar="L.email.ar" required for="email" />
                            <input
                                id="email"
                                v-model="form.email"
                                @blur="checkDuplicates"
                                type="email"
                                :class="[inputClass, err('email') && '!border-red-500']"
                                placeholder="Email Address"
                            />
                            <p v-if="err('email')" class="mt-1 text-xs text-red-600">{{ err('email') }}</p>
                        </div>
                    </div>
                    <div v-if="duplicates.length" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900" role="status">
                        <b>Possible duplicate:</b> another registration already uses the same
                        <template v-for="(d, i) in duplicates" :key="d.id"
                            >{{ i ? '; ' : ' ' }}{{ d.same.join(' and ') }} -
                            <Link :href="route('agents.registrations.show', d.id)" class="font-semibold underline" target="_blank">{{
                                d.reference_no
                            }}</Link>
                            ({{ d.shop_name }}, {{ d.status }})</template
                        >. You can still submit if this is intended.
                    </div>
                    <div class="grid gap-3 @md:grid-cols-2">
                        <div>
                            <BilingualLabel :en="L.mobile.en" :ar="L.mobile.ar" required for="mobile" />
                            <PhoneInput
                                id="mobile"
                                v-model:code="form.mobile_country_code"
                                v-model="form.mobile"
                                placeholder="Mobile Number"
                                :invalid="!!err('mobile')"
                            />
                            <p v-if="err('mobile') || err('mobile_country_code')" class="mt-1 text-xs text-red-600">
                                {{ err('mobile') || err('mobile_country_code') }}
                            </p>
                        </div>
                        <div>
                            <BilingualLabel :en="L.whatsapp.en" :ar="L.whatsapp.ar" required for="whatsapp" />
                            <PhoneInput
                                id="whatsapp"
                                v-model:code="form.whatsapp_country_code"
                                v-model="form.whatsapp"
                                placeholder="WhatsApp Number"
                                :invalid="!!err('whatsapp')"
                            />
                            <p v-if="err('whatsapp') || err('whatsapp_country_code')" class="mt-1 text-xs text-red-600">
                                {{ err('whatsapp') || err('whatsapp_country_code') }}
                            </p>
                        </div>
                    </div>
                </div>
                <PhotoUpload
                    v-model="form.photo"
                    :existing-url="r?.has_photo ? fileUrl('photo') : null"
                    :label="L.photo.en"
                    :label-ar="L.photo.ar"
                    :error="err('photo')"
                />
            </div>

            <div class="mt-3 grid gap-3">
                <div>
                    <BilingualLabel :en="L.address.en" :ar="L.address.ar" required for="address" />
                    <input
                        id="address"
                        v-model="form.address"
                        :class="[inputClass, err('address') && '!border-red-500']"
                        placeholder="Full business address"
                    />
                    <p v-if="err('address')" class="mt-1 text-xs text-red-600">{{ err('address') }}</p>
                </div>
                <div>
                    <BilingualLabel :en="L.location_map.en" :ar="L.location_map.ar" required for="location_map" />
                    <div class="relative">
                        <input
                            id="location_map"
                            v-model="form.location_map"
                            type="url"
                            :class="[inputClass, 'pr-9', err('location_map') && '!border-red-500']"
                            placeholder="Paste Google maps link or enter location"
                        />
                        <MapPin class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#243475]" />
                    </div>
                    <p v-if="err('location_map')" class="mt-1 text-xs text-red-600">{{ err('location_map') }}</p>
                </div>
            </div>
        </SectionCard>

        <!-- 2 Products -->
        <SectionCard :number="2" :title="L.sections.products.en" :title-ar="L.sections.products.ar">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-1">
                <ProductCheckbox
                    v-model="form.products.chocolate"
                    :icon="chocolate"
                    :label="L.products.chocolate.en"
                    :label-ar="L.products.chocolate.ar"
                />
                <ProductCheckbox v-model="form.products.attar" :icon="attar" :label="L.products.attar.en" :label-ar="L.products.attar.ar" />
                <ProductCheckbox v-model="form.products.tasbih" :icon="tasbih" :label="L.products.tasbih.en" :label-ar="L.products.tasbih.ar" />
                <ProductCheckbox v-model="form.products.pen" :icon="pen" :label="L.products.pen.en" :label-ar="L.products.pen.ar" />
                <ProductCheckbox v-model="form.products.others" :label="L.products.others.en" :label-ar="L.products.others.ar" />
                <div class="min-w-[200px] flex-1">
                    <input
                        v-model="form.products_other_text"
                        :disabled="!form.products.others"
                        :class="[inputClass, err('products_other_text') && '!border-red-500']"
                        placeholder="https://onlinesouquae.com/"
                    />
                    <p v-if="err('products_other_text')" class="mt-1 text-xs text-red-600">{{ err('products_other_text') }}</p>
                </div>
            </div>
        </SectionCard>

        <!-- 3 Devices -->
        <SectionCard :number="3" :title="L.sections.devices.en" :title-ar="L.sections.devices.ar">
            <div class="-mx-4 bg-[#bcc1cc] px-4 py-3 md:-mx-5 md:px-5">
                <div class="flex flex-wrap gap-x-8 gap-y-2">
                    <ProductCheckbox v-model="form.dev_tab" :label="L.devices.dev_tab.en" :label-ar="L.devices.dev_tab.ar" />
                    <ProductCheckbox v-model="form.dev_pos_machine" :label="L.devices.dev_pos_machine.en" :label-ar="L.devices.dev_pos_machine.ar" />
                    <ProductCheckbox v-model="form.dev_sim_card" :label="L.devices.dev_sim_card.en" :label-ar="L.devices.dev_sim_card.ar" />
                    <ProductCheckbox v-model="form.dev_active_data" :label="L.devices.dev_active_data.en" :label-ar="L.devices.dev_active_data.ar" />
                </div>
            </div>
            <div class="-mx-4 -mb-4 grid items-center gap-3 rounded-b-xl bg-[#fbe3ef] px-4 py-3 md:-mx-5 md:grid-cols-[auto_1fr_1fr] md:px-5">
                <ProductCheckbox v-model="form.sim_card_provided" :label="L.sim_card_provided.en" :label-ar="L.sim_card_provided.ar" />
                <div>
                    <BilingualLabel :en="L.delivery_date.en" :ar="L.delivery_date.ar" required for="delivery_date" />
                    <div class="relative">
                        <CalendarDays class="pointer-events-none absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-[#1d1d1b]" />
                        <input
                            id="delivery_date"
                            v-model="form.delivery_date"
                            type="date"
                            :class="[inputClass, 'pl-8', err('delivery_date') && '!border-red-500']"
                        />
                    </div>
                    <p v-if="err('delivery_date')" class="mt-1 text-xs text-red-600">{{ err('delivery_date') }}</p>
                </div>
                <div>
                    <BilingualLabel :en="L.delivery_time.en" :ar="L.delivery_time.ar" required for="delivery_time" />
                    <div class="relative">
                        <Clock class="pointer-events-none absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-[#1d1d1b]" />
                        <input
                            id="delivery_time"
                            v-model="form.delivery_time"
                            type="time"
                            :class="[inputClass, 'pl-8', err('delivery_time') && '!border-red-500']"
                        />
                    </div>
                    <p v-if="err('delivery_time')" class="mt-1 text-xs text-red-600">{{ err('delivery_time') }}</p>
                </div>
            </div>
        </SectionCard>

        <!-- 4 Terms -->
        <SectionCard :number="4" :title="L.sections.terms.en" :title-ar="L.sections.terms.ar">
            <ol class="space-y-3 pt-1">
                <li v-for="(t, i) in terms" :key="i" class="flex gap-3 text-[14px] leading-snug text-[#1d1d1b]">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e6007e] text-xs font-bold text-white">{{
                        i + 1
                    }}</span>
                    <span class="text-justify">{{ t }}</span>
                </li>
            </ol>
        </SectionCard>

        <!-- 5 Documents -->
        <SectionCard :number="5" :title="L.sections.documents.en" :title-ar="L.sections.documents.ar" dark>
            <div class="grid grid-cols-2 gap-3 pt-1 sm:grid-cols-3 lg:grid-cols-6">
                <DocumentUploadCard
                    v-for="(label, key) in documentTypes"
                    :key="key"
                    v-model="form.documents[key]"
                    :label="L.documents[key]?.en ?? label"
                    :label-ar="L.documents[key]?.ar ?? ''"
                    :uploaded="r?.document_types_uploaded?.includes(key)"
                    :view-url="fileUrl(key) ?? undefined"
                    :error="err(`documents.${key}`)"
                />
            </div>
        </SectionCard>

        <!-- 6 Signatures -->
        <SectionCard :number="6" :title="L.sections.signatures.en" :title-ar="L.sections.signatures.ar">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <BilingualLabel :en="L.person_signature.en" :ar="L.person_signature.ar" required />
                    <SignaturePad
                        v-model="form.person_signature"
                        :existing-url="r ? fileUrl('person_signature') : null"
                        :error="err('person_signature')"
                    />
                </div>
                <div>
                    <BilingualLabel :en="L.company_signature.en" :ar="L.company_signature.ar" required />
                    <SignaturePad
                        v-model="form.company_signature"
                        :existing-url="r ? fileUrl('company_signature') : null"
                        :error="err('company_signature')"
                    />
                </div>
            </div>
            <div class="mt-4 grid items-end gap-3 md:grid-cols-[200px_1fr_auto]">
                <div>
                    <BilingualLabel :en="L.signed_date.en" :ar="L.signed_date.ar" required for="signed_date" />
                    <input id="signed_date" v-model="form.signed_date" type="date" :class="[inputClass, err('signed_date') && '!border-red-500']" />
                    <p v-if="err('signed_date')" class="mt-1 text-xs text-red-600">{{ err('signed_date') }}</p>
                </div>
                <div>
                    <BilingualLabel :en="L.signed_place.en" :ar="L.signed_place.ar" required for="signed_place" />
                    <input
                        id="signed_place"
                        v-model="form.signed_place"
                        :class="[inputClass, err('signed_place') && '!border-red-500']"
                        placeholder="Enter Place"
                    />
                    <p v-if="err('signed_place')" class="mt-1 text-xs text-red-600">{{ err('signed_place') }}</p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="h-[38px] rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex h-[38px] items-center gap-2 rounded-md bg-[#e6007e] px-5 text-sm font-semibold text-white hover:bg-[#c9006f] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <FileSignature class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : editing ? 'Save & Submit for Approval' : 'Submit for Approval' }}
                    </button>
                </div>
            </div>
        </SectionCard>
    </form>
</template>
