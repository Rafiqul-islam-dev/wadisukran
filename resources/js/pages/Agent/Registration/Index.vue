<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { can } from '@/helpers/permissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, FilePlus2, Pencil, Search, Trash2 } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { statusStyles } from './labels';

interface Row {
    id: number;
    reference_no: string;
    status: 'pending' | 'approved' | 'rejected';
    shop_name: string;
    owner_name: string;
    mobile_country_code: string;
    mobile: string;
    created_at: string;
    creator?: { id: number; name: string } | null;
}

const props = defineProps<{
    registrations: { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { status: string | null; search: string };
    counts: { all: number; pending: number; approved: number; rejected: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Registration & Agreement', href: '/agents/registrations' }];

const tabs = [
    { key: '', label: 'All', count: () => props.counts.all },
    { key: 'pending', label: 'Pending', count: () => props.counts.pending },
    { key: 'approved', label: 'Approved', count: () => props.counts.approved },
    { key: 'rejected', label: 'Rejected', count: () => props.counts.rejected },
];

const search = ref(props.filters.search ?? '');
let timer: ReturnType<typeof setTimeout>;
const go = (status: string | null, q: string) =>
    router.get(route('agents.registrations.index'), { status: status || undefined, search: q || undefined }, { preserveState: true, replace: true });

onBeforeUnmount(() => clearTimeout(timer)); // a pending debounce must not redirect after navigating away
watch(
    () => props.filters.search,
    (s) => {
        if ((s ?? '') !== search.value) search.value = s ?? ''; // keep the box in sync after browser Back
    },
);
watch(search, (q) => {
    clearTimeout(timer);
    timer = setTimeout(() => go(props.filters.status, q), 350);
});

const toDelete = ref<Row | null>(null);
const deleting = ref(false);
function confirmDelete() {
    if (!toDelete.value || deleting.value) return;
    deleting.value = true;
    router.delete(route('agents.registrations.destroy', toDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success((usePage().props as any).flash?.success ?? 'Registration deleted.');
            toDelete.value = null;
        },
        onError: () => toast.error('Could not delete this registration.'),
        onFinish: () => (deleting.value = false),
    });
}

const fmt = (d: string) => new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Registration & Agreement" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                        :class="(filters.status ?? '') === t.key ? 'bg-[#243475] text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        @click="go(t.key, search)"
                    >
                        {{ t.label }} <span class="ml-1 opacity-70">{{ t.count() }}</span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="search"
                            placeholder="Search ref, shop, owner, phone, email"
                            class="h-9 w-64 rounded-md border border-gray-300 bg-white pr-3 pl-9 text-sm focus:ring-2 focus:ring-[#e6007e]/40 focus:outline-none"
                        />
                    </div>
                    <Link v-if="can('create sales point registration')" :href="route('agents.registrations.create')">
                        <Button class="bg-[#e6007e] hover:bg-[#c9006f]"><FilePlus2 class="mr-2 h-4 w-4" /> New Registration</Button>
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-bold tracking-wider text-gray-600 uppercase">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Shop</th>
                                <th class="px-4 py-3">Owner</th>
                                <th class="px-4 py-3">Mobile</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in registrations.data" :key="row.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-[#243475]">{{ row.reference_no }}</td>
                                <td class="px-4 py-3">{{ row.shop_name }}</td>
                                <td class="px-4 py-3">{{ row.owner_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ row.mobile_country_code }} {{ row.mobile }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize" :class="statusStyles[row.status]">{{
                                        row.status
                                    }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ fmt(row.created_at)
                                    }}<span v-if="row.creator" class="block text-xs text-gray-400">{{ row.creator.name }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Link
                                            :href="route('agents.registrations.show', row.id)"
                                            class="rounded p-1.5 text-blue-600 hover:bg-blue-50"
                                            title="View"
                                            ><Eye class="h-4 w-4"
                                        /></Link>
                                        <Link
                                            v-if="can('update sales point registration') && row.status !== 'approved'"
                                            :href="route('agents.registrations.edit', row.id)"
                                            class="rounded p-1.5 text-amber-600 hover:bg-amber-50"
                                            title="Edit"
                                            ><Pencil class="h-4 w-4"
                                        /></Link>
                                        <button
                                            v-if="can('delete sales point registration') && row.status !== 'approved'"
                                            type="button"
                                            class="rounded p-1.5 text-red-600 hover:bg-red-50"
                                            title="Delete"
                                            @click="toDelete = row"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!registrations.data.length">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">No registrations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="registrations.links.length > 3" class="flex flex-wrap items-center justify-center gap-1 border-t p-3">
                    <template v-for="(l, i) in registrations.links" :key="i">
                        <Link
                            v-if="l.url"
                            :href="l.url"
                            preserve-state
                            class="rounded px-3 py-1 text-sm"
                            :class="l.active ? 'bg-[#243475] text-white' : 'text-gray-700 hover:bg-gray-100'"
                            v-html="l.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="l.label" />
                    </template>
                </div>
            </div>
        </div>

        <Dialog :open="!!toDelete" @update:open="(o: boolean) => !o && (toDelete = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete registration?</DialogTitle>
                    <DialogDescription>{{ toDelete?.reference_no }} - {{ toDelete?.shop_name }} will be removed from the list.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="toDelete = null">Cancel</Button>
                    <Button variant="destructive" :disabled="deleting" @click="confirmDelete">{{ deleting ? 'Deleting...' : 'Delete' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
