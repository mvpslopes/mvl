import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { copyFileSync, existsSync, mkdirSync, readdirSync } from 'fs';
import { join } from 'path';

export default defineConfig({
  plugins: [
    react(),
    {
      name: 'copy-sesmt-api',
      closeBundle() {
        const apiDir = join(__dirname, 'api');
        const distApiDir = join(__dirname, 'dist', 'api');
        if (!existsSync(apiDir)) return;

        const copyRecursive = (src: string, dest: string) => {
          if (!existsSync(dest)) mkdirSync(dest, { recursive: true });
          for (const entry of readdirSync(src, { withFileTypes: true })) {
            // config.local.php é copiado se existir (necessário no servidor após deploy)
            const srcPath = join(src, entry.name);
            const destPath = join(dest, entry.name);
            if (entry.isDirectory()) copyRecursive(srcPath, destPath);
            else copyFileSync(srcPath, destPath);
          }
        };

        copyRecursive(apiDir, distApiDir);
      },
    },
  ],
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
      },
    },
  },
});
