<template>
  <header class="sticky top-0 z-20 h-16 bg-white/70 backdrop-blur-xl shadow-[0_1px_0_rgba(15,23,42,0.04)] flex items-center px-4 sm:px-6 gap-3">
    <button class="btn-icon lg:hidden" @click="$emit('toggle-sidebar')">
      <Bars3Icon class="w-6 h-6 text-slate-700" />
    </button>

    <div class="hidden md:flex items-center max-w-md w-full rounded-xl bg-surface-100 px-3.5 py-2 transition focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-500/10">
      <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 mr-2" />
      <input
        v-model="search"
        @keyup.enter="goSearch"
        type="text"
        placeholder="Search leads, mobile, email…"
        class="w-full text-sm outline-none bg-transparent placeholder:text-slate-400"
      />
      <kbd class="hidden lg:inline-flex text-2xs text-slate-400 bg-white rounded px-1.5 py-0.5 shadow-soft">⏎</kbd>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <button class="hidden sm:inline-flex btn-primary" @click="$router.push('/leads?new=1')">
        <PlusIcon class="w-4 h-4" /> Add Lead
      </button>

      <button class="btn-icon relative">
        <BellIcon class="w-5 h-5 text-slate-600" />
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-danger-500 rounded-full ring-2 ring-white"></span>
      </button>

      <!-- Profile menu -->
      <div class="relative" v-click-outside="() => (menuOpen = false)">
        <button class="flex items-center gap-2 p-1.5 pr-3 rounded-full hover:bg-slate-100" @click="menuOpen = !menuOpen">
          <div class="w-8 h-8 rounded-full bg-brand-600 text-white grid place-items-center text-sm font-semibold">
            {{ initials }}
          </div>
          <div class="hidden sm:block text-left leading-tight">
            <div class="text-sm font-semibold text-slate-800">{{ auth.user?.name }}</div>
            <div class="text-[11px] text-slate-500 uppercase">{{ auth.roleLabel }}</div>
          </div>
          <ChevronDownIcon class="w-4 h-4 text-slate-400" />
        </button>

        <transition name="pop">
          <div v-if="menuOpen" class="absolute right-0 mt-2 w-56 card p-2">
            <div class="px-3 py-2 border-b border-slate-100 mb-1">
              <div class="text-sm font-semibold">{{ auth.user?.name }}</div>
              <div class="text-xs text-slate-500">{{ auth.user?.email }}</div>
            </div>
            <router-link to="/profile" class="menu-item" @click="menuOpen = false">
              <UserCircleIcon class="w-4 h-4" /> My profile
            </router-link>
            <router-link to="/dashboard" class="menu-item" @click="menuOpen = false">
              <HomeIcon class="w-4 h-4" /> Dashboard
            </router-link>
            <button class="menu-item text-rose-600 w-full" @click="logout">
              <ArrowRightOnRectangleIcon class="w-4 h-4" /> Sign out
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import {
  Bars3Icon, MagnifyingGlassIcon, BellIcon, PlusIcon, ChevronDownIcon,
  UserCircleIcon, HomeIcon, ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

defineEmits(['toggle-sidebar']);

const router = useRouter();
const auth = useAuthStore();
const search = ref('');
const menuOpen = ref(false);

const initials = computed(() => (auth.user?.name || '?').split(' ').map(s => s[0]).join('').slice(0, 2).toUpperCase());

const goSearch = () => router.push({ path: '/leads', query: { q: search.value } });

const logout = async () => {
  await auth.logout();
  router.push('/auth/login');
};
</script>

<script>
export default {
  directives: {
    'click-outside': {
      mounted(el, binding) {
        el.__co__ = (e) => { if (!el.contains(e.target)) binding.value(); };
        document.addEventListener('click', el.__co__);
      },
      unmounted(el) { document.removeEventListener('click', el.__co__); },
    },
  },
};
</script>

<style scoped>
.menu-item { @apply w-full flex items-center gap-2 px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-100 text-left; }
.pop-enter-active, .pop-leave-active { transition: all .12s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
