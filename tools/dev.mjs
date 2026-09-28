/*
 * `npm run dev` — build, render, serve, and re-render on change.
 *
 * The loop this exists for: edit a component or a data file, alt-tab, refresh,
 * see it. Without it that is four commands, and the friction is enough that
 * people stop looking at the page they are building.
 *
 * It watches the theme and the theme source, and re-runs only what changed:
 * a CSS edit needs no re-render at all (the preview symlinks the real assets),
 * a PHP or JSON edit re-renders, and a theme.js edit rebuilds the tokens and
 * the sprite first.
 */

import { spawnSync } from 'node:child_process';
import { watch } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve, extname } from 'node:path';
import { serve } from './qa/lib.mjs';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(HERE, '..');
const THEME = resolve(ROOT, 'wp-content/themes/brandsure-v2');
const PORT = Number(process.env.PORT ?? 8080);

const run = (label, cmd, args) => {
  const t = Date.now();
  const r = spawnSync(cmd, args, { cwd: ROOT, encoding: 'utf8' });

  if (r.status !== 0) {
    console.error(`\n  ✗ ${label}\n${(r.stderr || r.stdout || '').trim().split('\n').slice(0, 12).map((l) => '    ' + l).join('\n')}\n`);
    return false;
  }

  console.log(`  ✓ ${label} (${Date.now() - t}ms)`);
  return true;
};

const build = () => run('build  tokens', 'node', ['tools/build-tokens.mjs']) &&
                     run('build  icons', 'node', ['tools/build-icons.mjs']);
const render = () => run('render 21 surfaces', 'php', ['tools/render.php']);

console.log('\nBrandSure dev\n');
build();
render();

const server = await serve(PORT);
console.log(`\n  ${server.origin}\n  watching for changes — ctrl-c to stop\n`);

/*
 * Debounced, because an editor writing a file fires several events and a
 * text editor's atomic save fires a delete and a create. Re-rendering four
 * times per keystroke is how a dev server becomes slower than not having one.
 */
let timer = null;
let pending = new Set();

const onChange = (file) => {
  if (!file) return;
  if (file.includes('/build/') || file.startsWith('.')) return;

  pending.add(file);
  clearTimeout(timer);

  timer = setTimeout(() => {
    const files = [...pending];
    pending = new Set();

    const themeChanged = files.some((f) => f.includes('theme-source'));
    // CSS needs nothing: build/preview symlinks the real wp-content, so the
    // next refresh already has it.
    const needsRender = files.some((f) => ['.php', '.json'].includes(extname(f))) || themeChanged;

    console.log(`\n  changed: ${files.slice(0, 3).join(', ')}${files.length > 3 ? ` +${files.length - 3}` : ''}`);

    if (themeChanged) build();
    if (needsRender) render();
    else console.log('  ✓ assets are symlinked — just refresh');
  }, 120);
};

for (const dir of [THEME, resolve(ROOT, 'theme-source')]) {
  watch(dir, { recursive: true }, (_, file) => onChange(file));
}

process.on('SIGINT', async () => {
  await server.close();
  console.log('\nstopped\n');
  process.exit(0);
});
