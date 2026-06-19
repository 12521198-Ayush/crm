<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Team & Users</h1>
        <p class="text-sm text-slate-500">Manage your sub-masters and agents.</p>
      </div>
      <button class="btn-primary" @click="openForm()"><PlusIcon class="w-4 h-4" /> Add user</button>
    </div>

    <div class="card overflow-hidden">
      <table class="table-base">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="u in items" :key="u.id">
            <td class="font-semibold">{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td><span class="badge bg-slate-100 text-slate-700">{{ u.role }}</span></td>
            <td>
              <span :class="u.is_active ? 'badge bg-emerald-100 text-emerald-700' : 'badge bg-slate-200 text-slate-600'">
                {{ u.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-right">
              <button class="text-brand-600 hover:underline text-sm font-medium mr-3" @click="openForm(u)">Edit</button>
              <button class="text-rose-600 hover:underline text-sm font-medium" @click="remove(u)">Delete</button>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="text-center text-slate-400 py-8">No users.</td></tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="fixed inset-0 z-50 bg-slate-900/50 grid place-items-center p-4" @click.self="showForm = false">
      <form @submit.prevent="save" class="card p-6 w-full max-w-md space-y-3">
        <h3 class="text-lg font-semibold">{{ form.id ? 'Edit User' : 'New User' }}</h3>
        <div><label class="label">Name</label><input v-model="form.name" required class="input" /></div>
        <div><label class="label">Email</label><input v-model="form.email" type="email" required class="input" /></div>
        <div><label class="label">Phone</label><input v-model="form.phone" class="input" /></div>
        <div>
          <label class="label">Role</label>
          <select v-model="form.role" class="input" required>
            <option value="agent">Agent</option>
            <option v-if="auth.isMaster" value="sub_master">Sub Master</option>
            <option v-if="auth.isMaster" value="master">Master</option>
          </select>
        </div>
        <div><label class="label">Password {{ form.id ? '(leave blank to keep)' : '*' }}</label>
          <input v-model="form.password" type="password" :required="!form.id" class="input" />
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" /> Active
        </label>
        <div v-if="error" class="text-sm text-rose-600">{{ error }}</div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
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
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const items = ref([]);
const showForm = ref(false);
const error = ref('');
const form = reactive({ id: null, name: '', email: '', phone: '', role: 'agent', password: '', is_active: true });

const load = async () => {
  const { data } = await axios.get('/api/users');
  items.value = data.data || data;
};

const openForm = (u = null) => {
  error.value = '';
  Object.assign(form, { id: null, name: '', email: '', phone: '', role: 'agent', password: '', is_active: true });
  if (u) Object.assign(form, { ...u, password: '' });
  showForm.value = true;
};

const save = async () => {
  error.value = '';
  try {
    const payload = { ...form };
    if (!payload.password) delete payload.password;
    if (form.id) await axios.put(`/api/users/${form.id}`, payload);
    else         await axios.post('/api/users', payload);
    showForm.value = false;
    await load();
  } catch (e) { error.value = e?.response?.data?.message || 'Save failed'; }
};

const remove = async (u) => {
  if (!confirm(`Delete ${u.name}?`)) return;
  await axios.delete(`/api/users/${u.id}`);
  await load();
};

onMounted(load);
</script>
