import {defineConfig} from 'vite';
import path from 'path';
import reactRefresh from '@vitejs/plugin-react-refresh';

const projectRootDir = path.resolve(__dirname);

export default defineConfig({
  plugins: [reactRefresh()],
  build: {
    emptyOutDir: false,
    outDir: './webroot/',

    // make a manifest and source maps.
    manifest: true,
    sourcemap: true,

    rollupOptions: {
      // Use a custom non-html entry point
      input: path.resolve(projectRootDir, './assets/js/app.tsx'),
    },
  },
  resolve: {
    alias: [
      {
        find: 'app',
        replacement: path.resolve(projectRootDir, './assets/js'),
      },
    ],
  },
  esbuild: {
    // Simulate react17 style jsx usage.
    jsxInject: `import React from 'react';`,
  },
  optimizeDeps: {
    include: ['react', 'axios', '@inertiajs/inertia', '@inertiajs/inertia-react'],
  },
  server: {
    watch: {
      ignored: ['**/vendor/**', '**/flutterapp/**'],
    },
  },
});
