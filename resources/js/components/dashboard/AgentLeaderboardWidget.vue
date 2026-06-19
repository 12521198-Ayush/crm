<template>
  <ChartCard
    title="Performance Leaderboard"
    subtitle="Top agents by conversions"
    :icon="TrophyIcon"
    :loading="loading"
    :empty="!loading && !rows.length"
    :delay="delay"
  >
    <div class="space-y-1.5">
      <button
        v-for="a in rows"
        :key="a.agent_id"
        class="w-full flex items-center gap-3 px-2.5 py-2.5 rounded-xl transition-all duration-200 hover:bg-brand-50/60"
        @click="$emit('drill', a.query)"
      >
        <span class="w-7 h-7 rounded-lg grid place-items-center text-xs font-bold shrink-0" :class="medal(a.rank)">
          {{ a.rank }}
        </span>
        <div class="w-8 h-8 rounded-full bg-brand-gradient text-white grid place-items-center text-2xs font-semibold shrink-0">
          {{ initials(a.agent) }}
        </div>
        <div class="min-w-0 flex-1 text-left">
          <div class="text-sm font-semibold text-slate-800 truncate">{{ a.agent }}</div>
          <div class="text-2xs text-slate-400">{{ a.assigned }} leads · {{ a.meetings }} meetings · {{ a.follow_ups }} follow-ups</div>
        </div>
        <div class="text-right shrink-0">
          <div class="text-sm font-bold text-success-600">{{ a.conversion }}%</div>
          <div class="text-2xs text-slate-400">{{ a.converted }} won</div>
        </div>
        <div class="hidden sm:block w-16 shrink-0">
          <div class="h-1.5 rounded-full bg-surface-200 overflow-hidden">
            <div class="h-full bg-brand-gradient rounded-full transition-all duration-700" :style="{ width: Math.min(100, a.conversion) + '%' }" />
          </div>
        </div>
      </button>
    </div>
  </ChartCard>
</template>

<script setup>
import ChartCard from '../charts/ChartCard.vue';
import { TrophyIcon } from '@heroicons/vue/24/outline';

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['drill']);

const initials = (n) => (n || '?').split(' ').map(s => s[0]).join('').slice(0, 2).toUpperCase();
const medal = (rank) => {
  if (rank === 1) return 'bg-warning-100 text-warning-700';
  if (rank === 2) return 'bg-slate-200 text-slate-600';
  if (rank === 3) return 'bg-orange-100 text-orange-700';
  return 'bg-surface-200 text-slate-500';
};
</script>
