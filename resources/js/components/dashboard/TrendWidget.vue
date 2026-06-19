<template>
  <ChartCard
    title="Lead Trend"
    subtitle="Created vs converted over time"
    :icon="ArrowTrendingUpIcon"
    :loading="loading"
    :empty="!loading && !hasData"
    :delay="delay"
  >
    <template #actions>
      <div class="flex items-center gap-0.5 p-0.5 rounded-lg bg-surface-200">
        <button
          v-for="g in granularities"
          :key="g.value"
          class="px-2.5 py-1 rounded-md text-2xs font-semibold transition"
          :class="modelValue === g.value ? 'bg-white text-brand-700 shadow-soft' : 'text-slate-500 hover:text-slate-700'"
          @click="$emit('update:modelValue', g.value)"
        >{{ g.label }}</button>
      </div>
    </template>

    <div class="h-64">
      <LineChart :labels="trend?.labels || []" :datasets="trend?.series || []" />
    </div>
  </ChartCard>
</template>

<script setup>
import { computed } from 'vue';
import ChartCard from '../charts/ChartCard.vue';
import LineChart from '../charts/LineChart.vue';
import { ArrowTrendingUpIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  trend: { type: Object, default: null },
  modelValue: { type: String, default: 'day' },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['update:modelValue']);

const granularities = [
  { label: 'Day', value: 'day' },
  { label: 'Week', value: 'week' },
  { label: 'Month', value: 'month' },
  { label: 'Quarter', value: 'quarter' },
  { label: 'Year', value: 'year' },
];

const hasData = computed(() => (props.trend?.labels || []).length > 0);
</script>
