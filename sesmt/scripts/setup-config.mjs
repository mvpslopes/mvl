import { copyFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const example = join(root, 'api', 'config.example.php');
const local = join(root, 'api', 'config.local.php');

if (existsSync(local)) {
  console.log('✓ api/config.local.php já existe.');
  process.exit(0);
}

copyFileSync(example, local);
console.log('✓ Criado api/config.local.php — edite com os dados do banco MySQL.');
