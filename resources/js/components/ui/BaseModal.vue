<template>
  <teleport to="body">
    <transition name="modal" appear>
      <div v-if="modelValue" class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="modal-backdrop" @click="close" />
        <div
          class="modal-panel"
          :class="sizeClass"
          role="dialog"
          aria-modal="true"
          v-motion
          :initial="{ opacity: 0, scale: 0.94, y: 16 }"
          :enter="{ opacity: 1, scale: 1, y: 0, transition: { duration: 280, ease: [0.22, 1, 0.36, 1] } }"
        >
          <div v-if="title || $slots.header" class="flex items-start justify-between mb-4">
            <div>
              <slot name="header">
                <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
                <p v-if="subtitle" class="text-sm text-slate-500 mt-0.5">{{ subtitle }}</p>
              </slot>
            </div>
            <button class="btn-icon -mr-1.5 -mt-1" @click="close" aria-label="Close">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="max-h-[70vh] overflow-y-auto -mx-1 px-1">
            <slot />
          </div>

          <div v-if="$slots.footer" class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm | md | lg | xl
  closeOnBackdrop: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'close']);

const sizes = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };
const sizeClass = computed(() => sizes[props.size] || sizes.md);

const close = () => {
  if (!props.closeOnBackdrop) return;
  emit('update:modelValue', false);
  emit('close');
};

const onKey = (e) => { if (e.key === 'Escape' && props.modelValue) close(); };
watch(() => props.modelValue, (open) => {
  document.documentElement.style.overflow = open ? 'hidden' : '';
  if (open) document.addEventListener('keydown', onKey);
  else document.removeEventListener('keydown', onKey);
});
onUnmounted(() => { document.documentElement.style.overflow = ''; document.removeEventListener('keydown', onKey); });
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
