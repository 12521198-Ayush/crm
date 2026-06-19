<template>
  <div class="card overflow-hidden">
    <!-- Bulk action bar -->
    <transition name="bulk">
      <div v-if="selectable && selected.length" class="flex items-center gap-3 px-4 py-2.5 bg-brand-soft">
        <span class="text-sm font-semibold text-brand-700">{{ selected.length }} selected</span>
        <div class="flex items-center gap-2 ml-auto">
          <slot name="bulk-actions" :selected="selected" :clear="clearSelection" />
        </div>
      </div>
    </transition>

    <div class="overflow-x-auto max-h-[calc(100vh-16rem)] overflow-y-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th v-if="selectable" class="w-10">
              <input type="checkbox" class="rounded accent-brand-600" :checked="allChecked" @change="toggleAll" />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[col.align === 'right' ? 'text-right' : '', col.sortable ? 'cursor-pointer select-none' : '']"
              @click="col.sortable && sortBy(col.key)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <ChevronUpDownIcon v-if="col.sortable && sortKey !== col.key" class="w-3.5 h-3.5 text-slate-300" />
                <ChevronUpIcon v-else-if="col.sortable && sortDir === 'asc'" class="w-3.5 h-3.5 text-brand-500" />
                <ChevronDownIcon v-else-if="col.sortable" class="w-3.5 h-3.5 text-brand-500" />
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in rows" :key="rowKey ? row[rowKey] : i" @click="$emit('row-click', row)" :class="{ 'cursor-pointer': hasRowClick }">
            <td v-if="selectable" @click.stop>
              <input type="checkbox" class="rounded accent-brand-600" :value="row[rowKey]" v-model="selected" />
            </td>
            <td v-for="col in columns" :key="col.key" :class="col.align === 'right' ? 'text-right' : ''">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">{{ row[col.key] }}</slot>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td :colspan="columns.length + (selectable ? 1 : 0)" class="text-center py-10 text-slate-400">
              <slot name="empty">No records found.</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { ChevronUpDownIcon, ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label, sortable, align }]
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  selectable: { type: Boolean, default: false },
});
const emit = defineEmits(['row-click', 'sort']);

const selected = ref([]);
const sortKey = ref(null);
const sortDir = ref('asc');

const allChecked = computed(() => props.rows.length > 0 && selected.value.length === props.rows.length);
const toggleAll = () => { selected.value = allChecked.value ? [] : props.rows.map(r => r[props.rowKey]); };
const clearSelection = () => { selected.value = []; };

const sortBy = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  else { sortKey.value = key; sortDir.value = 'asc'; }
  emit('sort', { key: sortKey.value, dir: sortDir.value });
};

const hasRowClick = computed(() => !!getCurrentInstance()?.vnode?.props?.onRowClick);

defineExpose({ clearSelection, selected });
</script>

<style scoped>
.bulk-enter-active, .bulk-leave-active { transition: all .2s ease; }
.bulk-enter-from, .bulk-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
