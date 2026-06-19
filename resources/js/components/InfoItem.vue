<template>
  <div>
    <div class="label">{{ label }}</div>
    <div class="text-sm text-slate-800">{{ value || '—' }}</div>
  </div>
</template>

<script setup>
defineProps({ label: String, value: [String, Number] });
</script>
