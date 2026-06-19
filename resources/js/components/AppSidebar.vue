<template>
  <!-- Mobile backdrop -->
  <div
    v-if="open"
    class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
    @click="$emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 z-40 w-64 bg-white/80 backdrop-blur-xl transform transition-transform duration-300 ease-spring lg:translate-x-0 shadow-[1px_0_0_rgba(15,23,42,0.04)]"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="h-16 flex items-center gap-3 px-5">
      <div class="w-10 h-10 rounded-xl bg-brand-gradient text-white grid place-items-center font-bold shadow-glow">N</div>
      <div>
        <div class="font-bold text-slate-900 leading-tight">Ninja CRM</div>
        <div class="text-2xs uppercase tracking-wider text-slate-400">{{ auth.roleLabel }}</div>
      </div>
    </div>

    <nav class="p-3 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">
      <template v-for="group in groups" :key="group.label">
        <div class="px-3 pt-4 pb-1 text-2xs font-semibold uppercase tracking-wider text-slate-400">
          {{ group.label }}
        </div>
        <router-link
          v-for="item in group.items.filter(visible)"
          :key="item.to"
          :to="item.to"
          class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-brand-50 hover:text-brand-700"
          active-class="bg-brand-gradient !text-white shadow-glow"
          @click="$emit('close')"
        >
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span>{{ item.label }}</span>
          <span v-if="item.badge" class="ml-auto badge bg-brand-100 text-brand-700">{{ item.badge }}</span>
        </router-link>
      </template>
    </nav>
  </aside>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import {
  HomeIcon, UsersIcon, UserGroupIcon, BuildingOffice2Icon,
  TagIcon, Squares2X2Icon, AdjustmentsHorizontalIcon, UserCircleIcon,
} from '@heroicons/vue/24/outline';

defineProps({ open: Boolean });
defineEmits(['close']);

const auth = useAuthStore();

const visible = (item) => !item.roles || item.roles.includes(auth.user?.role);

const groups = [
  {
    label: 'Workspace',
    items: [
      { to: '/dashboard', label: 'Dashboard', icon: HomeIcon },
      { to: '/leads',     label: 'Leads',     icon: Squares2X2Icon },
    ],
  },
  {
    label: 'Management',
    items: [
      { to: '/projects', label: 'Projects', icon: BuildingOffice2Icon, roles: ['master', 'sub_master'] },
      { to: '/statuses', label: 'Lead Statuses', icon: TagIcon, roles: ['master', 'sub_master'] },
      { to: '/sources',  label: 'Lead Sources',  icon: AdjustmentsHorizontalIcon, roles: ['master'] },
      { to: '/users',    label: 'Team / Users',  icon: UsersIcon, roles: ['master', 'sub_master'] },
    ],
  },
  {
    label: 'Account',
    items: [
      { to: '/profile', label: 'My Profile', icon: UserCircleIcon },
    ],
  },
];
</script>
