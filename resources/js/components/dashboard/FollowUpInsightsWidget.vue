<template>
  <ChartCard title="Follow-up Insights" :icon="BellAlertIcon" :loading="loading" :delay="delay">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <button
        v-for="m in metrics"
        :key="m.key"
        class="rounded-xl p-3.5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card"
        :class="m.bg"
        @click="$emit('drill', m.query)"
      >
        <component :is="m.icon" class="w-5 h-5 mb-2" :class="m.fg" />
        <div class="text-xl font-bold" :class="m.fg">
          <AnimatedCounter :value="m.count" />
        </div>
        <div class="text-2xs font-semibold uppercase tracking-wide text-slate-500 mt-0.5">{{ m.label }}</div>
      </button>
    </div>
  </ChartCard>
</template>

<script setup>
import { computed } from 'vue';
import ChartCard from '../charts/ChartCard.vue';
import AnimatedCounter from '../ui/AnimatedCounter.vue';
import { BellAlertIcon, CalendarDaysIcon, ExclamationTriangleIcon, ArrowUpRightIcon, XCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  data: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['drill']);

const metrics = computed(() => {
  const d = props.data || {};
  return [
    { key: 'due_today', label: 'Due Today', icon: CalendarDaysIcon, bg: 'bg-info-50', fg: 'text-info-700', count: d.due_today?.count || 0, query: d.due_today?.query },
    { key: 'overdue', label: 'Overdue', icon: ExclamationTriangleIcon, bg: 'bg-danger-50', fg: 'text-danger-700', count: d.overdue?.count || 0, query: d.overdue?.query },
    { key: 'upcoming', label: 'Upcoming', icon: ArrowUpRightIcon, bg: 'bg-brand-50', fg: 'text-brand-700', count: d.upcoming?.count || 0, query: d.upcoming?.query },
    { key: 'missed', label: 'Missed', icon: XCircleIcon, bg: 'bg-warning-50', fg: 'text-warning-700', count: d.missed?.count || 0, query: d.missed?.query },
    { key: 'completed', label: 'Completed', icon: CheckCircleIcon, bg: 'bg-success-50', fg: 'text-success-700', count: d.completed?.count || 0, query: d.completed?.query },
  ];
});
</script>
