/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        'sesmt-page': '#F8FAF8',
        'sesmt-forest': '#2D4F3C',
        'sesmt-forest-muted': '#E8F2EB',
        'sesmt-accent': '#1052E0',
        'sesmt-accent-muted': '#E8EEFB',
        'sesmt-slate-muted': '#EEF2F6',
      },
      boxShadow: {
        sesmt: '0 4px 20px rgba(0, 0, 0, 0.05)',
        'sesmt-lg': '0 8px 28px rgba(0, 0, 0, 0.08)',
      },
      borderRadius: {
        sesmt: '16px',
      },
    },
  },
  plugins: [],
};
