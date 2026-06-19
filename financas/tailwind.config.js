/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        page: '#F8F9FB',
        ink: '#1A1D26',
      },
      boxShadow: {
        card: '0 4px 24px rgba(0, 0, 0, 0.05)',
      },
    },
  },
  plugins: [],
};
