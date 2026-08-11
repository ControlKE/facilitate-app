import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import fs from 'node:fs';
import path from 'node:path';

const copyPhpRuntimePlugin = () => ({
  name: 'copy-php-runtime',
  closeBundle() {
    const runtimeCopies = [
      ['src/php', 'dist/php'],
      ['vendor', 'dist/vendor'],
    ];

    runtimeCopies.forEach(([sourceRelativePath, targetRelativePath]) => {
      const sourcePath = path.resolve(process.cwd(), sourceRelativePath);
      const targetPath = path.resolve(process.cwd(), targetRelativePath);

      if (!fs.existsSync(sourcePath)) {
        return;
      }

      fs.cpSync(sourcePath, targetPath, { recursive: true, force: true });
    });
  },
});

export default defineConfig({
  plugins: [vue(), copyPhpRuntimePlugin()],
  server: {
    proxy: {
      '/phpapi': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/phpapi/, '/facilitate/src/php'),
      },
      '/api': {
        target: 'http://localhost:3001',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
