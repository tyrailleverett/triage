import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  build: {
    outDir: 'resources/dist',
    rollupOptions: {
      input: 'resources/js/app.tsx',
      output: {
        entryFileNames: 'assets/app.js',
        assetFileNames: 'assets/app.css',
      },
    },
  },
});
