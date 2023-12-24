import {defineConfig} from 'vite';
import path from 'path';

const projectRootDir = path.resolve(__dirname);

export default defineConfig({
  plugins: [],
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
  esbuild: {},
  optimizeDeps: {
    include: [],
  },
  server: {
    watch: {
      ignored: ['**/vendor/**', '**/flutterapp/**'],
    },
  },
});
