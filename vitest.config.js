import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

// Minimal Vitest setup — only the React foundation is unit-tested in JS.
// Everything server-rendered (Blade coexistence, prop serialization, security,
// EN/AR, RTL, the API and favourite endpoints) is covered by PHP feature tests.
export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./vitest.setup.js'],
    include: ['resources/js/react/**/*.test.{js,jsx}'],
    css: false,
  },
});
