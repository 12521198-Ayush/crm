<template>
  <!-- Card skeleton -->
  <div v-if="variant === 'card'" class="card card-pad">
    <div class="flex items-start justify-between">
      <div class="space-y-2 flex-1">
        <div class="skeleton h-3 w-24" />
        <div class="skeleton h-7 w-16" />
        <div class="skeleton h-3 w-20" />
      </div>
      <div class="skeleton h-11 w-11 rounded-xl" />
    </div>
  </div>

  <!-- Chart skeleton -->
  <div v-else-if="variant === 'chart'" class="card card-pad">
    <div class="skeleton h-4 w-32 mb-4" />
    <div class="flex items-end gap-2 h-40">
      <div v-for="n in 9" :key="n" class="skeleton flex-1 rounded-t-lg" :style="{ height: bars[n % bars.length] }" />
    </div>
  </div>

  <!-- Table rows skeleton -->
  <div v-else-if="variant === 'table'" class="space-y-2">
    <div v-for="n in (rows || 5)" :key="n" class="skeleton h-11 w-full" />
  </div>

  <!-- List rows skeleton -->
  <div v-else-if="variant === 'list'" class="space-y-3">
    <div v-for="n in (rows || 4)" :key="n" class="flex items-center gap-3">
      <div class="skeleton h-9 w-9 rounded-full" />
      <div class="flex-1 space-y-1.5">
        <div class="skeleton h-3 w-1/3" />
        <div class="skeleton h-3 w-1/2" />
      </div>
    </div>
  </div>

  <!-- Plain block -->
  <div v-else class="skeleton" :class="block || 'h-4 w-full'" />
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'text' }, // card | chart | table | list | text
  rows: { type: Number, default: 0 },
  block: { type: String, default: '' },
});
const bars = ['55%', '80%', '40%', '95%', '65%', '75%', '50%', '88%', '60%'];
</script>
