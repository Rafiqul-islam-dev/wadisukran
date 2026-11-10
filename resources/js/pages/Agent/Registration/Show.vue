<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { can } from '@/helpers/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Download, FileText, Pencil, RefreshCw, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { L, statusStyles } from './labels';

const props = defineProps<{ registration: any; documentTypes: Record<string, string>; terms: string[] }>();
// Reactive: Inertia reuses this component when the same page is re-rendered (reload / redirect back),
// so every derived value must follow props.registration.
const r = computed(() => props.registration);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Registration & Agreement', href: '/agents/registrations' },
    { title: r.value.reference_no, href: `/agents/registrations/${r.value.id}` },
]);

onMounted(() => {
    const flash = (usePage().props as any).flash?.success;
    if (flash) toast.success(flash);
});

// ---- approval workflow (server re-checks state, permission and PDF generation) ----
const busy = ref(false);
const showApprove = ref(false);
const showReject = ref(false);
const reason = ref('');
const reasonError = ref('');

// Approve/reject redirect back to this same page, so the component is reused and onMounted does not
// run again: read the flash message from the response instead.
function notify(page: { props: Record<string, any> }) {
    const f = page.props.flash;
    if (f?.error) toast.error(f.error);
    else if (f?.success) toast.success(f.success);
}

function approve() {
    if (busy.value) return;
    busy.value = true;
    router.post(
        route('agents.registrations.approve', r.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: notify,
            onError: () => toast.error('Approval failed. Please try again.'),
            onFinish: () => {
                busy.value = false;
                showApprove.value = false;
            },
        },
    );
}

function reject() {
    if (busy.value) return;
    reasonError.value = '';
    busy.value = true;
    router.post(
        route('agents.registrations.reject', r.value.id),
        { reason: reason.value },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                notify(page);
                showReject.value = false;
                reason.value = '';
            },
            onError: (errors) => (reasonError.value = errors.reason ?? 'Could not reject.'),
            onFinish: () => (busy.value = false),
        },
    );
}

function regenerate() {
    if (busy.value) return;
    busy.value = true;
    router.post(
        route('agents.registrations.regenerate-pdf', r.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: notify,
            onFinish: () => (busy.value = false),
        },
    );
}

const ACTIONS: Record<string, string> = {
    created: 'Submitted',
    updated: 'Edited and re-submitted',
    approved: 'Approved',
    rejected: 'Rejected',
    pdf_regenerated: 'Regenerated the agreement PDF',
    pdf_viewed: 'Viewed the agreement PDF',
    pdf_downloaded: 'Downloaded the agreement PDF',
    document_viewed: 'Opened a document',
    deleted: 'Deleted',
};
const actionLabel = (e: { action: string; meta?: { kind?: string } | null }) =>
    (ACTIONS[e.action] ?? e.action) + (e.action === 'document_viewed' && e.meta?.kind ? ` (${L.documents[e.meta.kind]?.en ?? e.meta.kind})` : '');

const pdfUrl = computed(() => route('agents.registrations.pdf', { registration: r.value.id }));
const pdfDownloadUrl = computed(() => route('agents.registrations.pdf', { registration: r.value.id, download: 1 }));
const pdfVersion = computed(() => r.value.pdf_generated_at ?? '');

const file = (kind: string) => route('agents.registrations.file', { registration: r.value.id, kind });
const uploaded = computed<string[]>(() => r.value.document_types_uploaded ?? []);
const fmt = (d?: string | null) => (d ? new Date(d).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }) : '-');
// date-only values (YYYY-MM-DD) are formatted from their parts to avoid timezone day shifts
const day = (d?: string | null) => {
    if (!d) return '-';
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d);
    const date = m ? new Date(+m[1], +m[2] - 1, +m[3]) : new Date(d);
    return date.toLocaleDateString('en-GB', { dateStyle: 'medium' });
};

const rows = computed<[string, string][]>(() => [
    ['Business Shop Name', r.value.shop_name],
    ['Authorized Person / Owner', r.value.owner_name],
    ['Passport Number', r.value.passport_no],
    ['UID Number', r.value.uid_no],
    ['E-ID Number', r.value.eid_no],
    ['Trade License Number', r.value.trade_license_no],
    ['Email', r.value.email],
    ['Mobile', `${r.value.mobile_country_code} ${r.value.mobile}`],
    ['WhatsApp', `${r.value.whatsapp_country_code} ${r.value.whatsapp}`],
    ['Business Address', r.value.address],
]);
const selectedProducts = computed(() =>
    Object.entries(L.products)
        .filter(([k]) => r.value.products?.[k])
        .map(([k, v]) => (k === 'others' && r.value.products_other_text ? `${v.en}: ${r.value.products_other_text}` : v.en)),
);
const devices = computed(() =>
    Object.entries(L.devices)
        .filter(([k]) => r.value[k])
        .map(([, v]) => v.en),
);
const isLink = (u: string) => /^https?:\/\//i.test(u);
</script>

