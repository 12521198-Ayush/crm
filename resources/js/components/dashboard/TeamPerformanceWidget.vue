<template>
  <ChartCard
    title="Team Performance"
    subtitle="Volume, conversion & efficiency"
    :icon="UserGroupIcon"
    :loading="loading"
    :empty="!loading && !rows.length"
    :delay="delay"
  >
    <div class="space-y-3">
      <div v-for="t in rows" :key="t.team_id" class="rounded-xl bg-surface-100 p-3.5">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-slate-800">{{ t.team }}</span>
          <span class="chip">Efficiency {{ t.efficiency }}</span>
        </div>
        <div class="grid grid-cols-3 gap-3 text-center">
          <div>
            <div class="text-lg font-bold text-slate-900">{{ t.volume }}</div>
            <div class="text-2xs uppercase tracking-wide text-slate-400">Leads</div>
          </div>
          <div>
            <div class="text-lg font-bold text-success-600">{{ t.converted }}</div>
            <div class="text-2xs uppercase tracking-wide text-slate-400">Converted</div>
          </div>
          <div>
            <div class="text-lg font-bold text-brand-600">{{ t.conversion }}%</div>
            <div class="text-2xs uppercase tracking-wide text-slate-400">Conv. Rate</div>
          </div>
        </div>
        <div class="h-1.5 rounded-full bg-surface-200 overflow-hidden mt-3">
          <div class="h-full bg-brand-gradient rounded-full transition-all duration-700" :style="{ width: Math.min(100, t.efficiency) + '%' }" />
        </div>
      </div>
    </div>
  </ChartCard>
</template>

<script setup>
import ChartCard from '../charts/ChartCard.vue';
import { UserGroupIcon } from '@heroicons/vue/24/outline';

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
</script>
