<template>
  <ChartCard
    title="Lead Aging Analysis"
    subtitle="Open leads by time since creation"
    :icon="ClockIcon"
    :loading="loading"
    :empty="!loading && total === 0"
    :delay="delay"
  >
    <div class="space-y-3">
      <button
        v-for="(b, i) in buckets"
        :key="b.key"
        class="w-full flex items-center gap-3 group/age"
        @click="$emit('drill', b.query)"
      >
        <span class="w-20 text-xs font-semibold text-slate-500 text-left shrink-0">{{ b.label }}</span>
        <div class="flex-1 h-7 rounded-lg bg-surface-200 overflow-hidden">
          <div class="h-full rounded-lg transition-all duration-700 ease-spring" :style="{ width: width(b) + '%', background: color(i) }" />
        </div>
        <span class="w-10 text-sm font-bold text-slate-900 text-right shrink-0">{{ b.count }}</span>
      </button>
    </div>
  </ChartCard>
</template>

<script setup>
import { computed } from 'vue';
import ChartCard from '../charts/ChartCard.vue';
import { ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  buckets: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['drill']);

const total = computed(() => props.buckets.reduce((s, b) => s + (b.count || 0), 0));
const max = computed(() => Math.max(1, ...props.buckets.map(b => b.count || 0)));
const width = (b) => Math.max(b.count ? 4 : 0, Math.round(((b.count || 0) / max.value) * 100));
// fresh → aging gradient: green to red
const colors = ['#10b981', '#84cc16', '#f59e0b', '#f97316', '#ef4444'];
const color = (i) => colors[i % colors.length];
</script>
