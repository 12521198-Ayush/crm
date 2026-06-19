<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-900">Welcome back</h1>
      <p class="text-sm text-slate-500 mt-1">Sign in to continue to your Ninja CRM workspace.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label class="label">Email address</label>
        <div class="relative">
          <EnvelopeIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="form.email" type="email" required
                 placeholder="you@company.com"
                 autocomplete="username"
                 class="input pl-10" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="label !mb-0">Password</label>
          <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-700">Forgot password?</a>
        </div>
        <div class="relative">
          <LockClosedIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="form.password" :type="showPwd ? 'text' : 'password'" required
                 placeholder="••••••••"
                 autocomplete="current-password"
                 class="input pl-10 pr-10" />
          <button type="button" @click="showPwd = !showPwd"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
            <EyeIcon v-if="!showPwd" class="w-5 h-5" />
            <EyeSlashIcon v-else class="w-5 h-5" />
          </button>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm text-slate-600 select-none cursor-pointer">
        <input v-model="form.remember" type="checkbox"
               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
        Remember me on this device
      </label>

      <div v-if="error" class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
        {{ error }}
      </div>

      <button class="btn-primary w-full py-2.5 text-sm" :disabled="loading">
        <span v-if="loading" class="flex items-center justify-center gap-2">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          Signing in…
        </span>
        <span v-else>Sign in</span>
      </button>
    </form>

    <!-- <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Demo accounts</div>
      <div class="grid gap-1.5 text-xs text-slate-600">
        <button type="button" @click="fill('master@ninja.test')" class="flex justify-between hover:text-brand-700">
          <span>master@ninja.test</span><span class="text-slate-400">Master</span>
        </button>
        <button type="button" @click="fill('submaster@ninja.test')" class="flex justify-between hover:text-brand-700">
          <span>submaster@ninja.test</span><span class="text-slate-400">Sub Master</span>
        </button>
        <button type="button" @click="fill('agent@ninja.test')" class="flex justify-between hover:text-brand-700">
          <span>agent@ninja.test</span><span class="text-slate-400">Agent</span>
        </button>
      </div>
      <div class="text-[11px] text-slate-500 mt-2">Password for all: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">password</code></div>
    </div> -->
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { EnvelopeIcon, LockClosedIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({ email: '', password: '', remember: true });
const loading = ref(false);
const error = ref('');
const showPwd = ref(false);

const fill = (email) => {
  form.email = email;
  form.password = 'password';
};

const submit = async () => {
  loading.value = true; error.value = '';
  try {
    await auth.login(form.email, form.password);
    router.push(route.query.redirect || '/dashboard');
  } catch (e) {
    error.value = e?.response?.data?.message || 'Invalid credentials';
  } finally { loading.value = false; }
};
</script>
