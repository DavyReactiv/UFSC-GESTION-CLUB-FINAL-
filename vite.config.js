import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    manifest: true,
    outDir: 'assets/build',
    emptyOutDir: false,
    assetsDir: '',
    rollupOptions: {
      input: {
        'ufsc-frontend.js': resolve(__dirname, 'assets/src/js/ufsc-frontend.js'),
        'ufsc-frontend.css': resolve(__dirname, 'assets/src/css/ufsc-frontend.css'),
        'ufsc-forms.css': resolve(__dirname, 'assets/src/css/ufsc-forms.css')
      },
      output: {
        entryFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
        format: 'iife'
      }
    }
  }
});
