<template>
  <SimpleCrud
    title="Lead Statuses"
    subtitle="Define your custom lead workflow. System statuses cannot be deleted."
    endpoint="/api/lead-statuses"
    :columns="[
      { key: 'name', label: 'Name' },
      { key: 'color', label: 'Color', color: true },
      { key: 'sort_order', label: 'Order' },
      { key: 'is_system', label: 'System', boolean: true },
    ]"
    :fields="[
      { key: 'name', label: 'Name', required: true },
      { key: 'color', label: 'Color', type: 'color', default: '#3b82f6' },
      { key: 'sort_order', label: 'Order', type: 'number', default: 10 },
    ]"
  />
</template>

<script setup>
import SimpleCrud from '../components/SimpleCrud.vue';
</script>
