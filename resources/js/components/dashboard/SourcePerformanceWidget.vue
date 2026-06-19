<template>
  <ChartCard
    title="Lead Source Performance"
    :subtitle="best ? `Best: ${best.source} (${best.conversion}% conversion)` : 'Conversion by source'"
    :icon="MegaphoneIcon"
    :loading="loading"
    :empty="!loading && !rows.length"
    :delay="delay"
  >
    <div class="overflow-x-auto -mx-2">
      <table class="table-base">
        <thead>
          <tr>
            <th>Source</th>
            <th class="text-right">Leads</th>
            <th class="text-right">Conv.</th>
            <th class="text-right">Revenue</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in rows" :key="s.source_id" class="cursor-pointer" @click="$emit('drill', s.query)">
            <td>
              <div class="flex items-center gap-2">
                <span class="badge bg-brand-100 text-brand-700">{{ s.source }}</span>
              </div>
            </td>
            <td class="text-right font-semibold">{{ s.total.toLocaleString() }}</td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-2">
                <div class="hidden sm:block w-14 h-1.5 rounded-full bg-surface-200 overflow-hidden">
                  <div class="h-full bg-success-gradient rounded-full" :style="{ width: Math.min(100, s.conversion) + '%' }" />
                </div>
                <span class="font-semibold text-success-600 w-12 text-right">{{ s.conversion }}%</span>
              </div>
            </td>
            <td class="text-right text-slate-600">{{ money(s.revenue) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </ChartCard>
</template>

<script setup>
import ChartCard from '../charts/ChartCard.vue';
import { MegaphoneIcon } from '@heroicons/vue/24/outline';

defineProps({
  rows: { type: Array, default: () => [] },
  best: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  delay: { type: Number, default: 0 },
});
defineEmits(['drill']);

const money = (n) => {
  const v = Number(n) || 0;
  if (v >= 1e7) return '₹' + (v / 1e7).toFixed(1) + 'Cr';
  if (v >= 1e5) return '₹' + (v / 1e5).toFixed(1) + 'L';
  if (v >= 1e3) return '₹' + (v / 1e3).toFixed(1) + 'K';
  return '₹' + v.toLocaleString();
};
</script>
