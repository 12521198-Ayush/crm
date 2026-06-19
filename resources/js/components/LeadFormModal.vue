<template>
  <div class="fixed inset-0 z-50 bg-slate-900/50 grid place-items-center p-4" @click.self="$emit('close')">
    <div class="card w-full max-w-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">New Lead</h2>
        <button @click="$emit('close')" class="p-1 rounded hover:bg-slate-100"><XMarkIcon class="w-5 h-5" /></button>
      </div>

      <div class="inline-flex rounded-lg border border-slate-200 p-1 mb-5">
        <button class="px-3 py-1.5 rounded-md text-sm font-medium" :class="mode === 'manual' ? 'bg-brand-600 text-white' : 'text-slate-600'" @click="mode = 'manual'">
          Manual Entry
        </button>
        <button class="px-3 py-1.5 rounded-md text-sm font-medium" :class="mode === 'bulk' ? 'bg-brand-600 text-white' : 'text-slate-600'" @click="mode = 'bulk'">
          Bulk Upload
        </button>
      </div>

      <form v-if="mode === 'manual'" @submit.prevent="save" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Customer Name *</label>
          <input v-model="form.customer_name" class="input" required />
        </div>
        <div>
          <label class="label">Mobile</label>
          <input v-model="form.mobile" class="input" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" class="input" />
        </div>
        <div>
          <label class="label">City</label>
          <input v-model="form.city" class="input" />
        </div>
        <div>
          <label class="label">Project</label>
          <select v-model="form.project_id" class="input">
            <option value="">- Select -</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Source</label>
          <select v-model="form.source_id" class="input">
            <option value="">- Select -</option>
            <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Sub Source</label>
          <input v-model="form.sub_source" class="input" />
        </div>
        <div>
          <label class="label">Status</label>
          <select v-model="form.status_id" class="input">
            <option value="">- Default -</option>
            <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Budget</label>
          <input v-model="form.budget" type="number" min="0" step="0.01" class="input" />
        </div>
        <div>
          <label class="label">Assign to</label>
          <select v-model="form.assigned_to" class="input">
            <option value="">- Unassigned -</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }})</option>
          </select>
        </div>
        <div>
          <label class="label">Follow-up Date</label>
          <input v-model="form.follow_up_at" type="datetime-local" class="input" />
        </div>
        <div>
          <label class="label">Campaign Name</label>
          <input v-model="form.campaign_name" class="input" />
        </div>
        <div>
          <label class="label">Ad Set Name</label>
          <input v-model="form.ad_set_name" class="input" />
        </div>
        <div>
          <label class="label">Ad Name</label>
          <input v-model="form.ad_name" class="input" />
        </div>
        <div class="sm:col-span-2">
          <label class="label">Remarks</label>
          <textarea v-model="form.remarks" rows="3" class="input"></textarea>
        </div>
        <div v-if="error" class="sm:col-span-2 text-sm text-rose-600">{{ error }}</div>
        <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="$emit('close')">Cancel</button>
          <button class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save lead' }}</button>
        </div>
      </form>

      <div v-else class="space-y-4">
        <div class="rounded-lg border border-slate-200 p-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-800">Upload leads</h3>
              <p class="text-sm text-slate-500">Download a template, fill it in, then upload as CSV.</p>
            </div>
            <div class="flex items-center gap-2">
              <button class="btn-secondary" :disabled="downloading" @click="downloadTemplate('csv')">
                <ArrowDownTrayIcon class="w-4 h-4" /> CSV
              </button>
              <button class="btn-secondary" :disabled="downloading" @click="downloadTemplate('xlsx')">
                <ArrowDownTrayIcon class="w-4 h-4" /> Excel
              </button>
            </div>
          </div>
          <input type="file" accept=".csv,text/csv" class="input mt-4" @change="file = $event.target.files?.[0] || null" />
        </div>
        <div v-if="bulkMessage" class="text-sm" :class="bulkErrors.length ? 'text-amber-700' : 'text-emerald-700'">{{ bulkMessage }}</div>
        <ul v-if="bulkErrors.length" class="text-xs text-rose-600 max-h-28 overflow-auto space-y-1">
          <li v-for="err in bulkErrors" :key="`${err.row}-${err.message}`">Row {{ err.row }}: {{ err.message }}</li>
        </ul>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="$emit('close')">Cancel</button>
          <button class="btn-primary" :disabled="saving || !file" @click="upload">{{ saving ? 'Uploading...' : 'Upload leads' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { ArrowDownTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { downloadFile } from '../utils/download';

defineProps({ statuses: Array, sources: Array, projects: Array, users: Array });
const emit = defineEmits(['close', 'saved']);

const mode = ref('manual');
const file = ref(null);
const bulkMessage = ref('');
const bulkErrors = ref([]);
const form = reactive({
  customer_name: '', mobile: '', email: '', city: '',
  project_id: '', source_id: '', sub_source: '', status_id: '',
  budget: '', remarks: '', follow_up_at: '', assigned_to: '',
  campaign_name: '', ad_set_name: '', ad_name: '',
});
const saving = ref(false);
const error = ref('');

const compact = (source) => Object.fromEntries(Object.entries(source).filter(([_, v]) => v !== '' && v !== null));

const save = async () => {
  saving.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/api/leads', compact(form));
    emit('saved', data);
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to save lead';
  } finally {
    saving.value = false;
  }
};

const toast = useToast();
const downloading = ref(false);

const downloadTemplate = async (format = 'csv') => {
  downloading.value = true;
  try {
    const ext = format === 'xlsx' ? 'xls' : 'csv';
    await downloadFile('/api/leads/import-template', { format }, `lead-import-template.${ext}`);
  } catch (e) {
    toast.error('Could not download template. Please try again.');
  } finally {
    downloading.value = false;
  }
};

const upload = async () => {
  if (!file.value) return;
  saving.value = true;
  bulkMessage.value = '';
  bulkErrors.value = [];
  try {
    const body = new FormData();
    body.append('file', file.value);
    const { data } = await axios.post('/api/leads/import', body);
    bulkErrors.value = data.errors || [];
    bulkMessage.value = `${data.created || 0} leads imported`;
    if (!bulkErrors.value.length) emit('saved', data);
  } catch (e) {
    bulkMessage.value = e?.response?.data?.message || 'Failed to upload leads';
  } finally {
    saving.value = false;
  }
};
</script>
