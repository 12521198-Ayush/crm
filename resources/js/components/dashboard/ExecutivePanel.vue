<template>
  <BaseCard :delay="delay" pad class="bg-brand-gradient text-white overflow-hidden relative">
    <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-white/10 blur-2xl" />
    <div class="relative">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-sm font-bold flex items-center gap-2">
            <BuildingOffice2Icon class="w-4 h-4" /> Executive Overview
          </h3>
          <p class="text-2xs text-white/70 mt-0.5">Organisation-wide metrics</p>
        </div>
        <span v-if="data?.growth" class="badge bg-white/15 text-white">
          <ArrowTrendingUpIcon class="w-3 h-3" />
          {{ data.growth.trend_pct ?? 0 }}% MoM
        </span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div v-for="kpi in kpis" :key="kpi.label">
          <div class="text-2xl font-bold leading-tight">
            <AnimatedCounter :value="kpi.value" :prefix="kpi.prefix || ''" />
          </div>
          <div class="text-2xs uppercase tracking-wide text-white/70 mt-1">{{ kpi.label }}</div>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed } from 'vue';
import BaseCard from '../ui/BaseCard.vue';
import AnimatedCounter from '../ui/AnimatedCounter.vue';
import { BuildingOffice2Icon, ArrowTrendingUpIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  data: { type: Object, default: null },
  delay: { type: Number, default: 0 },
});

const kpis = computed(() => {
  const d = props.data || {};
  const revenue = Number(d.total_revenue || 0);
  const revShort = revenue >= 1e5 ? Math.round(revenue / 1e5) : revenue; // lakhs
  return [
    { label: 'Total Clients', value: d.total_clients || 0 },
    { label: 'Total Users', value: d.total_users || 0 },
    { label: 'Total Leads', value: d.total_leads || 0 },
    { label: 'Revenue (₹L)', value: revShort, prefix: '₹' },
    { label: 'Active Subs', value: d.active_subscriptions || 0 },
    { label: 'Expiring (30d)', value: d.expiring_accounts || 0 },
  ];
});
</script>
