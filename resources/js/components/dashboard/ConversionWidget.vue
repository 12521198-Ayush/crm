<template>
  <ChartCard
    title="Conversion Rate"
    :subtitle="`By ${by}`"
    :icon="ChartPieIcon"
    :loading="loading"
    :empty="!loading && !rows.length"
    :delay="delay"
  >
    <template #actions>
      <div class="flex items-center gap-0.5 p-0.5 rounded-lg bg-surface-200">
        <button
          v-for="opt in options"
          :key="opt"
          class="px-2.5 py-1 rounded-md text-2xs font-semibold capitalize transition"
          :class="by === opt ? 'bg-white text-brand-700 shadow-soft' : 'text-slate-500 hover:text-slate-700'"
          @click="by = opt"
        >{{ opt }}</button>
      </div>
    </template>

    <div class="h-64">
      <BarChart
        :labels="labels"
        :datasets="[{ label: 'Conversion %', data: values }]"
        horizontal
        color-per-bar
        :options="{ scales: { x: { max: 100, ticks: { callback: v => v + '%' } } } }"
      />
    </div>
  </ChartCard>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ChartCard from '../charts/ChartCard.vue';
import BarChart from '../charts/BarChart.vue';
import { ChartPieIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  filters: { type: Object, required: true },
  delay: { type: Number, default: 0 },
});

const options = ['source', 'agent', 'team'];
const by = ref('source');
const rows = ref([]);
const loading = ref(false);

const labels = computed(() => rows.value.map(r => r.label));
const values = computed(() => rows.value.map(r => r.conversion));

async function load() {
  loading.value = true;
  try {
    const params = { ...cleanFilters(), by: by.value };
    const { data } = await axios.get('/api/analytics/conversion', { params });
    rows.value = data.rows || [];
  } finally {
    loading.value = false;
  }
}

function cleanFilters() {
  return Object.fromEntries(Object.entries(props.filters).filter(([, v]) => v !== null && v !== undefined && v !== ''));
}

watch(by, load);
watch(() => cleanFilters(), load, { deep: true });
load();
</script>
