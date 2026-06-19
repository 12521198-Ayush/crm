<template>
  <div class="max-w-2xl space-y-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">My Profile</h1>
      <p class="text-sm text-slate-500">Your account information.</p>
    </div>
    <div class="card p-6 flex items-center gap-4">
      <div class="w-16 h-16 rounded-full bg-brand-600 text-white grid place-items-center text-xl font-bold">
        {{ initials }}
      </div>
      <div>
        <div class="text-lg font-semibold">{{ auth.user?.name }}</div>
        <div class="text-sm text-slate-500">{{ auth.user?.email }}</div>
        <span class="badge bg-brand-100 text-brand-700 mt-1 inline-block">{{ auth.roleLabel }}</span>
      </div>
    </div>
    <div class="card p-6">
      <h3 class="font-semibold mb-2">Session</h3>
      <p class="text-sm text-slate-500 mb-4">Sign out of this device.</p>
      <button class="btn-danger" @click="logout">Sign out</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const initials = computed(() => (auth.user?.name || '?').split(' ').map(s => s[0]).join('').slice(0,2).toUpperCase());
const logout = async () => { await auth.logout(); router.push('/auth/login'); };
</script>
