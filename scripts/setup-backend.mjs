#!/usr/bin/env node
/**
 * Kokango backend setup (re-runnable).
 *
 *   node scripts/setup-backend.mjs [--sqlite]
 *
 * 1. creates a fresh Laravel 12 skeleton and overlays our files on it (ours win),
 * 2. installs spatie/laravel-permission + laravel/sanctum and publishes their files,
 * 3. creates backend/.env, app key, storage link,
 * 4. runs migrations + seeders.
 *
 * Needs PHP 8.2+ and Composer. Use --sqlite if you do not have MySQL.
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const backend = path.join(root, 'backend');
const skeleton = path.join(root, '.laravel-skeleton');
const args = process.argv.slice(2);
const useSqlite = args.includes('--sqlite');

if (args.includes('--help') || args.includes('-h')) {
  console.log('Usage: node scripts/setup-backend.mjs [--sqlite]\n  --sqlite  use a local SQLite file instead of MySQL');
  process.exit(0);
}

const step = (msg) => console.log(`\n\x1b[1m==> ${msg}\x1b[0m`);
const info = (msg) => console.log(`    ${msg}`);

const isWin = process.platform === 'win32';
const childEnv = { ...process.env, COMPOSER_MEMORY_LIMIT: '-1', COMPOSER_NO_INTERACTION: '1' };

/** Quote one argument for a shell (only used for the shell fallback). */
function quote(arg) {
  return isWin ? `"${String(arg).replace(/"/g, '\\"')}"` : `'${String(arg).replace(/'/g, `'\\''`)}'`;
}

function fail(label, status) {
  console.error(`\nCommand failed (exit ${status}): ${label}`);
  process.exit(status || 1);
}

/** Run a shell command line (needed for composer, which is composer.bat on Windows). */
function run(cmd, cwd = backend, { allowFail = false } = {}) {
  info(`$ ${cmd}`);
  const res = spawnSync(cmd, { cwd, shell: true, stdio: 'inherit', env: childEnv });
  if (res.status !== 0 && !allowFail) fail(cmd, res.status);
  return res.status === 0;
}

/** Run php WITHOUT a shell so backslashes and quotes in arguments are passed through untouched. */
function php(args, cwd = backend, { allowFail = false } = {}) {
  info(`$ php ${args.join(' ')}`);
  let res = spawnSync('php', args, { cwd, stdio: 'inherit', env: childEnv });
  if (res.error && res.error.code === 'ENOENT') {
    // php is a shim (.bat/.cmd) on some Windows setups: retry through the shell with quoting.
    res = spawnSync(['php', ...args.map(quote)].join(' '), { cwd, shell: true, stdio: 'inherit', env: childEnv });
  }
  if (res.status !== 0 && !allowFail) fail(`php ${args.join(' ')}`, res.status);
  return res.status === 0;
}

/** Capture stdout of a command (stderr is kept separately so warnings do not corrupt the value). */
function capture(cmd, args = null) {
  const res = args
    ? spawnSync(cmd, args, { encoding: 'utf8' })
    : spawnSync(cmd, { shell: true, encoding: 'utf8' });
  return {
    ok: res.status === 0,
    out: `${res.stdout || ''}`.trim(),
    all: `${res.stdout || ''}${res.stderr || ''}`.trim(),
  };
}

function has(...parts) {
  return fs.existsSync(path.join(backend, ...parts));
}

// ---------------------------------------------------------------- (a) tools
step('Checking PHP and Composer');
const phpv = capture('php', ['-r', 'echo PHP_VERSION;']);
if (!phpv.ok || !/^\d+\.\d+/.test(phpv.out)) {
  console.error(`
PHP was not found. Install PHP 8.2 or newer (with the pdo_mysql, mbstring, openssl, xml, curl, fileinfo extensions):
  - Windows: https://windows.php.net/download  (or install Laragon / XAMPP)
  - macOS:   brew install php
  - Linux:   sudo apt install php-cli php-mbstring php-xml php-curl php-mysql php-sqlite3 unzip
Then run this command again.`);
  process.exit(1);
}
info(`PHP ${phpv.out}`);
const [major, minor] = phpv.out.split('.').map(Number);
if (major < 8 || (major === 8 && minor < 2)) {
  console.error('Laravel 12 needs PHP 8.2 or newer.');
  process.exit(1);
}
const composer = capture('composer --version');
if (!composer.ok) {
  console.error(`
Composer was not found. Install it from https://getcomposer.org/download/ and run this command again.`);
  process.exit(1);
}
info(composer.out.split('\n')[0]);

if (useSqlite) {
  const ext = capture('php', ['-r', "echo extension_loaded('pdo_sqlite') ? 'yes' : 'no';"]);
  if (ext.out !== 'yes') {
    console.error('The PHP extension pdo_sqlite is not enabled. Enable it in php.ini (extension=pdo_sqlite) or use MySQL.');
    process.exit(1);
  }
}

// ---------------------------------------------------- (b) skeleton + overlay
function copyMissing(src, dst) {
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    if (['.git', '.env', 'node_modules'].includes(entry.name)) continue;
    const from = path.join(src, entry.name);
    const to = path.join(dst, entry.name);

    if (entry.isDirectory()) {
      if (!fs.existsSync(to)) {
        try {
          fs.renameSync(from, to); // fast path (also keeps vendor/ intact)
        } catch {
          fs.cpSync(from, to, { recursive: true });
        }
      } else {
        copyMissing(from, to);
      }
    } else if (!fs.existsSync(to)) {
      fs.copyFileSync(from, to);
    }
  }
}

