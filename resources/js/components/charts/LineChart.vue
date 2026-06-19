<template>
  <Line :data="chartData" :options="mergedOptions" />
</template>

<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import '../../charts/setup';
import { series as palette } from '../../charts/setup';

const props = defineProps({
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] }, // [{ label, data, key }]
  options: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['point-click']);

const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets.map((ds, i) => ({
    label: ds.label,
    data: ds.data,
    borderColor: palette[i % palette.length],
    backgroundColor: hexToRgba(palette[i % palette.length], 0.12),
    fill: true,
    tension: 0.4,
    borderWidth: 2.5,
    pointRadius: 0,
    pointHoverRadius: 5,
    pointBackgroundColor: palette[i % palette.length],
  })),
}));

const mergedOptions = computed(() => ({
  responsive: true,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { display: props.datasets.length > 1, position: 'top', align: 'end' } },
  scales: {
    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
    y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.15)' }, ticks: { precision: 0 } },
  },
  onClick: (e, els) => { if (els.length) emit('point-click', els[0].index); },
  ...props.options,
}));

function hexToRgba(hex, a) {
  const n = parseInt(hex.slice(1), 16);
  return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
}
</script>
