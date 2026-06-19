<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Leads</h1>
        <p class="text-sm text-slate-500">Manage and track every customer enquiry.</p>
      </div>
      <button class="btn-primary" @click="openCreate">
        <PlusIcon class="w-4 h-4" /> New Lead
      </button>
    </div>

    <div class="card p-3 flex flex-wrap gap-2 items-center">
      <input v-model="filters.search" @keyup.enter="load(1)" placeholder="Search name, mobile, email..."
        class="input max-w-xs" />
      <select v-model="filters.status_id" @change="load(1)" class="input max-w-[180px]">
        <option value="">All statuses</option>
        <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
      <select v-model="filters.source_id" @change="load(1)" class="input max-w-[180px]">
        <option value="">All sources</option>
        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
      <select v-model="filters.project_id" @change="load(1)" class="input max-w-[180px]">
        <option value="">All projects</option>
        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>
      <input v-model="filters.sub_source" @keyup.enter="load(1)" placeholder="Sub source" class="input max-w-[180px]" />
      <button class="btn-secondary" @click="resetFilters">Reset</button>
      <div class="flex flex-wrap gap-2 sm:ml-auto">
        <button class="btn-secondary" @click="exportLeads('csv')"><ArrowDownTrayIcon class="w-4 h-4" /> CSV</button>
        <button class="btn-secondary" @click="exportLeads('xlsx')"><ArrowDownTrayIcon class="w-4 h-4" /> Excel</button>
        <button class="btn-secondary" @click="exportLeads('pdf')"><ArrowDownTrayIcon class="w-4 h-4" /> PDF</button>
      </div>
    </div>

    <div class="card overflow-hidden">
      <table class="table-base">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Project</th>
            <th>Source</th>
            <th>Sub Source</th>
            <th>Status</th>
            <th>Assignee</th>
            <th>Follow-up</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="l in items" :key="l.id">
            <td>
              <div class="font-semibold text-slate-800">{{ l.customer_name }}</div>
              <div class="text-xs text-slate-500">{{ l.city || '-' }}</div>
            </td>
            <td>
              <div>{{ l.mobile || '-' }}</div>
              <div class="text-xs text-slate-500">{{ l.email || '' }}</div>
              <div v-if="l.mobile" class="flex gap-1.5 mt-1.5">
                <a :href="telHref(l.mobile)" @click="logContactActivity(l.id, 'call', l.mobile)"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-brand-50 text-brand-700 hover:bg-brand-100 text-xs font-medium transition" title="Call Now">
                  <PhoneIcon class="w-3.5 h-3.5" /> Call
                </a>
                <a :href="waHref(l.mobile)" target="_blank" rel="noopener"
                   @click="logContactActivity(l.id, 'whatsapp', l.mobile)"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-medium transition" title="WhatsApp Now">
                  <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" /> WhatsApp
                </a>
              </div>
            </td>
            <td>{{ l.project?.name || '-' }}</td>
            <td><span class="badge bg-slate-100 text-slate-700">{{ l.source?.name || '-' }}</span></td>
            <td class="text-sm text-slate-500">{{ l.sub_source || '-' }}</td>
            <td>
              <span class="badge" :style="{ background: (l.status?.color || '#e2e8f0') + '22', color: l.status?.color || '#475569' }">
                {{ l.status?.name || '-' }}
              </span>
            </td>
            <td>{{ l.assignee?.name || 'Unassigned' }}</td>
            <td class="text-sm">{{ fmt(l.follow_up_at) }}</td>
            <td class="text-right">
              <router-link :to="`/leads/${l.id}`" class="text-brand-600 hover:underline text-sm font-medium">Open</router-link>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="9" class="text-center text-slate-400 py-10">No leads found.</td></tr>
        </tbody>
      </table>
      <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-sm">
        <div class="text-slate-500">Page {{ meta.current_page }} of {{ meta.last_page }} - {{ meta.total }} leads</div>
        <div class="space-x-2">
          <button class="btn-secondary" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Prev</button>
          <button class="btn-secondary" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
        </div>
      </div>
    </div>

    <LeadFormModal
      v-if="showCreate"
      :statuses="statuses" :sources="sources" :projects="projects" :users="agents"
      @close="showCreate = false"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { ArrowDownTrayIcon, PlusIcon, PhoneIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import LeadFormModal from '../components/LeadFormModal.vue';
import { telHref, waHref, logContactActivity } from '../utils/contact.js';
import { downloadFile } from '../utils/download.js';
import { useToast } from 'vue-toastification';

const route = useRoute();
const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const statuses = ref([]);
const sources = ref([]);
const projects = ref([]);
const agents = ref([]);
const filters = reactive({
  search: '',
  status_id: '',
  source_id: '',
  project_id: '',
  sub_source: '',
  status_slug: '',
  source_slug: '',
  assigned: '',
  due: '',
});
const showCreate = ref(false);

const openCreate = () => showCreate.value = true;

const cleanedParams = (extra = {}) => Object.fromEntries(
  Object.entries({ ...filters, ...extra }).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
);

const load = async (page = 1) => {
  const { data } = await axios.get('/api/leads', { params: cleanedParams({ page }) });
  items.value = data.data;
  meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
};

const resetFilters = () => {
  Object.keys(filters).forEach(k => filters[k] = '');
  load(1);
};

const fmt = (d) => d ? new Date(d).toLocaleString() : '-';

const onSaved = () => { showCreate.value = false; load(1); };

const syncFiltersFromRoute = () => {
  Object.keys(filters).forEach(k => {
    filters[k] = route.query[k] !== undefined ? route.query[k] : '';
  });
  if (route.query.q) filters.search = route.query.q;
};

const toast = useToast();
const exporting = ref(false);

const exportLeads = async (format) => {
  exporting.value = true;
  try {
    const ext = format === 'xlsx' ? 'xls' : format;
    await downloadFile('/api/leads/export', cleanedParams({ format }), `leads.${ext}`);
  } catch (e) {
    toast.error('Export failed. Please try again.');
  } finally {
    exporting.value = false;
  }
};

onMounted(async () => {
  const [s, src, p, u] = await Promise.all([
    axios.get('/api/lead-statuses'),
    axios.get('/api/lead-sources'),
    axios.get('/api/projects'),
    axios.get('/api/users').catch(() => ({ data: { data: [] } })),
  ]);
  statuses.value = s.data;
  sources.value = src.data;
  projects.value = p.data;
  agents.value = (u.data?.data || u.data || []).filter(x => ['agent', 'sub_agent', 'sub_master'].includes(x.role));

  syncFiltersFromRoute();
  if (route.query.new) showCreate.value = true;
  load(1);
});

watch(() => route.query, () => {
  syncFiltersFromRoute();
  if (route.query.new) showCreate.value = true;
  load(1);
});
</script>
