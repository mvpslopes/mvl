import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { copyFileSync, existsSync, mkdirSync, readdirSync } from 'fs';
import { join } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
    {
      name: 'copy-php',
      closeBundle() {
        // Copiar send-contact.php para dist após o build
        const phpFile = join(__dirname, 'send-contact.php');
        const distFile = join(__dirname, 'dist', 'send-contact.php');
        if (existsSync(phpFile)) {
          copyFileSync(phpFile, distFile);
        }

        // Copiar pasta api/ para dist/ (exceto credentials.json por segurança)
        const apiDir = join(__dirname, 'api');
        const distApiDir = join(__dirname, 'dist', 'api');
        if (existsSync(apiDir)) {
          // Função para copiar recursivamente
          const copyRecursive = (src: string, dest: string) => {
            if (!existsSync(dest)) {
              mkdirSync(dest, { recursive: true });
            }
            const entries = readdirSync(src, { withFileTypes: true });
            for (const entry of entries) {
              const srcPath = join(src, entry.name);
              const destPath = join(dest, entry.name);
              
              // Pular arquivos e pastas que não devem ser copiados
              const skipItems = [
                'credentials.json', // Arquivo sensível
                '.git', // Pasta git
                '.gitignore',
                '.gitattributes',
                'node_modules', // Se houver
                '.DS_Store', // Arquivos do macOS
                'Thumbs.db', // Arquivos do Windows
              ];
              
              if (skipItems.includes(entry.name)) {
                continue;
              }
              
              // Pular arquivos dentro de pastas .git
              if (srcPath.includes('.git')) {
                continue;
              }
              
              try {
              if (entry.isDirectory()) {
                copyRecursive(srcPath, destPath);
              } else {
                copyFileSync(srcPath, destPath);
                }
              } catch (err: unknown) {
                // Ignorar erros de permissão em arquivos específicos
                const code =
                  typeof err === 'object' && err && 'code' in err
                    ? (err as { code?: unknown }).code
                    : undefined;
                if (code === 'EPERM' || code === 'EACCES') {
                  console.log(`⚠️  Pulando arquivo com problema de permissão: ${entry.name}`);
                  continue;
                }
                throw err;
              }
            }
          };
          
          copyRecursive(apiDir, distApiDir);
          console.log('✅ Pasta api/ copiada para dist/ (exceto credentials.json)');
        }

        const htaccess = join(__dirname, 'public', '.htaccess');
        if (existsSync(htaccess)) {
          copyFileSync(htaccess, join(__dirname, 'dist', '.htaccess'));
        }
      },
    },
  ],
  optimizeDeps: {
    exclude: ['lucide-react'],
  },
});
