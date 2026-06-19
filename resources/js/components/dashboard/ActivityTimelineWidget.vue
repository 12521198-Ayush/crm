<template>
  <ChartCard
    title="Activity Timeline"
    subtitle="Latest team activity"
    :icon="BoltIcon"
    :loading="loading"
    :empty="!loading && !rows.length"
    :delay="delay"
  >
    <div class="relative pl-2">
      <div class="absolute left-[1.05rem] top-1 bottom-1 w-px bg-slate-100" />
      <div class="space-y-4">
        <router-link
          v-for="a in rows"
          :key="a.id"
          :to="`/leads/${a.lead_id}`"
          class="relative flex gap-3 group"
        >
          <span class="relative z-10 w-6 h-6 rounded-full grid place-items-center shrink-0 ring-4 ring-white" :class="meta(a.type).bg">
            <component :is="meta(a.type).icon" class="w-3.5 h-3.5" :class="meta(a.type).fg" />
          </span>
          <div class="min-w-0 -mt-0.5 pb-1">
            <p class="text-sm text-slate-700 leading-snug">
              <span class="font-semibold">{{ a.user || 'System' }}</span>
              {{ verb(a.type) }}
              <span class="font-medium text-brand-600 group-hover:underline">{{ a.lead || 'a lead' }}</span>
            </p>
            <p v-if="a.title" class="text-2xs text-slate-400 truncate">{{ a.title }}</p>
            <p class="text-2xs text-slate-400">{{ ago(a.at) }}</p>
          </div>
        </router-link>
      </div>
    </div>
  </ChartCard>
</template>

<script setup>
import ChartCard from '../charts/ChartCard.vue';
import {
  BoltIcon, PhoneIcon, ChatBubbleLeftRightIcon, CalendarDaysIcon,
  ArrowPathIcon, UserPlusIcon, PencilSquareIcon, FlagIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});

const types = {
  call:          { icon: PhoneIcon, bg: 'bg-info-100', fg: 'text-info-700' },
  whatsapp:      { icon: ChatBubbleLeftRightIcon, bg: 'bg-success-100', fg: 'text-success-700' },
  meeting:       { icon: CalendarDaysIcon, bg: 'bg-cyan-100', fg: 'text-cyan-700' },
  follow_up:     { icon: FlagIcon, bg: 'bg-warning-100', fg: 'text-warning-700' },
  status_change: { icon: ArrowPathIcon, bg: 'bg-brand-100', fg: 'text-brand-700' },
  assignment:    { icon: UserPlusIcon, bg: 'bg-indigo-100', fg: 'text-indigo-700' },
  note:          { icon: PencilSquareIcon, bg: 'bg-slate-100', fg: 'text-slate-600' },
  event:         { icon: BoltIcon, bg: 'bg-slate-100', fg: 'text-slate-600' },
};
const meta = (t) => types[t] || types.note;
const verb = (t) => ({
  call: 'logged a call on', whatsapp: 'messaged', meeting: 'scheduled a meeting for',
  follow_up: 'set a follow-up on', status_change: 'updated status of',
  assignment: 'assigned', note: 'added a note to', event: 'recorded an event on',
}[t] || 'updated');

const ago = (iso) => {
  if (!iso) return '';
  const d = (Date.now() - new Date(iso).getTime()) / 1000;
  if (d < 60) return 'just now';
  if (d < 3600) return Math.floor(d / 60) + 'm ago';
  if (d < 86400) return Math.floor(d / 3600) + 'h ago';
  return Math.floor(d / 86400) + 'd ago';
};
</script>
