<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
        <p class="text-sm text-slate-500">{{ subtitle }}</p>
      </div>
      <button class="btn-primary" @click="openForm()"><PlusIcon class="w-4 h-4" /> New</button>
    </div>

    <div class="card overflow-hidden">
      <table class="table-base">
        <thead><tr>
          <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
          <th></th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in items" :key="row.id">
            <td v-for="c in columns" :key="c.key">
              <span v-if="c.boolean">
                <span :class="row[c.key] ? 'badge bg-emerald-100 text-emerald-700' : 'badge bg-slate-200 text-slate-600'">
                  {{ row[c.key] ? 'Yes' : 'No' }}
                </span>
              </span>
              <span v-else-if="c.color" class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded" :style="{ background: row[c.key] }"></span>
                <span class="font-mono text-xs">{{ row[c.key] }}</span>
              </span>
              <span v-else>{{ row[c.key] || '—' }}</span>
            </td>
            <td class="text-right">
              <button class="text-brand-600 hover:underline text-sm mr-3" @click="openForm(row)">Edit</button>
              <button class="text-rose-600 hover:underline text-sm" @click="remove(row)" :disabled="row.is_system">Delete</button>
            </td>
          </tr>
          <tr v-if="!items.length"><td :colspan="columns.length + 1" class="text-center text-slate-400 py-8">No records.</td></tr>
        </tbody>
      </table>
    </div>

    <div v-if="show" class="fixed inset-0 z-50 bg-slate-900/50 grid place-items-center p-4" @click.self="show = false">
      <form @submit.prevent="save" class="card p-6 w-full max-w-md space-y-3">
        <h3 class="text-lg font-semibold">{{ form.id ? 'Edit' : 'New' }}</h3>
        <div v-for="f in fields" :key="f.key">
          <label class="label">{{ f.label }}{{ f.required ? ' *' : '' }}</label>
          <textarea v-if="f.type === 'textarea'" v-model="form[f.key]" rows="3" class="input"></textarea>
          <label v-else-if="f.type === 'checkbox'" class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="form[f.key]" /> Enabled
          </label>
          <input v-else :type="f.type || 'text'" v-model="form[f.key]" class="input" :required="f.required" />
        </div>
        <div v-if="error" class="text-sm text-rose-600">{{ error }}</div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="show = false">Cancel</button>
          <button class="btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  title: String, subtitle: String, endpoint: String,
  columns: Array, fields: Array,
});

const items = ref([]);
const show = ref(false);
const error = ref('');
const form = reactive({});

const reset = () => {
  Object.keys(form).forEach(k => delete form[k]);
  form.id = null;
  props.fields.forEach(f => form[f.key] = f.default ?? (f.type === 'checkbox' ? false : ''));
};

const load = async () => {
  const { data } = await axios.get(props.endpoint);
  items.value = Array.isArray(data) ? data : (data.data || []);
};

const openForm = (row = null) => {
  reset(); error.value = '';
  if (row) Object.assign(form, row);
  show.value = true;
};

const save = async () => {
  error.value = '';
  try {
    if (form.id) await axios.put(`${props.endpoint}/${form.id}`, form);
    else         await axios.post(props.endpoint, form);
    show.value = false;
    await load();
  } catch (e) { error.value = e?.response?.data?.message || 'Save failed'; }
};

const remove = async (row) => {
  if (row.is_system) return alert('System record cannot be deleted.');
  if (!confirm('Delete this record?')) return;
  await axios.delete(`${props.endpoint}/${row.id}`);
  await load();
};

onMounted(load);
</script>
