<template>
  <component
    :is="to ? RouterLink : (href ? 'a' : 'button')"
    :to="to"
    :href="href"
    :type="to || href ? undefined : type"
    :disabled="disabled || loading"
    :class="[variantClass, sizeClass, block ? 'w-full' : '']"
  >
    <span v-if="loading" class="w-4 h-4 rounded-full border-2 border-current border-t-transparent animate-spin" />
    <component v-else-if="icon" :is="icon" class="w-4 h-4" />
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger
  size: { type: String, default: 'md' },          // sm | md
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  icon: { type: [Object, Function], default: null },
  type: { type: String, default: 'button' },
  to: { type: [String, Object], default: null },
  href: { type: String, default: null },
});

const variants = {
  primary: 'btn-primary',
  secondary: 'btn-secondary',
  ghost: 'btn-ghost',
  danger: 'btn-danger',
};
const variantClass = computed(() => variants[props.variant] || variants.primary);
const sizeClass = computed(() => (props.size === 'sm' ? 'btn-sm' : ''));
</script>
