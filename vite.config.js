import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    outDir: 'assets/dist',
    emptyOutDir: false,
    assetsDir: '',
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'assets/src/js/admin.js'),
        'admin.css': resolve(__dirname, 'assets/src/css/admin.css')
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name][extname]',
        format: 'es'
      }
    }
  }
});