if (!has('artisan')) {
  step('Creating the Laravel 12 skeleton (this takes a few minutes)');
  fs.rmSync(skeleton, { recursive: true, force: true });
  run('composer create-project laravel/laravel .laravel-skeleton "^12.0" --prefer-dist --no-interaction', root);

  step('Copying skeleton files into backend/ (our files are kept)');
  fs.mkdirSync(backend, { recursive: true });
  // The skeleton's post-install step migrated a throwaway SQLite file; do not carry it over.
  fs.rmSync(path.join(skeleton, 'database', 'database.sqlite'), { force: true });
  copyMissing(skeleton, backend);
  fs.rmSync(skeleton, { recursive: true, force: true });
} else {
  step('Laravel skeleton already present, skipping');
}

// -------------------------------------------------------------------- (e) env
step('Preparing backend/.env');
const envPath = path.join(backend, '.env');
if (!fs.existsSync(envPath)) {
  fs.copyFileSync(path.join(backend, '.env.example'), envPath);
  info('Created backend/.env from .env.example');
} else {
  info('backend/.env already exists, keeping it');
}

function setEnv(key, value) {
  let text = fs.readFileSync(envPath, 'utf8');
  const re = new RegExp(`^${key}=.*$`, 'm');
  const line = `${key}=${value}`;
  text = re.test(text) ? text.replace(re, () => line) : `${text.replace(/\s*$/, '\n')}${line}\n`;
  fs.writeFileSync(envPath, text);
}

if (useSqlite) {
  const sqliteFile = path.join(backend, 'database', 'database.sqlite');
  fs.mkdirSync(path.dirname(sqliteFile), { recursive: true });
  if (!fs.existsSync(sqliteFile)) fs.writeFileSync(sqliteFile, '');
  setEnv('DB_CONNECTION', 'sqlite');
  // Quoted: a path with spaces (C:/Users/John Doe/...) would otherwise break the .env parser.
  setEnv('DB_DATABASE', `"${sqliteFile.replace(/\\/g, '/')}"`);
  info(`Using SQLite: ${sqliteFile}`);
}

// ------------------------------------------------------------ (c) packages
step('Installing packages');
const composerJson = fs.readFileSync(path.join(backend, 'composer.json'), 'utf8');
const missingPkgs = ['spatie/laravel-permission', 'laravel/sanctum'].filter((p) => !composerJson.includes(`"${p}"`) || !has('vendor', ...p.split('/')));
if (!has('vendor', 'autoload.php')) {
  run('composer install --no-interaction');
}
if (missingPkgs.length) {
  run(`composer require ${missingPkgs.join(' ')} --no-interaction`);
} else {
  info('spatie/laravel-permission and laravel/sanctum already installed');
}

const migrationFiles = fs.existsSync(path.join(backend, 'database', 'migrations'))
  ? fs.readdirSync(path.join(backend, 'database', 'migrations'))
  : [];

// Publish when the migration is missing (existing config files are never overwritten: no --force).
if (!migrationFiles.some((f) => f.includes('create_permission_tables'))) {
  php(['artisan', 'vendor:publish', '--provider=Spatie\\Permission\\PermissionServiceProvider', '--no-interaction']);
} else {
  info('Spatie permission files already published');
}
if (!migrationFiles.some((f) => f.includes('create_personal_access_tokens_table'))) {
  php(['artisan', 'vendor:publish', '--provider=Laravel\\Sanctum\\SanctumServiceProvider', '--no-interaction']);
} else {
  info('Sanctum files already published');
}

// ------------------------------------------------------- (d) key + storage
step('App key and storage link');
const envText = fs.readFileSync(envPath, 'utf8');
if (!/^APP_KEY=.+$/m.test(envText)) {
  php(['artisan', 'key:generate', '--no-interaction']);
} else {
  info('APP_KEY already set');
}
if (!has('public', 'storage')) {
  php(['artisan', 'storage:link'], backend, { allowFail: true });
} else {
  info('public/storage link already exists');
}

// ----------------------------------------------------------- (f) migrate
step('Running migrations and seeders');
php(['artisan', 'config:clear'], backend, { allowFail: true });
// Seeders run only after every migration (ours + Sanctum + Spatie) has finished.
const migrated = php(['artisan', 'migrate', '--force', '--no-interaction'], backend, { allowFail: true })
  && php(['artisan', 'db:seed', '--force', '--no-interaction'], backend, { allowFail: true });
if (!migrated) {
  console.error(`
\x1b[31mMigration or seeding failed.\x1b[0m Read the error above. If it mentions a connection or "Unknown database":
  1. Create the MySQL database:  CREATE DATABASE kokango CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  2. Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD in backend/.env
  3. No MySQL? Run again with SQLite:  node scripts/setup-backend.mjs --sqlite
Then re-run this command (finished steps are skipped).`);
  process.exit(1);
}

// ---------------------------------------------------------------- (g) done
console.log(`
\x1b[32mBackend is ready.\x1b[0m

Seeded logins
  Admin     admin@kokango.test   /  Admin@12345
  Customer  rohan@example.com    /  Password@123

Next steps
  1. Start the API:      cd backend && php artisan serve      (http://localhost:8000/api/v1)
  2. Start the website:  npm run dev                          (http://localhost:5173)
  3. Admin panel:        http://localhost:5173/admin
  Optional: queue worker  php artisan queue:work --stop-when-empty
            scheduler     php artisan schedule:run   (cron: * * * * *)
`);
