<template>
  <SimpleCrud
    title="Projects"
    subtitle="Create projects so leads can be grouped and routed."
    endpoint="/api/projects"
    :columns="[
      { key: 'name', label: 'Name' },
      { key: 'code', label: 'Code' },
      { key: 'description', label: 'Description' },
      { key: 'is_active', label: 'Active', boolean: true },
    ]"
    :fields="[
      { key: 'name', label: 'Name', required: true },
      { key: 'code', label: 'Code' },
      { key: 'description', label: 'Description', type: 'textarea' },
      { key: 'is_active', label: 'Active', type: 'checkbox', default: true },
    ]"
  />
</template>

<script setup>
import SimpleCrud from '../components/SimpleCrud.vue';
</script>
