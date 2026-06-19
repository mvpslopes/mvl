/**
 * Build unificado → C:\projetos\SiteMVL\dist
 * - Site principal + painel (/dashboard) com finanças integradas
 * - API em api/ (inclui api/financas/)
 */

import { execSync } from 'child_process';
import { copyFileSync, cpSync, existsSync, mkdirSync, readdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const dist = join(root, 'dist');
const financasApiSrc = join(root, 'financas', 'api');
const financasApiDest = join(root, 'api', 'financas');
const projetosApiSrc = join(root, 'projetos', 'api');
const projetosApiDest = join(root, 'api', 'projetos');

function syncModuleApi(src, dest, dbFolder) {
  if (!existsSync(src)) return;
  copyRecursive(src, dest, new Set(['config.local.php']));
  const dbSrc = join(root, dbFolder, 'database');
  const dbDest = join(dest, 'database');
  if (existsSync(dbSrc)) {
    copyRecursive(dbSrc, dbDest);
  }
}

function run(cmd, cwd = root) {
  console.log(`\n▶ ${cmd}`);
  execSync(cmd, { cwd, stdio: 'inherit', shell: true });
}

function copyRecursive(src, dest, skip = new Set()) {
  if (!existsSync(dest)) mkdirSync(dest, { recursive: true });
  for (const entry of readdirSync(src, { withFileTypes: true })) {
    if (skip.has(entry.name)) continue;
    const srcPath = join(src, entry.name);
    const destPath = join(dest, entry.name);
    if (entry.isDirectory()) copyRecursive(srcPath, destPath, skip);
    else copyFileSync(srcPath, destPath);
  }
}

console.log('📦 Build unificado SiteMVL → dist/\n');

// Sincronizar APIs dos módulos
syncModuleApi(financasApiSrc, financasApiDest, 'financas');
if (existsSync(financasApiSrc)) {
  console.log('✅ api/financas/ sincronizado');
}
syncModuleApi(projetosApiSrc, projetosApiDest, 'projetos');
if (existsSync(projetosApiSrc)) {
  console.log('✅ api/projetos/ sincronizado');
}

// Site principal (dashboard com menus de finanças)
run('npx vite build', root);

const htaccessRoot = join(root, 'public', '.htaccess');
if (existsSync(htaccessRoot)) {
  copyFileSync(htaccessRoot, join(dist, '.htaccess'));
}

console.log('\n✅ Build concluído!');
console.log(`   Pasta: ${dist}`);
console.log('   Painel: /dashboard (menus Finanças no sidebar)');
console.log('   Instalar DB finanças: GET /api/financas/install.php?key=install');
console.log('   Instalar DB projetos: GET /api/projetos/install.php?key=install\n');