<template>
    <Head :title="r.reference_no" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-3 md:p-6">
            <div class="mx-auto max-w-[950px] space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Link :href="route('agents.registrations.index')" class="flex items-center text-sm text-gray-600 hover:text-gray-900"
                        ><ArrowLeft class="mr-1 h-4 w-4" /> Back to list</Link
                    >
                    <Link v-if="can('update sales point registration') && r.status !== 'approved'" :href="route('agents.registrations.edit', r.id)">
                        <Button class="bg-amber-500 hover:bg-amber-600"><Pencil class="mr-2 h-4 w-4" /> Edit</Button>
                    </Link>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h1 class="text-xl font-bold text-[#243475]">{{ r.reference_no }} - {{ r.shop_name }}</h1>
                        <span class="rounded-full px-3 py-1 text-sm font-semibold capitalize" :class="statusStyles[r.status]">{{ r.status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Created {{ fmt(r.created_at) }}<span v-if="r.creator"> by {{ r.creator.name }}</span>
                    </p>
                    <p v-if="r.status === 'approved'" class="mt-1 text-sm text-emerald-700">
                        Approved {{ fmt(r.approved_at) }}<span v-if="r.approver"> by {{ r.approver.name }}</span>
                    </p>
                    <p v-if="r.status === 'rejected'" class="mt-2 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        <b>Rejected</b> {{ fmt(r.rejected_at) }}<span v-if="r.rejecter"> by {{ r.rejecter.name }}</span
                        >: {{ r.reject_reason }}
                    </p>
                </div>

                <!-- Approval panel -->
                <div
                    v-if="r.status === 'pending' && can('approve sales point registration')"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4"
                >
                    <p class="text-sm text-amber-900">
                        <b>Waiting for approval.</b> Approving generates the signed agreement PDF and locks this registration.
                    </p>
                    <div class="flex gap-2">
                        <Button variant="outline" class="border-red-300 text-red-700 hover:bg-red-50" :disabled="busy" @click="showReject = true"
                            ><XCircle class="mr-2 h-4 w-4" /> Reject</Button
                        >
                        <Button class="bg-emerald-600 hover:bg-emerald-700" :disabled="busy" @click="showApprove = true"
                            ><CheckCircle2 class="mr-2 h-4 w-4" /> Approve</Button
                        >
                    </div>
                </div>

                <!-- Agreement PDF -->
                <div v-if="r.status === 'approved' && r.pdf_generated_at" class="rounded-xl bg-white p-5 shadow">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-bold tracking-wide text-[#243475] uppercase">Agreement PDF</h2>
                        <div class="flex gap-2">
                            <a v-if="can('download sales point agreement')" :href="pdfDownloadUrl">
                                <Button class="bg-[#e6007e] hover:bg-[#c9006f]"><Download class="mr-2 h-4 w-4" /> Download PDF</Button>
                            </a>
                            <Button v-if="can('approve sales point registration')" variant="outline" :disabled="busy" @click="regenerate">
                                <RefreshCw class="mr-2 h-4 w-4" :class="busy ? 'animate-spin' : ''" /> Regenerate
                            </Button>
                        </div>
                    </div>
                    <iframe
                        v-if="can('download sales point agreement')"
                        :key="pdfVersion"
                        :src="pdfUrl"
                        title="Agreement PDF"
                        class="h-[780px] w-full rounded border"
                    />
                    <p v-else class="text-sm text-gray-500">You do not have permission to view the agreement PDF.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-[1fr_200px]">
                    <div class="rounded-xl bg-white p-5 shadow">
                        <h2 class="mb-3 text-sm font-bold tracking-wide text-[#243475] uppercase">1. Business Information</h2>
                        <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
                            <div v-for="[k, v] in rows" :key="k" :class="k === 'Business Address' ? 'sm:col-span-2' : ''">
                                <dt class="text-xs text-gray-500">{{ k }}</dt>
                                <dd class="text-sm font-medium break-words text-gray-900">{{ v }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500">Location Map</dt>
                                <dd class="text-sm font-medium break-all">
                                    <a
                                        v-if="isLink(r.location_map)"
                                        :href="r.location_map"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-blue-600 underline"
                                        >{{ r.location_map }}</a
                                    >
                                    <span v-else>{{ r.location_map }}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-xl bg-white p-4 text-center shadow">
                        <img
                            v-if="r.has_photo"
                            :src="file('photo')"
                            alt="Authorized person"
                            class="mx-auto aspect-[162/230] w-full max-w-[180px] rounded-md border-2 border-[#243475] object-cover"
                        />
                        <div
                            v-else
                            class="mx-auto flex aspect-[162/230] w-full max-w-[180px] items-center justify-center rounded-md bg-gray-100 text-xs text-gray-400"
                        >
                            No photo
                        </div>
                        <p class="mt-2 text-xs text-gray-600">{{ L.photo.en }}</p>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <h2 class="mb-2 text-sm font-bold tracking-wide text-[#243475] uppercase">2. Products</h2>
                    <p class="text-sm">{{ selectedProducts.length ? selectedProducts.join(', ') : 'None selected' }}</p>
                    <h2 class="mt-4 mb-2 text-sm font-bold tracking-wide text-[#243475] uppercase">3. Device & Service Allocation</h2>
                    <p class="text-sm">
                        {{ devices.length ? devices.join(', ') : 'No devices' }}<span v-if="r.sim_card_provided"> - SIM card provided</span>
                    </p>
                    <p class="mt-1 text-sm text-gray-600">Delivery: {{ day(r.delivery_date) }} {{ r.delivery_time }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-[#243475] uppercase">
                        4. Agreement Terms <span class="font-normal text-gray-400 normal-case">(v{{ r.terms_version }})</span>
                    </h2>
                    <ol class="space-y-2">
                        <li v-for="(t, i) in terms" :key="i" class="flex gap-3 text-sm">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#e6007e] text-[11px] font-bold text-white"
                                >{{ i + 1 }}</span
                            >
                            <span class="text-justify">{{ t }}</span>
                        </li>
                    </ol>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-[#243475] uppercase">5. Documents</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div v-for="(label, key) in documentTypes" :key="key" class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                            <FileText class="h-5 w-5 shrink-0 text-gray-500" />
                            <span class="flex-1">{{ L.documents[key]?.en ?? label }}</span>
                            <a
                                v-if="uploaded.includes(key)"
                                :href="file(key)"
                                target="_blank"
                                rel="noopener"
                                class="text-xs font-semibold text-blue-600 underline"
                                >View</a
                            >
                            <span v-else class="text-xs text-gray-400">-</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-[#243475] uppercase">6. Signatures</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="mb-1 text-xs text-gray-500">{{ L.person_signature.en }}</p>
                            <img
                                :src="file('person_signature')"
                                alt="Authorized person signature"
                                class="h-24 w-full rounded-md border-2 border-[#243475] bg-white object-contain"
                            />
                        </div>
                        <div>
                            <p class="mb-1 text-xs text-gray-500">{{ L.company_signature.en }}</p>
                            <img
                                :src="file('company_signature')"
                                alt="Company representative signature"
                                class="h-24 w-full rounded-md border-2 border-[#243475] bg-white object-contain"
                            />
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">Date: {{ day(r.signed_date) }} &nbsp;|&nbsp; Place: {{ r.signed_place }}</p>
                </div>

                <div v-if="r.events?.length" class="rounded-xl bg-white p-5 shadow">
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-[#243475] uppercase">Activity</h2>
                    <ul class="divide-y text-sm">
                        <li v-for="e in r.events" :key="e.id" class="flex flex-wrap items-baseline justify-between gap-2 py-2">
                            <span>
                                <b class="font-semibold">{{ actionLabel(e) }}</b>
                                <span class="text-gray-600"> by {{ e.user?.name ?? 'system' }}</span>
                                <span v-if="e.meta?.reason" class="text-gray-500"> - {{ e.meta.reason }}</span>
                            </span>
                            <span class="text-xs text-gray-400"
                                >{{ fmt(e.created_at) }}<template v-if="e.ip_address"> - {{ e.ip_address }}</template></span
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <Dialog :open="showApprove" @update:open="(o: boolean) => !busy && (showApprove = o)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Approve {{ r.reference_no }}?</DialogTitle>
                    <DialogDescription>
                        The signed agreement PDF will be generated and this registration can no longer be edited or deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" :disabled="busy" @click="showApprove = false">Cancel</Button>
                    <Button class="bg-emerald-600 hover:bg-emerald-700" :disabled="busy" @click="approve">{{
                        busy ? 'Generating PDF...' : 'Approve & generate PDF'
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="showReject" @update:open="(o: boolean) => !busy && (showReject = o)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject {{ r.reference_no }}</DialogTitle>
                    <DialogDescription>Tell the submitter what to fix. They can edit and re-submit it.</DialogDescription>
                </DialogHeader>
                <textarea
                    v-model="reason"
                    rows="4"
                    maxlength="1000"
                    placeholder="Reason for rejection (min 5 characters)"
                    class="w-full rounded-md border border-gray-300 p-2 text-sm focus:ring-2 focus:ring-red-300 focus:outline-none"
                />
                <p v-if="reasonError" class="text-xs text-red-600">{{ reasonError }}</p>
                <DialogFooter>
                    <Button variant="outline" :disabled="busy" @click="showReject = false">Cancel</Button>
                    <Button variant="destructive" :disabled="busy" @click="reject">{{ busy ? 'Rejecting...' : 'Reject' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
