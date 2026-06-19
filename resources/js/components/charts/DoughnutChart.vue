<template>
  <div class="relative h-full">
    <Doughnut :data="chartData" :options="mergedOptions" />
    <div v-if="centerLabel" class="absolute inset-0 grid place-items-center pointer-events-none">
      <div class="text-center">
        <div class="text-2xl font-bold text-slate-900">{{ centerValue }}</div>
        <div class="text-2xs uppercase tracking-wide text-slate-400">{{ centerLabel }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import '../../charts/setup';
import { series as defaultPalette } from '../../charts/setup';

const props = defineProps({
  labels: { type: Array, default: () => [] },
  data: { type: Array, default: () => [] },
  colors: { type: Array, default: null },
  centerLabel: { type: String, default: '' },
  centerValue: { type: [String, Number], default: '' },
  options: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['slice-click']);

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [{
    data: props.data,
    backgroundColor: props.colors || props.labels.map((_, i) => defaultPalette[i % defaultPalette.length]),
    borderWidth: 2,
    borderColor: '#fff',
    hoverOffset: 6,
  }],
}));

const mergedOptions = computed(() => ({
  responsive: true,
  cutout: '68%',
  plugins: { legend: { position: 'right', align: 'center' } },
  onClick: (e, els) => { if (els.length) emit('slice-click', els[0].index); },
  ...props.options,
}));
</script>
