<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 p-4 sm:p-6">
        <router-view v-slot="{ Component, route }">
          <transition name="page" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </main>
      <footer class="px-6 py-3 text-xs text-slate-400">
        © {{ new Date().getFullYear() }} Ninja CRM — Enterprise Edition
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AppSidebar from '../components/AppSidebar.vue';
import AppHeader from '../components/AppHeader.vue';

const sidebarOpen = ref(false);
</script>

