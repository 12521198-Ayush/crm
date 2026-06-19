<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to"
    class="card card-pad group"
    :class="{ 'card-hover cursor-pointer': to }"
    v-motion
    :initial="{ opacity: 0, y: 16 }"
    :enter="{ opacity: 1, y: 0, transition: { delay, duration: 350, ease: [0.22, 1, 0.36, 1] } }"
  >
    <div class="flex items-start justify-between">
      <div class="min-w-0">
        <div class="text-2xs font-semibold uppercase tracking-wide text-slate-500 truncate">{{ label }}</div>
        <div class="text-2xl font-bold mt-1 text-slate-900">
          <AnimatedCounter :value="Number(value) || 0" />
        </div>
        <div v-if="trendPct !== null && trendPct !== undefined" class="flex items-center gap-1.5 mt-2">
          <TrendBadge :value="trendPct" :invert="invert" :comparedTo="comparedTo" />
          <span class="text-2xs text-slate-400">vs {{ comparedTo }}</span>
        </div>
      </div>
      <div class="w-11 h-11 rounded-xl grid place-items-center transition-transform duration-300 group-hover:scale-110" :class="bg">
        <component :is="iconComp" class="w-5 h-5" :class="fg" />
      </div>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { ArchiveBoxIcon, ClockIcon, PhoneIcon, Squares2X2Icon, PlusIcon, CalendarDaysIcon, FireIcon, UserMinusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AnimatedCounter from './ui/AnimatedCounter.vue';
import TrendBadge from './ui/TrendBadge.vue';

const props = defineProps({
  label: String,
  value: [Number, String],
  icon: String,
  tone: { type: String, default: 'brand' },
  to: [String, Object],
  trendPct: { type: Number, default: null },
  invert: { type: Boolean, default: false },
  comparedTo: { type: String, default: 'previous period' },
  delay: { type: Number, default: 0 },
});

const map = { archive: ArchiveBoxIcon, clock: ClockIcon, phone: PhoneIcon, leads: Squares2X2Icon, plus: PlusIcon, calendar: CalendarDaysIcon, fire: FireIcon, user: UserMinusIcon, x: XMarkIcon };
const iconComp = computed(() => map[props.icon] || Squares2X2Icon);

const tones = {
  brand:   ['bg-brand-100',   'text-brand-700'],
  success: ['bg-success-100', 'text-success-700'],
  emerald: ['bg-success-100', 'text-success-700'],
  warning: ['bg-warning-100', 'text-warning-700'],
  amber:   ['bg-warning-100', 'text-warning-700'],
  danger:  ['bg-danger-100',  'text-danger-700'],
  rose:    ['bg-danger-100',  'text-danger-700'],
  info:    ['bg-info-100',    'text-info-700'],
  slate:   ['bg-slate-100',   'text-slate-600'],
  indigo:  ['bg-indigo-100',  'text-indigo-700'],
  cyan:    ['bg-cyan-100',    'text-cyan-700'],
};
const bg = computed(() => (tones[props.tone] || tones.brand)[0]);
const fg = computed(() => (tones[props.tone] || tones.brand)[1]);
</script>
