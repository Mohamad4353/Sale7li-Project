import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({
      // `app.js` boots Alpine on every page. `react/index.jsx` is a SEPARATE
      // entry pulled in only by <x-react> pages, so React never ships on pages
      // that do not use it.
      input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/react/index.jsx'],
      refresh: true,
    }),
    tailwindcss(),
    react(),
  ],
});
