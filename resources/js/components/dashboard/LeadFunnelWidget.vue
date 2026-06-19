<template>
  <ChartCard
    title="Lead Funnel"
    :subtitle="funnel ? `${funnel.overall_conversion}% overall conversion · ${funnel.entered} entered` : ''"
    :icon="FunnelIcon"
    :loading="loading"
    :empty="!loading && (!funnel || !funnel.entered)"
    :delay="delay"
  >
    <div class="space-y-2.5">
      <button
        v-for="(stage, i) in funnel?.stages || []"
        :key="stage.key"
        class="w-full text-left group/stage"
        @click="$emit('drill', { status_slug: stage.slugs.join(',') })"
      >
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md grid place-items-center text-2xs font-bold text-white" :style="{ background: color(i) }">{{ i + 1 }}</span>
            {{ stage.label }}
          </span>
          <span class="text-sm font-bold text-slate-900">{{ stage.reached.toLocaleString() }}</span>
        </div>
        <div class="relative h-8 rounded-lg bg-surface-200 overflow-hidden">
          <div
            class="h-full rounded-lg transition-all duration-700 ease-spring flex items-center px-2"
            :style="{ width: barWidth(stage) + '%', background: color(i) }"
          >
            <span class="text-2xs font-semibold text-white/90">{{ stage.pct_of_total }}%</span>
          </div>
        </div>
        <div class="flex items-center justify-between mt-1 text-2xs">
          <span class="text-success-600 font-medium" v-if="i > 0">▲ {{ stage.conversion }}% from {{ funnel.stages[i-1].label }}</span>
          <span v-else class="text-slate-400">Pipeline entry</span>
          <span class="text-danger-500 font-medium" v-if="i > 0 && stage.drop_off > 0">▼ {{ stage.drop_off }}% drop-off</span>
        </div>
      </button>
    </div>
  </ChartCard>
</template>

<script setup>
import ChartCard from '../charts/ChartCard.vue';
import { FunnelIcon } from '@heroicons/vue/24/outline';
import { series } from '../../charts/setup';

const props = defineProps({
  funnel: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['drill']);

const color = (i) => series[i % series.length];
const barWidth = (stage) => Math.max(6, Number(stage.pct_of_total) || 0);
</script>
