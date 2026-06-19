<template>
  <SimpleCrud
    title="Lead Sources"
    subtitle="Channels through which leads enter your CRM."
    endpoint="/api/lead-sources"
    :columns="[
      { key: 'name', label: 'Name' },
      { key: 'channel', label: 'Channel' },
      { key: 'is_active', label: 'Active', boolean: true },
    ]"
    :fields="[
      { key: 'name', label: 'Name', required: true },
      { key: 'channel', label: 'Channel slug (e.g. meta_fb, google_ads)' },
      { key: 'is_active', label: 'Active', type: 'checkbox', default: true },
    ]"
  />
</template>

<script setup>
import SimpleCrud from '../components/SimpleCrud.vue';
</script>
