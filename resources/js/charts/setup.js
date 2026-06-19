// Single Chart.js registration shared by every chart component.
import {
  Chart as ChartJS,
  CategoryScale, LinearScale,
  BarElement, LineElement, PointElement, ArcElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js';

ChartJS.register(
  CategoryScale, LinearScale,
  BarElement, LineElement, PointElement, ArcElement,
  Title, Tooltip, Legend, Filler,
);

// Premium defaults aligned with the design tokens.
ChartJS.defaults.font.family = "Inter, ui-sans-serif, system-ui, sans-serif";
ChartJS.defaults.color = '#64748b';
ChartJS.defaults.plugins.legend.labels.usePointStyle = true;
ChartJS.defaults.plugins.legend.labels.boxWidth = 8;
ChartJS.defaults.plugins.tooltip.backgroundColor = 'rgba(15,23,42,0.92)';
ChartJS.defaults.plugins.tooltip.padding = 12;
ChartJS.defaults.plugins.tooltip.cornerRadius = 12;
ChartJS.defaults.plugins.tooltip.titleFont = { weight: '600' };
ChartJS.defaults.maintainAspectRatio = false;

// Token palette for series.
export const palette = {
  brand: '#3a5dff',
  brandSoft: 'rgba(58,93,255,0.12)',
  success: '#10b981',
  warning: '#f59e0b',
  danger: '#ef4444',
  info: '#3b82f6',
  cyan: '#06b6d4',
  indigo: '#6366f1',
  slate: '#94a3b8',
};

export const series = [palette.brand, palette.success, palette.warning, palette.indigo, palette.cyan, palette.danger, palette.info, palette.slate];

export { ChartJS };
