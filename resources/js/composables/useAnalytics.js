import { ref, reactive, watch } from 'vue';
import axios from 'axios';

/**
 * Reactive analytics fetcher reused by the dashboard and (later) report screens.
 * Pass an endpoint under /api/analytics and a shared filters object; the data
 * refetches whenever the filters change.
 *
 * @param {string} endpoint  e.g. 'dashboard', 'funnel', 'agents'
 * @param {object} filters   reactive shared filter state
 * @param {object} opts      { immediate, extra }
 */
export function useAnalytics(endpoint, filters, opts = {}) {
  const data = ref(opts.initial ?? null);
  const loading = ref(false);
  const error = ref(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const params = { ...toParams(filters), ...(opts.extra || {}) };
      const { data: res } = await axios.get(`/api/analytics/${endpoint}`, { params });
      data.value = res;
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load analytics';
    } finally {
      loading.value = false;
    }
  }

  if (opts.immediate !== false) load();
  if (filters) watch(() => toParams(filters), load, { deep: true });

  return { data, loading, error, reload: load };
}

function toParams(filters) {
  if (!filters) return {};
  return Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== null && v !== undefined && v !== '')
  );
}

/** Shared, app-wide default filter state for analytics screens. */
export function createAnalyticsFilters(overrides = {}) {
  return reactive({
    period: 'month',
    granularity: 'day',
    project_id: null,
    source_id: null,
    assigned_to: null,
    ...overrides,
  });
}
