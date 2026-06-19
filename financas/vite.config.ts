import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { copyFileSync, existsSync, mkdirSync, readdirSync } from 'fs';
import { join } from 'path';

export default defineConfig({
  base: '/financas/',
  plugins: [
    react(),
    {
      name: 'copy-financas-api',
      closeBundle() {
        const apiDir = join(__dirname, 'api');
        const distApiDir = join(__dirname, 'dist', 'api');
        if (!existsSync(apiDir)) return;

        const copyRecursive = (src: string, dest: string) => {
          if (!existsSync(dest)) mkdirSync(dest, { recursive: true });
          const skip = new Set(['config.local.php', 'node_modules', '.git']);
          for (const entry of readdirSync(src, { withFileTypes: true })) {
            if (skip.has(entry.name)) continue;
            const srcPath = join(src, entry.name);
            const destPath = join(dest, entry.name);
            if (entry.isDirectory()) copyRecursive(srcPath, destPath);
            else copyFileSync(srcPath, destPath);
          }
        };

        copyRecursive(apiDir, distApiDir);

        const htaccess = join(__dirname, 'public', '.htaccess');
        if (existsSync(htaccess)) {
          copyFileSync(htaccess, join(__dirname, 'dist', '.htaccess'));
        }
      },
    },
  ],
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8081',
        changeOrigin: true,
      },
    },
  },
});
