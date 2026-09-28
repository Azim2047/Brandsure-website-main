/*
 * Shared plumbing for the QA suite: a preview server and a browser.
 *
 * Every audit runs against build/preview over real HTTP rather than against
 * file:// or a string of HTML. That matters more than it sounds: file:// has
 * different CORS and cookie behaviour, and reading the markup cannot tell you
 * that an invisible element is intercepting every tap — which is a bug this
 * suite has actually caught.
 */

import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname } from 'node:path';

const HERE = dirname(fileURLToPath(import.meta.url));
export const ROOT = resolve(HERE, '../..');
export const PREVIEW = join(ROOT, 'build/preview');

/** The widths the brief names, plus tablet and desktop. */
export const MOBILE_WIDTHS = [360, 375, 390, 412, 430];
export const ALL_WIDTHS = [...MOBILE_WIDTHS, 768, 1024, 1280];

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.webp': 'image/webp',
  '.woff2': 'font/woff2',
};

export async function serve(port = 0) {
  const server = createServer(async (req, res) => {
    try {
      const url = new URL(req.url, 'http://localhost');
      // normalize + the prefix check keep a request for ../../etc/passwd inside
      // the preview directory. It is a local harness, but a path traversal in a
      // dev server is still a path traversal.
      const target = join(PREVIEW, normalize(decodeURIComponent(url.pathname)));

      if (!target.startsWith(PREVIEW)) {
        res.writeHead(403).end('forbidden');
        return;
      }

      const info = await stat(target).catch(() => null);
      const file = info?.isDirectory() ? join(target, 'index.html') : target;
      const body = await readFile(file);

      res.writeHead(200, {
        'content-type': TYPES[extname(file)] ?? 'application/octet-stream',
        'cache-control': 'no-store',
      }).end(body);
    } catch {
      res.writeHead(404).end('not found');
    }
  });

  await new Promise((ok) => server.listen(port, '127.0.0.1', ok));

  return {
    origin: `http://127.0.0.1:${server.address().port}`,
    close: () => new Promise((ok) => server.close(ok)),
  };
}

/**
 * Launch a browser, wherever Playwright and its browsers happen to live.
 *
 * Three environments have to work without anyone configuring anything:
 * a contributor's laptop (node_modules + `npx playwright install`), CI, and
 * this container (Playwright installed globally, browsers in a shared
 * directory, downloads disabled). The awkward case is a version skew — a
 * node_modules Playwright wanting a browser build the shared directory does
 * not have — so a failed launch retries against the known-good binary before
 * giving up.
 */
export async function browser() {
  const { existsSync } = await import('node:fs');
  const SHARED = '/opt/pw-browsers/chromium';

  const candidates = [];

  try {
    candidates.push((await import('playwright')).chromium);
  } catch { /* not installed locally — fine */ }

  try {
    candidates.push((await import('/opt/node22/lib/node_modules/playwright/index.mjs')).chromium);
  } catch { /* not installed globally — also fine */ }

  if (!candidates.length) {
    throw new Error('Playwright is not installed. Run `npm install`, then `npx playwright install chromium`.');
  }

  const errors = [];

  for (const chromium of candidates) {
    try {
      return await chromium.launch();
    } catch (e) {
      errors.push(e.message.split('\n')[0]);
    }

    if (existsSync(SHARED)) {
      try {
        return await chromium.launch({ executablePath: SHARED });
      } catch (e) {
        errors.push(e.message.split('\n')[0]);
      }
    }
  }

  throw new Error(
    'Could not launch Chromium. Tried every installed Playwright, and the shared browser at ' +
    `${SHARED}.\n  ` + errors.join('\n  ') +
    '\n\nOn a fresh machine: npx playwright install chromium'
  );
}

/** Every rendered surface, so a new page is audited the moment it renders. */
export async function pages() {
  const { readdir } = await import('node:fs/promises');
  return (await readdir(PREVIEW)).filter((f) => f.endsWith('.html')).sort();
}

export function report(name, failures, summary) {
  if (failures.length) {
    console.log(`\nFAIL  ${name}`);
    for (const f of failures.slice(0, 40)) console.log(`      ${f}`);
    if (failures.length > 40) console.log(`      … and ${failures.length - 40} more`);
    return 1;
  }

  console.log(`ok    ${name} — ${summary}`);
  return 0;
}
