<template>
  <div class="space-y-6">
    <!-- Header + global filters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-500">
          Welcome back, {{ auth.user?.name }} — your pipeline at a glance
        </p>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-0.5 p-0.5 rounded-xl bg-white shadow-soft">
          <button
            v-for="p in periods"
            :key="p.value"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
            :class="filters.period === p.value ? 'bg-brand-gradient text-white shadow-glow' : 'text-slate-500 hover:text-slate-700'"
            @click="filters.period = p.value"
          >{{ p.label }}</button>
        </div>
      </div>
    </div>

    <!-- Registry-driven widget grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
      <template v-for="w in widgets" :key="w.key">
        <!-- Executive -->
        <div v-if="w.key === 'executive' && data?.executive" :class="span(w)">
          <ExecutivePanel :data="data.executive" :delay="delay(w)" />
        </div>

        <!-- Summary cards -->
        <div v-else-if="w.key === 'summary'" :class="span(w)">
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
            <template v-if="loading">
              <SkeletonLoader v-for="n in 8" :key="n" variant="card" />
            </template>
            <StatCard
              v-else
              v-for="(c, i) in data?.cards || []"
              :key="c.key"
              :label="c.label"
              :value="c.value"
              :icon="c.icon"
              :tone="c.tone"
              :trend-pct="c.trend_pct"
              :invert="c.invert"
              :compared-to="c.compared_to"
              :delay="i * 40"
              :to="{ name: 'leads', query: c.query }"
            />
          </div>
        </div>

        <div v-else-if="w.key === 'trend'" :class="span(w)">
          <TrendWidget :trend="data?.trend" v-model="filters.granularity" :loading="loading" :delay="delay(w)" />
        </div>

        <div v-else-if="w.key === 'funnel'" :class="span(w)">
          <LeadFunnelWidget :funnel="data?.funnel" :loading="loading" :delay="delay(w)" @drill="goLeads" />
        </div>

        <div v-else-if="w.key === 'followups'" :class="span(w)">
          <FollowUpInsightsWidget :data="data?.follow_ups" :loading="loading" :delay="delay(w)" @drill="goLeads" />
        </div>

        <div v-else-if="w.key === 'sources'" :class="span(w)">
          <SourcePerformanceWidget :rows="data?.sources?.rows || []" :best="data?.sources?.best_source" :loading="loading" :delay="delay(w)" @drill="goLeads" />
        </div>

        <div v-else-if="w.key === 'conversion'" :class="span(w)">
          <ConversionWidget :filters="filters" :delay="delay(w)" />
        </div>

        <div v-else-if="w.key === 'leaderboard'" :class="span(w)">
          <AgentLeaderboardWidget :rows="data?.agents || []" :loading="loading" :delay="delay(w)" @drill="goLeads" />
        </div>

        <div v-else-if="w.key === 'aging'" :class="span(w)">
          <LeadAgingWidget :buckets="data?.aging || []" :loading="loading" :delay="delay(w)" @drill="goLeads" />
        </div>

        <div v-else-if="w.key === 'teams'" :class="span(w)">
          <TeamPerformanceWidget :rows="data?.teams || []" :loading="loading" :delay="delay(w)" />
        </div>

        <div v-else-if="w.key === 'activity'" :class="span(w)">
          <ActivityTimelineWidget :rows="data?.activity || []" :loading="loading" :delay="delay(w)" />
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { widgetsForRole, SPAN_CLASS } from '../config/dashboardWidgets';

import StatCard from '../components/StatCard.vue';
import SkeletonLoader from '../components/ui/SkeletonLoader.vue';
import ExecutivePanel from '../components/dashboard/ExecutivePanel.vue';
import TrendWidget from '../components/dashboard/TrendWidget.vue';
import LeadFunnelWidget from '../components/dashboard/LeadFunnelWidget.vue';
import FollowUpInsightsWidget from '../components/dashboard/FollowUpInsightsWidget.vue';
import SourcePerformanceWidget from '../components/dashboard/SourcePerformanceWidget.vue';
import ConversionWidget from '../components/dashboard/ConversionWidget.vue';
import AgentLeaderboardWidget from '../components/dashboard/AgentLeaderboardWidget.vue';
import LeadAgingWidget from '../components/dashboard/LeadAgingWidget.vue';
import TeamPerformanceWidget from '../components/dashboard/TeamPerformanceWidget.vue';
import ActivityTimelineWidget from '../components/dashboard/ActivityTimelineWidget.vue';

const auth = useAuthStore();
const router = useRouter();

const periods = [
  { label: 'Today', value: 'today' },
  { label: 'Week', value: 'week' },
  { label: 'Month', value: 'month' },
  { label: 'Quarter', value: 'quarter' },
  { label: 'Year', value: 'year' },
  { label: 'All', value: 'all' },
];

const filters = ref({ period: 'month', granularity: 'day' });
const data = ref(null);
const loading = ref(true);

const widgets = computed(() => widgetsForRole(auth.user?.role));
const span = (w) => SPAN_CLASS[w.span] || 'lg:col-span-12';
const delay = (w) => widgets.value.indexOf(w) * 60;

const goLeads = (query) => router.push({ name: 'leads', query: { ...query } });

async function load() {
  loading.value = true;
  try {
    const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v != null && v !== ''));
    const { data: res } = await axios.get('/api/analytics/dashboard', { params });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

watch(filters, load, { deep: true });
load();
</script>
