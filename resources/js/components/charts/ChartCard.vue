<template>
  <BaseCard :delay="delay" class="flex flex-col">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div class="min-w-0">
        <h3 class="widget-title flex items-center gap-2">
          <component v-if="icon" :is="icon" class="w-4 h-4 text-brand-500" />
          {{ title }}
        </h3>
        <p v-if="subtitle" class="widget-sub mt-0.5">{{ subtitle }}</p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </div>

    <div class="flex-1 min-h-0">
      <SkeletonLoader v-if="loading" variant="chart" class="!shadow-none !p-0" />
      <div v-else-if="empty" class="h-full grid place-items-center text-center py-8">
        <div>
          <div class="text-slate-300 mb-1"><InboxIcon class="w-8 h-8 mx-auto" /></div>
          <p class="text-sm text-slate-400">{{ emptyText }}</p>
        </div>
      </div>
      <slot v-else />
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from '../ui/BaseCard.vue';
import SkeletonLoader from '../ui/SkeletonLoader.vue';
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
  title: String,
  subtitle: String,
  icon: { type: [Object, Function], default: null },
  loading: { type: Boolean, default: false },
  empty: { type: Boolean, default: false },
  emptyText: { type: String, default: 'No data for this period yet.' },
  delay: { type: Number, default: 0 },
});
</script>
