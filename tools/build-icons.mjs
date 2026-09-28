/*
 * theme.js ICON vocabulary  ->  assets/img/icons.svg
 *
 * Theme spec s3 is titled "Icons — white glyph, solid circle" and is emphatic:
 *
 *   "The only icon form in the system. A Lucide glyph at 2 px stroke, filling
 *    50% of a solid circle, always centred... The circle fill is the lane code
 *    — no borders, no tiles, no rounded squares."
 *
 * The hand-built sprite had 19 symbols covering 13 of the theme's 29 meanings,
 * two of them drawn by hand rather than taken from Lucide, and rendered as
 * bordered rounded squares — which is v1's rejected form. This generator takes
 * the glyphs from the real lucide-static package and the lane from theme.js's
 * own ICON_LANE map, so a glyph's colour is derived from its MEANING and can
 * never be picked by hand.
 *
 * It emits two things per meaning:
 *   #bs-icon-NAME        the bare glyph, currentColor — for text-adjacent use
 *   #bs-circle-NAME      the theme form: white glyph inside a solid circle
 *
 * The circle's fill comes from a CSS variable (--bs-lane, default set by the
 * symbol's own lane) so a component can override the lane when the meaning
 * changes — the Lane Switch on /plans/ recolouring a chain is exactly that.
 *
 * Usage:  node tools/build-icons.mjs [--check]
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { loadTheme, THEME_SHA256 } from './lib/load-theme.mjs';

const HERE = dirname(fileURLToPath(import.meta.url));
const LUCIDE = resolve(HERE, '../node_modules/lucide-static/icons');
const OUT = resolve(HERE, '../wp-content/themes/brandsure-v2/assets/img/icons.svg');

const theme = loadTheme();

/*
 * Meanings the website needs that the deck has no word for, because a slide
 * has no controls. Kept separate from theme.ICON so the theme's vocabulary is
 * never quietly extended — anything here is visibly the website's own.
 */
const WEB_ONLY = {
  menu:        { glyph: 'menu',            lane: 'bs' },
  close:       { glyph: 'x',               lane: 'bs' },
  'arrow-right': { glyph: 'arrow-right',   lane: 'bs' },
  play:        { glyph: 'play',            lane: 'bs' },
  compass:     { glyph: 'compass',         lane: 'bs' },
  grid:        { glyph: 'layout-grid',     lane: 'bs' },
};

/*
 * Names the existing templates already use. Kept as aliases so this sprite is
 * a drop-in replacement — renaming them would be a change to every component
 * for no gain, and a broken <use> renders as nothing at all, silently.
 */
const ALIASES = {
  enquiry:   'customers',
  written:   'clipboard',
  worker:    'worker',
  showroom:  'showroom',
  ad:        'ad',
  website:   'website',
};

const lucide = (name) => {
  const file = resolve(LUCIDE, `${name}.svg`);

  if (!existsSync(file)) {
    throw new Error(`Lucide has no glyph "${name}" — check the name in theme.js ICON.`);
  }

  const svg = readFileSync(file, 'utf8');
  const body = svg.slice(svg.indexOf('>', svg.indexOf('<svg')) + 1, svg.lastIndexOf('</svg>'));

  // Return lines, not a pre-joined string: each call site indents differently,
  // and joining here then re-indenting leaves ragged output in a file people read.
  return body.split('\n').map((l) => l.trim()).filter(Boolean);
};

const entries = [];

for (const [meaning, glyph] of Object.entries(theme.ICON)) {
  entries.push({ meaning, glyph, lane: theme.ICON_LANE[meaning] ?? 'bs', source: 'theme' });
}

for (const [meaning, spec] of Object.entries(WEB_ONLY)) {
  if (theme.ICON[meaning]) continue;
  entries.push({ meaning, glyph: spec.glyph, lane: spec.lane, source: 'web' });
}

entries.sort((a, b) => a.meaning.localeCompare(b.meaning));

const out = [];
const p = (s = '') => out.push(s);

p('<svg xmlns="http://www.w3.org/2000/svg" style="display:none">');
p('<!--');
p('\tBrandSure icon sprite. GENERATED — `npm run build:icons` overwrites this.');
p('');
p('\tGlyphs: lucide-static, unmodified, 2px stroke on the 24 grid.');
p(`\tLanes:  theme-source/theme.js ICON_LANE (sha256 ${THEME_SHA256.slice(0, 16)}…)`);
p('');
p('\tTwo forms per meaning:');
p('\t  #bs-icon-NAME    bare glyph, inherits currentColor');
p('\t  #bs-circle-NAME  the theme form — white glyph in a SOLID circle');
p('');
p('\tTheme s3: "The circle fill is the lane code — no borders, no tiles, no');
p('\trounded squares." The fill reads --bs-lane, which each symbol defaults to');
p('\tits own lane, so a component can recolour a circle by setting one variable');
p('\tand the meaning follows. Blue is us, green is you, teal is shared, slate is');
p('\ta problem.');
p('-->');

for (const { meaning, glyph, lane, source } of entries) {
  const glyphLines = lucide(glyph);
  const tag = source === 'theme' ? `lane ${lane}` : `lane ${lane} · website-only, not in the deck vocabulary`;

  p('');
  p(`\t<!-- ${meaning} · lucide "${glyph}" · ${tag} -->`);
  p(`\t<symbol id="bs-icon-${meaning}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">`);
  p(glyphLines.map((l) => `\t\t${l}`).join('\n'));
  p('\t</symbol>');
  p('');
  p(`\t<symbol id="bs-circle-${meaning}" viewBox="0 0 40 40" style="--bs-lane: var(--bs-lane-${lane})">`);
  p('\t\t<circle cx="20" cy="20" r="20" fill="var(--bs-lane)"/>');
  // Glyph box is 50% of the diameter, per GLYPH_RATIO, centred.
  p('\t\t<g transform="translate(10 10) scale(0.8333)" fill="none" stroke="var(--bs-lane-glyph)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">');
  p(glyphLines.map((l) => `\t\t\t${l}`).join('\n'));
  p('\t\t</g>');
  p('\t</symbol>');
}

for (const [alias, target] of Object.entries(ALIASES)) {
  if (!entries.some((e) => e.meaning === target)) continue;
  if (alias === target) continue;

  p('');
  p(`\t<!-- alias: templates already say "${alias}" for ${target} -->`);
  p(`\t<symbol id="bs-icon-${alias}" viewBox="0 0 24 24"><use href="#bs-icon-${target}"/></symbol>`);
  p(`\t<symbol id="bs-circle-${alias}" viewBox="0 0 40 40"><use href="#bs-circle-${target}"/></symbol>`);
}

p('');
p('</svg>');

const output = out.join('\n') + '\n';

if (process.argv.includes('--check')) {
  if (readFileSync(OUT, 'utf8') !== output) {
    console.error('icons.svg is stale — run `npm run build:icons`.');
    process.exit(1);
  }
  console.log('icons.svg is up to date with theme.js');
} else {
  writeFileSync(OUT, output);
  const theme_n = entries.filter((e) => e.source === 'theme').length;
  console.log(
    `icons.svg written — ${entries.length} meanings ` +
    `(${theme_n} from the theme vocabulary, ${entries.length - theme_n} website-only), ` +
    `${entries.length * 2 + Object.keys(ALIASES).length * 2} symbols`
  );
}
