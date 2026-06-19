<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to"
    class="card"
    :class="[pad ? 'card-pad' : '', (hover || to) ? 'card-hover cursor-pointer' : '']"
    v-motion
    :initial="{ opacity: 0, y: 14 }"
    :enter="{ opacity: 1, y: 0, transition: { delay: delay, duration: 350, ease: [0.22, 1, 0.36, 1] } }"
  >
    <slot />
  </component>
</template>

<script setup>
import { RouterLink } from 'vue-router';

defineProps({
  to: { type: [String, Object], default: null },
  hover: { type: Boolean, default: false },
  pad: { type: Boolean, default: true },
  delay: { type: Number, default: 0 },
});
</script>
