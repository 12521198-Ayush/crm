/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      // ── Color tokens ───────────────────────────────────────────────
      colors: {
        brand: {
          50:  '#eef4ff',
          100: '#dae6ff',
          200: '#bcd1ff',
          300: '#8fb1ff',
          400: '#5d86ff',
          500: '#3a5dff',
          600: '#243df5',
          700: '#1c2fd1',
          800: '#1c2aa6',
          900: '#1d2c83',
        },
        // Semantic tokens — components reference these, never raw palettes
        success: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' },
        warning: { 50: '#fffbeb', 100: '#fef3c7', 500: '#f59e0b', 600: '#d97706', 700: '#b45309' },
        danger:  { 50: '#fef2f2', 100: '#fee2e2', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c' },
        info:    { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
        // Neutral surface ramp for soft backgrounds / elevation
        surface: {
          50:  '#fbfcfe',
          100: '#f5f7fb',
          200: '#eef1f7',
          300: '#e3e8f0',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      // ── Typography scale ───────────────────────────────────────────
      fontSize: {
        '2xs': ['0.6875rem', { lineHeight: '1rem' }],
      },
      // ── Radius tokens ──────────────────────────────────────────────
      borderRadius: {
        xl: '0.875rem',
        '2xl': '1.25rem',
        '3xl': '1.75rem',
      },
      // ── Shadow tokens (soft elevation, no hard borders) ────────────
      boxShadow: {
        soft:        '0 2px 10px rgba(15, 23, 42, 0.06)',
        card:        '0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px -12px rgba(15, 23, 42, 0.12)',
        'card-hover':'0 6px 16px -4px rgba(15, 23, 42, 0.10), 0 14px 40px -12px rgba(36, 61, 245, 0.18)',
        glass:       '0 8px 32px rgba(15, 23, 42, 0.10)',
        glow:        '0 8px 24px -8px rgba(58, 93, 255, 0.55)',
        'inner-soft':'inset 0 1px 2px rgba(15, 23, 42, 0.04)',
      },
      // ── Gradient tokens ────────────────────────────────────────────
      backgroundImage: {
        'brand-gradient':   'linear-gradient(135deg, #3a5dff 0%, #243df5 50%, #1c2fd1 100%)',
        'brand-soft':       'linear-gradient(135deg, #eef4ff 0%, #dae6ff 100%)',
        'success-gradient': 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        'danger-gradient':  'linear-gradient(135deg, #f97373 0%, #dc2626 100%)',
        'warning-gradient': 'linear-gradient(135deg, #fbbf24 0%, #d97706 100%)',
        'app-radial':       'radial-gradient(1200px 600px at 100% -10%, #eef4ff 0%, transparent 60%), radial-gradient(900px 500px at -10% 110%, #f0f5ff 0%, transparent 55%)',
        'shimmer':          'linear-gradient(90deg, rgba(226,232,240,0) 0%, rgba(226,232,240,0.7) 50%, rgba(226,232,240,0) 100%)',
      },
      // ── Spacing token (extra) ──────────────────────────────────────
      spacing: {
        '18': '4.5rem',
      },
      // ── Animation tokens ───────────────────────────────────────────
      transitionTimingFunction: {
        'spring': 'cubic-bezier(0.22, 1, 0.36, 1)',
        'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
      keyframes: {
        'fade-in':   { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
        'slide-up':  { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
        'scale-in':  { '0%': { opacity: '0', transform: 'scale(0.96)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
        'shimmer':   { '0%': { backgroundPosition: '-468px 0' }, '100%': { backgroundPosition: '468px 0' } },
        'pulse-ring':{ '0%': { transform: 'scale(0.8)', opacity: '0.6' }, '100%': { transform: 'scale(2.2)', opacity: '0' } },
      },
      animation: {
        'fade-in':  'fade-in 0.3s ease-out both',
        'slide-up': 'slide-up 0.4s cubic-bezier(0.22, 1, 0.36, 1) both',
        'scale-in': 'scale-in 0.25s cubic-bezier(0.22, 1, 0.36, 1) both',
        'shimmer':  'shimmer 1.4s linear infinite',
        'pulse-ring':'pulse-ring 1.6s cubic-bezier(0.22, 1, 0.36, 1) infinite',
      },
      backdropBlur: {
        xs: '2px',
      },
    },
  },
  plugins: [],
};
