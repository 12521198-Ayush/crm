<template>
  <span>{{ display }}</span>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  value: { type: Number, default: 0 },
  duration: { type: Number, default: 900 },
  decimals: { type: Number, default: 0 },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
});

const current = ref(0);
const display = ref(format(0));

function format(n) {
  const fixed = Number(n).toFixed(props.decimals);
  const parts = fixed.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return props.prefix + parts.join('.') + props.suffix;
}

let raf = null;
function animate(to) {
  cancelAnimationFrame(raf);
  const from = current.value;
  const start = performance.now();
  const step = (now) => {
    const t = Math.min(1, (now - start) / props.duration);
    const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
    current.value = from + (to - from) * eased;
    display.value = format(current.value);
    if (t < 1) raf = requestAnimationFrame(step);
    else { current.value = to; display.value = format(to); }
  };
  raf = requestAnimationFrame(step);
}

onMounted(() => animate(props.value));
watch(() => props.value, (v) => animate(v));
</script>
