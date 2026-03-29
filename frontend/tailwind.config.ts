import type { Config } from 'tailwindcss';

export default {
  darkMode: ['class', '[data-theme="dark"]'],
  content: [
    './index.html',
    './src/**/*.{vue,ts,js}',
    './pages/**/*.vue',
    './components/**/*.vue',
    './modules/**/*.{vue,ts,js}',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef4ff',
          600: '#2563eb',
          700: '#1d4ed8',
        },
      },
      boxShadow: {
        soft: '0 10px 30px -18px rgba(15, 23, 42, 0.35)',
      },
    },
  },
  plugins: [],
} satisfies Config;
