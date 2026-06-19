<template>
  <div v-if="lead" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Left: lead info -->
    <div class="lg:col-span-2 space-y-4">
      <div class="card p-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-xs uppercase tracking-wider text-slate-400">Lead #{{ lead.id }}</div>
            <h1 class="text-xl font-bold text-slate-900">{{ lead.customer_name }}</h1>
            <div class="text-sm text-slate-500 mt-1">{{ lead.mobile }} · {{ lead.email || 'no email' }} · {{ lead.city || '—' }}</div>
          </div>
          <span class="badge" :style="{ background: (lead.status?.color || '#e2e8f0') + '22', color: lead.status?.color || '#475569' }">
            {{ lead.status?.name || '—' }}
          </span>
        </div>

        <div v-if="lead.mobile" class="flex flex-wrap gap-2 mt-4">
          <a :href="telHref(lead.mobile)" @click="trackContact('call')"
             class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700 text-sm font-medium shadow-sm transition">
            <PhoneIcon class="w-4 h-4" /> Call Now
          </a>
          <a :href="waHref(lead.mobile, `Hi ${lead.customer_name || ''}`)" target="_blank" rel="noopener"
             @click="trackContact('whatsapp')"
             class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-medium shadow-sm transition">
            <ChatBubbleLeftRightIcon class="w-4 h-4" /> WhatsApp Now
          </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-5 text-sm">
          <Info label="Project" :value="lead.project?.name" />
          <Info label="Source" :value="lead.source?.name" />
          <Info label="Budget" :value="lead.budget" />
          <Info label="Assignee" :value="lead.assignee?.name" />
          <Info label="Follow-up" :value="lead.follow_up_at && new Date(lead.follow_up_at).toLocaleString()" />
          <Info label="Created" :value="new Date(lead.created_at).toLocaleString()" />
        </div>

        <div class="mt-5">
          <div class="label">Remarks</div>
          <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ lead.remarks || '—' }}</div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="font-semibold mb-3">Activity</h3>
        <form @submit.prevent="addActivity" class="space-y-2 mb-4">
          <div class="flex gap-2 flex-wrap">
            <select v-model="act.type" class="input max-w-[180px]">
              <option value="note">Note</option>
              <option value="call">Call</option>
              <option value="meeting">Meeting</option>
              <option value="follow_up">Follow-up</option>
              <option value="event">Event</option>
            </select>
            <input v-model="act.title" placeholder="Title" class="input flex-1 min-w-[200px]" />
            <input v-if="act.type !== 'note'" v-model="act.scheduled_at" type="datetime-local" class="input max-w-[220px]" />
          </div>
          <textarea v-model="act.body" rows="2" placeholder="Add details…" class="input"></textarea>
          <div class="text-right"><button class="btn-primary">Add</button></div>
        </form>

        <ol class="relative border-l border-slate-200 ml-2 space-y-4">
          <li v-for="a in lead.activities" :key="a.id" class="pl-4">
            <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-brand-500"></span>
            <div class="text-xs text-slate-400">{{ new Date(a.created_at).toLocaleString() }} · {{ a.user?.name || 'System' }}</div>
            <div class="text-sm font-semibold capitalize">{{ a.type.replace('_', ' ') }}{{ a.title ? ' — ' + a.title : '' }}</div>
            <div v-if="a.body" class="text-sm text-slate-600 whitespace-pre-wrap">{{ a.body }}</div>
          </li>
          <li v-if="!lead.activities?.length" class="text-sm text-slate-400 pl-4">No activity yet.</li>
        </ol>
      </div>
    </div>

    <!-- Right: actions -->
    <div class="space-y-4">
      <div class="card p-5">
        <h3 class="font-semibold mb-3">Change Status</h3>
        <select v-model="newStatus" class="input mb-2">
          <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <textarea v-model="statusRemark" rows="2" class="input mb-2" placeholder="Add remark (optional)"></textarea>
        <button class="btn-primary w-full" @click="changeStatus">Update Status</button>
      </div>

      <div class="card p-5" v-if="canAssign">
        <h3 class="font-semibold mb-3">Assign</h3>
        <select v-model="newAssignee" class="input mb-2">
          <option value="">Unassigned</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }})</option>
        </select>
        <button class="btn-secondary w-full" @click="assign">Assign Lead</button>
      </div>
    </div>
  </div>
  <div v-else class="text-slate-500">Loading…</div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { PhoneIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';
import Info from '../components/InfoItem.vue';
import { telHref, waHref, logContactActivity } from '../utils/contact.js';

const props = defineProps({ id: [String, Number] });
const route = useRoute();
const auth = useAuthStore();

const lead = ref(null);
const statuses = ref([]); const users = ref([]);
const newStatus = ref(null); const statusRemark = ref('');
const newAssignee = ref('');
const act = reactive({ type: 'note', title: '', body: '', scheduled_at: '' });

const canAssign = computed(() => auth.isMaster || auth.isSubMaster);

const load = async () => {
  const id = props.id ?? route.params.id;
  const { data } = await axios.get(`/api/leads/${id}`);
  lead.value = data;
  newStatus.value = data.status?.id;
  newAssignee.value = data.assignee?.id || '';
};

const changeStatus = async () => {
  await axios.post(`/api/leads/${lead.value.id}/status`, { status_id: newStatus.value, remarks: statusRemark.value });
  statusRemark.value = '';
  await load();
};

const assign = async () => {
  await axios.post(`/api/leads/${lead.value.id}/assign`, { assigned_to: newAssignee.value });
  await load();
};

const addActivity = async () => {
  const payload = { ...act };
  if (!payload.scheduled_at) delete payload.scheduled_at;
  await axios.post(`/api/leads/${lead.value.id}/activities`, payload);
  act.title = ''; act.body = ''; act.scheduled_at = '';
  await load();
};

const trackContact = (type) => {
  logContactActivity(lead.value.id, type, lead.value.mobile);
  // Reload after a short delay so the new activity appears in the timeline.
  setTimeout(load, 800);
};

onMounted(async () => {
  const [s, u] = await Promise.all([
    axios.get('/api/lead-statuses'),
    axios.get('/api/users').catch(() => ({ data: { data: [] } })),
  ]);
  statuses.value = s.data;
  users.value = u.data?.data || u.data || [];
  await load();
});
</script>
