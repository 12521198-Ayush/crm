<template>
  <span v-if="value !== null && value !== undefined" class="badge" :class="toneClass" :title="tooltip">
    <component :is="icon" class="w-3 h-3" />
    {{ Math.abs(value).toFixed(1) }}%
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon, MinusSmallIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  value: { type: Number, default: null },     // signed percentage change
  // when true, a decrease is "good" (e.g. Not Interested, Dropped, Overdue)
  invert: { type: Boolean, default: false },
  comparedTo: { type: String, default: 'previous period' },
});

const isUp = computed(() => props.value > 0);
const isFlat = computed(() => Math.abs(props.value) < 0.05);
const good = computed(() => props.invert ? props.value < 0 : props.value > 0);

const icon = computed(() => isFlat.value ? MinusSmallIcon : (isUp.value ? ArrowTrendingUpIcon : ArrowTrendingDownIcon));
const toneClass = computed(() => {
  if (isFlat.value) return 'bg-slate-100 text-slate-500';
  return good.value ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700';
});
const tooltip = computed(() => `${props.value > 0 ? '+' : ''}${props.value}% vs ${props.comparedTo}`);
</script>
