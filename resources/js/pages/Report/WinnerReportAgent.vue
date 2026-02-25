<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import Multiselect from '@vueform/multiselect'
import axios from 'axios'

type BreadcrumbItem = { title: string; href: string }

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Winner Report Agent', href: '/reports/winner-report-agent' },
]

const { agents, wins, products, filters } = defineProps<{
  agents: Array<any>
  wins: any
  products: Array<any>
  filters: { agent: string | number; from_date: string; to_date: string }
}>()

const form = useForm({
  agent: filters?.agent ?? '',
  from_date: filters?.from_date ?? '',
  to_date: filters?.to_date ?? '',
})

const hasSearched = computed(() => !!(form.agent || form.from_date || form.to_date))

const handleSearch = () => {
  form.get(route('reports.winner-report-agent'), {
    showProgress: false,
    preserveState: true,
    replace: true,
  })
}

// pagination preserve filters
function goTo(url: string | null) {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  const params = new URLSearchParams(window.location.search)

  if (page) params.set('page', page)

  if (form.agent) params.set('agent', String(form.agent))
  else params.delete('agent')

  if (form.from_date) params.set('from_date', form.from_date)
  else params.delete('from_date')

  if (form.to_date) params.set('to_date', form.to_date)
  else params.delete('to_date')

  router.visit(`${window.location.pathname}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    showProgress: false,
  })
}

/** ---------------- Modal ---------------- */
const showModal = ref(false)
const isLoadingModal = ref(false)
const modalTitle = ref('All Winner')
const modalRows = ref<any[]>([])
const modalLinks = ref<any[]>([])
const modalPageInfo = ref<any>({})
const modalClaimedFilter = ref<0 | 1 | null>(null)

const openModal = async (claimed: 0 | 1 | null) => {
  showModal.value = true
  modalClaimedFilter.value = claimed
  modalTitle.value = claimed === 1 ? 'Claimed Only' : 'All Winner'
  await fetchModalData()
}

const closeModal = () => {
  showModal.value = false
  modalRows.value = []
  modalLinks.value = []
  modalPageInfo.value = {}
}

const fetchModalData = async (pageUrl?: string) => {
  if (!form.agent) return
  isLoadingModal.value = true
  try {
    const url = pageUrl ?? route('reports.winner-report-agent.details')
    const res = await axios.get(url, {
      params: {
        agent: form.agent,
        from_date: form.from_date || null,
        to_date: form.to_date || null,
        claimed: modalClaimedFilter.value === null ? null : modalClaimedFilter.value,
      },
    })

    const data = res.data
    modalRows.value = data.data || []
    modalLinks.value = data.links || []
    modalPageInfo.value = {
      from: data.from,
      to: data.to,
      total: data.total,
    }
  } finally {
    isLoadingModal.value = false
  }
}

const formatDate = (d: string) => {
  if (!d) return ''
  const dt = new Date(d)
  return dt.toLocaleDateString()
}

const formatDateTime = (d: string) => {
  if (!d) return ''
  const dt = new Date(d)
  return dt.toLocaleString()
}
const extractNumbers = (order: any): string[] => {
  const t = order?.tickets?.[0]
  if (!t) return []

  if (Array.isArray(t.selected_numbers)) {
    return t.selected_numbers.map((n: any) => String(n))
  }
  try {
    const parsed = JSON.parse(t.selected_numbers)
    if (Array.isArray(parsed)) return parsed.map((n: any) => String(n))
  } catch (e) {}

  return []
}
</script>

<template>
  <Head title="Winner Report Agent" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 p-3">

                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-lg">
                  <div class="grid grid-cols-2 md:grid-cols-6 gap-4 p-3 mb-2 items-center">
                    <div class="group">
                      <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Agent <span class="text-red-500">*</span>
                      </label>

                      <Multiselect
                        v-model="form.agent"
                        :options="agents"
                        valueProp="id"
                        label="name"
                        placeholder="Agent..."
                        :searchable="true"
                        class="w-full"
                      />

                      <p v-if="form.errors.agent" class="text-red-600 text-sm">
                        {{ form.errors.agent }}
                      </p>
                    </div>

                    <div class="group">
                      <label class="block text-sm font-medium text-gray-700 mb-3">From Date</label>
                      <Input v-model="form.from_date" type="date" class="w-full" />
                    </div>

                    <div class="group">
                      <label class="block text-sm font-medium text-gray-700 mb-3">To Date</label>
                      <Input v-model="form.to_date" type="date" class="w-full" />
                    </div>

                    <div class="flex items-center flex-col">
                      <button
                        @click="handleSearch"
                        class="cursor-pointer px-6 py-2 bg-gradient-to-r from-teal-500 to-green-500 text-white rounded-xl hover:from-teal-600 hover:to-green-600 transition-all duration-200 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                      >
                        Search
                      </button>
                    </div>
                  </div>
                </div>

                <!-- No data before search -->
                <div v-if="!hasSearched" class="text-center py-12">
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No data</h3>
                  <p class="text-gray-500">Please select Agent and date range, then click Search.</p>
                </div>

                <!-- Summary Table (User wise one row) -->
                <div v-else class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 mt-4">
                  <div class="overflow-x-auto">
                    <table class="w-full">
                      <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Vendor Name</th>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Address</th>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Total Prize</th>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Claimed</th>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Unclaimed</th>
                          <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        </tr>
                      </thead>

                      <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in wins?.data" :key="row.user_id" class="hover:bg-gray-50">
                          <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ row?.user?.name }}</div>
                          </td>

                          <td class="px-6 py-4 text-sm text-gray-700">
                            {{ row?.user?.address }}
                          </td>

                          <td class="px-6 py-4 font-semibold text-gray-900">
                           {{ row.total_prize }}
                          </td>

                          <td class="px-6 py-4 font-semibold text-gray-900">
                            {{ row?.claimed_prize }}
                          </td>

                          <td class="px-6 py-4 font-semibold text-gray-900">
                            {{ row?.unclaimed_prize }}
                          </td>

                          <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                              <button
                                @click="openModal(null)"
                                class="inline-flex items-center cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow"
                              >
                                All Winner
                              </button>

                              <button
                                @click="openModal(1)"
                                class="inline-flex items-center cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                              >
                                Claimed Only
                              </button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- pagination -->
                    <div v-if="wins?.links && wins?.data?.length" class="mt-4 flex justify-end py-5 px-6">
                      <nav class="flex items-center space-x-1">
                        <button
                          v-for="(link, i) in wins.links"
                          :key="i"
                          @click="goTo(link.url)"
                          v-html="link.label"
                          :disabled="!link.url"
                          :class="[
                            'px-3 py-1 rounded border transition-all duration-200',
                            link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                          ]"
                        />
                      </nav>
                    </div>

                    <div v-if="wins?.data && wins.data.length === 0" class="text-center py-12">
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No records found</h3>
                      <p class="text-gray-500">Try changing date range.</p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ Modal (Screenshot style) -->
    <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
        <div class="bg-white w-full max-w-6xl rounded-2xl shadow-2xl overflow-hidden">
          <!-- header -->
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
              <h3 class="text-lg font-bold text-gray-900">{{ modalTitle }}</h3>
              <p class="text-sm text-gray-500" v-if="modalPageInfo?.total">
                Showing {{ modalPageInfo.from }} - {{ modalPageInfo.to }} of {{ modalPageInfo.total }}
              </p>
            </div>

            <button @click="closeModal" class="text-gray-400 hover:text-gray-700">
              ✕
            </button>
          </div>

          <!-- body -->
          <div class="p-4">
            <div v-if="isLoadingModal" class="py-10 text-center text-gray-600">
              Loading...
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border">
                  <tr class="text-gray-600">
                    <th class="p-3 text-left">SL</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Invoice No</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Description</th>
                    <th class="p-3 text-left">Number</th>
                    <th class="p-3 text-left">Prize (AED)</th>
                    <th class="p-3 text-left">Claim To</th>
                    <th class="p-3 text-left">Claimed Date</th>
                  </tr>
                </thead>

                <tbody class="divide-y">
                  <tr v-for="(order, idx) in modalRows" :key="order.id" class="hover:bg-gray-50">
                    <td class="p-3">{{ idx + 1 }}</td>
                    <td class="p-3">{{ formatDate(order.created_at) }}</td>
                    <td class="p-3 font-medium">{{ order.invoice_no }}</td>
                    <td class="p-3">{{ order.type ?? '-' }}</td>
                    <td class="p-3">{{ order.description ?? 'Chance winner' }}</td>

                    <td class="p-3">
                    <div class="flex flex-wrap gap-1">
                        <span
                        v-for="(n, i) in extractNumbers(order)"
                        :key="i"
                        class="w-7 h-7 rounded-full border flex items-center justify-center text-xs font-semibold text-gray-700"
                        >
                        {{ n }}
                        </span>
                    </div>
                    </td>

                    <td class="p-3 font-semibold">
                    {{ (order?.check_win?.total_prize ?? order?.check_win?.['total_prize'] ?? 0) }}
                    </td>
                    <td class="p-3 uppercase text-gray-700">{{ order?.user?.name ?? '-' }}</td>
                    <td class="p-3">
                      {{ order.is_claimed ? formatDateTime(order.claimed_at ?? order.updated_at) : '-' }}
                    </td>
                  </tr>

                  <tr v-if="modalRows.length === 0">
                    <td colspan="9" class="p-6 text-center text-gray-500">
                      No data found.
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- modal pagination -->
              <div v-if="modalLinks?.length" class="mt-4 flex justify-end">
                <nav class="flex items-center gap-1">
                  <button
                    v-for="(link, i) in modalLinks"
                    :key="i"
                    @click="link.url && fetchModalData(link.url)"
                    v-html="link.label"
                    :disabled="!link.url"
                    :class="[
                      'px-3 py-1 rounded border transition-all duration-200 text-sm',
                      link.active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-700 hover:bg-orange-100 border-gray-300',
                      !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  />
                </nav>
              </div>
            </div>
          </div>

          <!-- footer -->
          <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
            <button @click="closeModal" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>