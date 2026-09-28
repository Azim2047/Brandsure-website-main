/*
 * Load the supplied theme.js.
 *
 * theme.js is CommonJS — it ends in `module.exports = {...}`. This package is
 * `"type": "module"`, so Node would try to parse it as ESM and fail. The
 * obvious fixes are both wrong: renaming it to .cjs makes the file no longer
 * the file BrandSure sent, and converting it to ESM edits an input.
 *
 * So it is evaluated as CommonJS in a sandbox instead, and stays byte-identical
 * to the copy in the Layer 4 pack. Verify any time with:
 *   sha256sum theme-source/theme.js
 *   -> 701e3dcb266b89b0c91e35ceaa7c06d0225411b153df9fd543163ace2b376ed2
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { createContext, runInContext } from 'node:vm';

const HERE = dirname(fileURLToPath(import.meta.url));

export const THEME_PATH = resolve(HERE, '../../theme-source/theme.js');

/** The theme's own hash, so a swapped file is visible rather than silent. */
export const THEME_SHA256 = '701e3dcb266b89b0c91e35ceaa7c06d0225411b153df9fd543163ace2b376ed2';

export function loadTheme(path = THEME_PATH) {
  const source = readFileSync(path, 'utf8');

  // No require, no fs, no process. theme.js is a data file and needs none of
  // them; if a future version reaches for one, this should fail loudly rather
  // than quietly grant a build script more power than it needs.
  const sandbox = { module: { exports: {} } };
  sandbox.exports = sandbox.module.exports;

  runInContext(source, createContext(sandbox), { filename: path, timeout: 5000 });

  const theme = sandbox.module.exports;

  for (const key of ['C', 'T', 'ICON', 'ICON_LANE', 'CIRCLE', 'MOTION']) {
    if (!theme?.[key]) {
      throw new Error(`theme.js did not export ${key} — is this the BSU Master Theme v3 file?`);
    }
  }

  return theme;
}
