/**
 * Dashboard widget registry — the single source of truth for which widgets
 * render, in what order, at what width, and for which roles. Layout is NOT
 * hardcoded in the Dashboard component: it is derived from this list, so a
 * future "customize dashboard" feature can reorder / hide / role-gate widgets
 * (drag & drop, per-user layouts, role-based dashboards) by persisting an
 * override of this array.
 *
 * `span` is a Tailwind lg column span on a 12-col grid.
 * `roles` (optional) restricts visibility; omit for everyone.
 */
export const DASHBOARD_WIDGETS = [
  { key: 'executive',  span: 12, roles: ['master', 'super_master'], visible: true },
  { key: 'summary',    span: 12, visible: true },
  { key: 'trend',      span: 8,  visible: true },
  { key: 'funnel',     span: 4,  visible: true },
  { key: 'followups',  span: 12, visible: true },
  { key: 'sources',    span: 6,  visible: true },
  { key: 'conversion', span: 6,  visible: true },
  { key: 'leaderboard',span: 7,  visible: true },
  { key: 'aging',      span: 5,  visible: true },
  { key: 'teams',      span: 5,  roles: ['master', 'super_master', 'sub_master'], visible: true },
  { key: 'activity',   span: 7,  visible: true },
];

export const SPAN_CLASS = {
  4: 'lg:col-span-4',
  5: 'lg:col-span-5',
  6: 'lg:col-span-6',
  7: 'lg:col-span-7',
  8: 'lg:col-span-8',
  12: 'lg:col-span-12',
};

/** Widgets visible to a given role, in registry order. */
export function widgetsForRole(role) {
  return DASHBOARD_WIDGETS.filter(w => w.visible !== false && (!w.roles || w.roles.includes(role)));
}
