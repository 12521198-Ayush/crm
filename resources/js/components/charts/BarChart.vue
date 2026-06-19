<template>
  <Bar :data="chartData" :options="mergedOptions" />
</template>

<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import '../../charts/setup';
import { series as palette } from '../../charts/setup';

const props = defineProps({
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] }, // [{ label, data }]
  horizontal: { type: Boolean, default: false },
  options: { type: Object, default: () => ({}) },
  colorPerBar: { type: Boolean, default: false },
});
const emit = defineEmits(['bar-click']);

const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets.map((ds, i) => ({
    label: ds.label,
    data: ds.data,
    backgroundColor: props.colorPerBar
      ? props.labels.map((_, j) => palette[j % palette.length])
      : palette[i % palette.length],
    borderRadius: 8,
    borderSkipped: false,
    maxBarThickness: 46,
  })),
}));

const mergedOptions = computed(() => ({
  responsive: true,
  indexAxis: props.horizontal ? 'y' : 'x',
  plugins: { legend: { display: props.datasets.length > 1, position: 'top', align: 'end' } },
  scales: {
    x: { grid: { display: props.horizontal, color: 'rgba(148,163,184,0.15)' }, ticks: { autoSkip: true, maxRotation: 0 }, beginAtZero: true },
    y: { grid: { display: !props.horizontal, color: 'rgba(148,163,184,0.15)' }, ticks: { precision: 0 }, beginAtZero: true },
  },
  onClick: (e, els) => { if (els.length) emit('bar-click', els[0].index); },
  ...props.options,
}));
</script>
