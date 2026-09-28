/*
 * theme.js  ->  assets/css/tokens.css
 *
 * The theme file says it plainly: "No hex literal belongs in a deck file —
 * import from here." This extends that rule to the website. Every colour, type
 * size and component surface in tokens.css is generated from the supplied
 * theme, so the two cannot drift.
 *
 * Hand-transcription is what this replaces, and it is worth recording what
 * transcription cost: tokens.css carried 13 of the theme's 23 colours, had no
 * teal token outside a gradient, and encoded the lane system — the theme's
 * central idea — nowhere at all.
 *
 * TWO THINGS THIS FILE DOES NOT TAKE FROM THE THEME VERBATIM.
 *
 * 1. Type sizes. The theme's scale is in POINTS, for a 13.33x7.5in slide.
 *    Layer 4 v4 s8: "Translate slide typography into responsive web sizes
 *    rather than copying points into CSS." Copying 27pt into a stylesheet
 *    would be the exact mistake it names, so the web scale stays hand-written
 *    below and is only annotated with its slide ancestor.
 *
 * 2. Three colours that fail WCAG AA as text. SUB #7F96A0 is 3.10:1 on white;
 *    WARN #FCC851 on WARN_BG is 1.40:1; and WARN_TXT #9A7B2E — the theme's own
 *    remedy for the second — is itself only 4.00:1 on white and 3.62:1 on the
 *    strip it was written for. Each is overridden below with its measurement
 *    attached, and all three are raised in OPEN-ITEMS item 9.
 *
 *    A generator that silently emitted unreadable text would be worse than the
 *    hand-transcription it replaces, because it would carry the authority of
 *    having come from the theme. On a slide these sit under a narrator; on a
 *    page they have to be read.
 *
 * Usage:  node tools/build-tokens.mjs [--check]
 *         --check exits non-zero if the file on disk is stale (for CI).
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { loadTheme, THEME_SHA256 } from './lib/load-theme.mjs';

const HERE = dirname(fileURLToPath(import.meta.url));
const OUT = resolve(HERE, '../wp-content/themes/brandsure-v2/assets/css/tokens.css');

const theme = loadTheme();
const hex = (v) => '#' + String(v).toUpperCase();

/* ------------------------------------------------------------------------ *
 * Colour
 *
 * Grouped the way the theme groups them, with each token's frequency count
 * from the deck XML where the spec gives one — that count is the evidence the
 * value is real and not someone's guess.
 * ------------------------------------------------------------------------ */

const COLOUR_GROUPS = [
  ['Core — the lane colours carry meaning, not decoration', [
    ['ink',        'INK',       'headlines · dark payoff card · the customer\'s step'],
    ['blue',       'BLUE',      "BrandSure's lane. Icon circles, tick bar, numbers"],
    ['blue-d',     'BLUE_D',    'pressed / darker blue'],
    ['green',      'GREEN',     'YOUR lane. A sale, ticks, included'],
    ['green-d',    'GREEN_D',   'green TEXT on light — see the contrast note below'],
    ['teal',       'TEAL',      'shared lane · your team inside our journey'],
    ['aqua',       'AQUA',      'connectors, chevrons, the glow, progress'],
  ]],
  ['Dark panels — a component, never a page', [
    ['navy',       'NAVY',      'hero panel gradient start'],
    ['blue-deep',  'BLUE_DEEP', 'hero panel gradient, deep fills'],
    ['navy-2',     'NAVY_2',    ''],
  ]],
  ['Text', [
    ['white',      'WHITE',     ''],
    ['body',       'BODY',      'paragraphs · problem-state icon circles'],
    ['muted',      'MUTED',     'body text ON dark cards'],
    ['muted-d',    'MUTED_D',   'secondary on dark, struck-through / "before"'],
  ]],
  ['Surfaces and borders', [
    ['bg',         'BG',        ''],
    ['border',     'BORDER',    'every card edge'],
    ['border-2',   'BORDER_2',  'stronger card edge, chips'],
    ['border-g',   'BORDER_G',  'the green lane card\'s border'],
    ['mist',       'MIST',      'tinted panel fill, chip fill'],
    ['mist-2',     'MIST_2',    'money that belongs to nobody — GST, material, transport'],
    ['tint-g',     'TINT_G',    ''],
    ['tint-g2',    'TINT_G2',   ''],
  ]],
  ['Caution — the system\'s ONLY warm colour. There is no red or coral.', [
    ['warn',       'WARN',      'SALE LOST pill, caution tag'],
    ['warn-bg',    'WARN_BG',   'disclaimer strip fill'],
    ['warn-line',  'WARN_LINE', 'disclaimer strip border'],
    ['warn-txt',   'WARN_TXT',  'disclaimer strip text'],
  ]],
];

/* The deviations. Each carries the measurement that forced it, so nobody has
   to take "it looked wrong" on trust. */
const DEVIATIONS = {
  WARN_TXT: {
    value: '#8A6410',
    why: [
      'THEME VALUE #9A7B2E IS OVERRIDDEN — deliberate, measured, documented.',
      "The theme's own warm text colour does not pass WCAG AA anywhere it is",
      'used: 4.00:1 on white, and 3.62:1 on the #FBF3DF disclaimer strip it was',
      'specified for. Both are below the 4.5:1 a body-size caveat needs, and a',
      'caveat nobody can read is the one thing a caveat may not be.',
      'The value below is the same warm brown at 5.37:1 on white and 4.85:1 on',
      'the strip. Found by the contrast audit within minutes of generating',
      'tokens from the theme for the first time. OPEN-ITEMS item 9.',
    ],
  },
  SUB: {
    value: '#5A7080',
    why: [
      'THEME VALUE #7F96A0 IS OVERRIDDEN — deliberate, measured, documented.',
      'Every use of this token in the website sets the colour of text or of a',
      'glyph beside text: captions, post dates, lane labels, the byline, the',
      'case context. #7F96A0 measures 3.10:1 on white and 2.88:1 on the tint,',
      'below the 4.5:1 body-size text needs. On a slide it sits under a',
      'narrator and nobody has to read it; on a page it is read.',
      'The value below is the same cool grey at 5.17:1 on white, 4.79:1 on the',
      'tint, and still clearly lighter than --bs-body (6.09:1) so the hierarchy',
      'holds. OPEN-ITEMS item 9.',
    ],
  },
};

/* Extra tokens with no slide equivalent, because a page has controls and a
   slide does not. */
const WEB_ONLY = `
  /* --- The lane code, as CSS -------------------------------------------- *
   *
   * theme.js derives an icon's colour from its MEANING: ICON_LANE maps each
   * glyph to a lane, CIRCLE maps each lane to a fill. Colour is never chosen
   * by hand, which is what makes "blue is us, green is you" readable without
   * a legend.
   *
   * These four tokens are that rule in CSS. A component sets --bs-lane on a
   * circle and the fill follows; it never names a colour. Written the other
   * way round — each component picking blue or green itself — the meaning
   * survives only as long as everybody remembers it, which is how the build
   * ended up with no teal token at all.
   */
  --bs-lane-bs:      var(--bs-blue);    /* BrandSure does it        */
  --bs-lane-you:     var(--bs-green);   /* you do it · a sale       */
  --bs-lane-shared:  var(--bs-teal);    /* shared · your team       */
  --bs-lane-problem: var(--bs-body);    /* a problem · a leak       */
  --bs-lane-quiet:   var(--bs-mist);    /* quiet, on a tinted panel */

  /* Default lane, so a circle with no lane set is still legible. */
  --bs-lane:         var(--bs-lane-bs);
  --bs-lane-glyph:   var(--bs-white);
`;

/* Type. Hand-written on purpose — see the header. */
const TYPE = `
  /* --- Typography ------------------------------------------------------- *
   *
   * Inter, per the theme (2,856 uses in the deck). The SIZES are web sizes,
   * not the theme's points: v4 s8 requires translation rather than copying.
   * Each is annotated with the slide role it descends from.
   */
  --bs-font: "Inter", system-ui, -apple-system, "Segoe UI", Roboto,
             "Helvetica Neue", Arial, sans-serif;

  --bs-fs-header:      2.5rem;   /* 40px · 700 · from HEADLINE 27pt        */
  --bs-fs-subheader:   1.25rem;  /* 20px · 600 · from CARD 15pt            */
  --bs-fs-body-lg:     1rem;     /* 16px · 400 · from SUB 13.5pt           */
  --bs-fs-note:        1rem;     /* 16px · 400 · qualifying prose          */
  --bs-fs-body:        0.875rem; /* 14px · 400 · from LABEL 12.5pt         */
  --bs-fs-button:      0.875rem; /* 14px · 600 · web only                  */

  --bs-fw-bold:        700;
  --bs-fw-semibold:    600;
  --bs-fw-regular:     400;

  --bs-lh-tight:       1.15;
  --bs-lh-snug:        1.35;
  --bs-lh-body:        1.6;
`;

const SHAPE_AND_LAYOUT = `
  /* --- Shape · the theme's card is radius 12px, 1px BORDER, cool shadow -- */
  --bs-radius-button:  10px;
  --bs-radius-card:    12px;
  --bs-radius-tag:     16px;
  --bs-radius-pill:    100px;

  --bs-shadow-card:       0 5px 9px rgba(17, 110, 152, 0.07);
  --bs-shadow-card-hover: 0 10px 22px rgba(17, 110, 152, 0.10);

  /* --- The two permitted gradients, plus the glow ----------------------- */
  --bs-gradient-hero:      linear-gradient(135deg, ${hex(theme.HERO_PANEL.from)} 0%, ${hex(theme.HERO_PANEL.to)} 100%);
  --bs-gradient-progress:  linear-gradient(135deg, ${hex(theme.PROGRESS.fill[0])} 0%, ${hex(theme.PROGRESS.fill[1])} 100%);
  /* The name brandsure.css already uses for the same aqua->teal fill. */
  --bs-gradient-aqua:      var(--bs-gradient-progress);
  --bs-gradient-signature: linear-gradient(135deg, ${hex(theme.C.BLUE)} 0%, ${hex(theme.C.AQUA)} 65%, #C6E7B9 100%);
  --bs-glow-aqua:          ${hex(theme.C.AQUA)};
  --bs-glow-warm:          ${hex(theme.C.WARN)};

  /* --- Spacing · 4px base ----------------------------------------------- */
  --bs-space-1: 0.25rem;  --bs-space-2: 0.5rem;   --bs-space-3: 0.75rem;
  --bs-space-4: 1rem;     --bs-space-5: 1.5rem;   --bs-space-6: 2rem;
  --bs-space-7: 3rem;     --bs-space-8: 4rem;

  /* --- Layout · designed at 390px first --------------------------------- */
  --bs-gutter:         1.25rem;
  --bs-container:      75rem;
  --bs-prose:          40rem;
  --bs-sticky-bar-h:   3.5rem;
  --bs-header-h:       3.5rem;

  /* Minimum size of anything a thumb has to hit. Layer 4 v4: "touch targets
     at least 48 px." Every interactive rule reads this; none sets its own. */
  --bs-tap:            3rem;
`;

/* Motion, straight from theme.js MOTION. Durations are the theme's. */
const motionTokens = () => {
  const m = theme.MOTION;
  const ms = (s) => `${Math.round(s * 1000)}ms`;
  return `
  /* --- Motion · the deck's grammar, translated --------------------------- *
   *
   * Generated from theme.js MOTION, so the website moves at the same speeds
   * as the videos. The firing order is fixed by the spec and belongs in the
   * stylesheet that uses these, not here:
   *   tick bar (M2) -> headline (M1) -> sub (M1) -> picture -> dark card
   *   -> green accents (M4, always last)
   */
  --bs-m1-settle:  ${ms(m.M1_SETTLE.dur)};   /* float in from bottom      */
  --bs-m2-draw:    ${ms(m.M2_DRAW.dur)};   /* wipe from left            */
  --bs-m3-pop:     ${ms(m.M3_POP.dur)};   /* zoom, icon circles only   */
  --bs-m4-verify:  ${ms(m.M4_VERIFY.dur + m.M4_VERIFY.then.dur)};   /* zoom then one pulse       */
  --bs-m5-stagger: ${ms(m.M5_FLOW.stagger)};   /* per node in a chain       */
  --bs-m6-grow:    ${ms(m.M6_GROW.dur)};   /* wipe from bottom          */
  --bs-page-fade:  ${ms(m.TRANSITION.dur)};   /* between pages             */
  --bs-page-gate:  ${ms(m.TRANSITION_GATE.dur)};   /* push into /start/         */

  /* Interaction feedback. Not from the deck — a slide has no buttons, so
     these have no theme value and are the web's own.

     --bs-transition KEEPS ITS 160ms MEANING. Twenty-six rules in
     brandsure.css use it for hovers, presses and colour changes. An early
     version of this generator pointed it at the theme's 500ms slide fade,
     which would have turned every button hover on the site into a half-second
     smear — a silent regression, since nothing errors and nothing looks
     broken in a screenshot. Page transitions are --bs-page-fade above. */
  --bs-transition: 160ms;
  --bs-t-fast:     120ms;
  --bs-t-base:     160ms;

  --bs-ease-settle: cubic-bezier(0.22, 1, 0.36, 1);
  --bs-ease-sheet:  cubic-bezier(0.32, 0.72, 0, 1);
`;
};

/* ------------------------------------------------------------------------ */

const lines = [];
const push = (s = '') => lines.push(s);

push('/* ==========================================================================');
push('   BrandSure v2 — Design Tokens');
push('');
push('   GENERATED FILE. Do not edit by hand — `npm run build:tokens` overwrites it.');
push('   Source: theme-source/theme.js (BSU Master Theme v3, 4 Sep 2026)');
push(`   sha256: ${THEME_SHA256}`);
push('');
push('   To change a colour, change the theme file and regenerate. The point of');
push('   generating this is that the website and the video decks cannot drift:');
push('   the theme file is the single source for both, which is what it asks for');
push('   ("No hex literal belongs in a deck file — import from here").');
push('');
push('   Forbidden here and everywhere: the older presentation identity —');
push('   navy #0E2439, gold #C79A4B, cream #F3ECDD, terracotta #A85A32, Bookman');
push('   Old Style. Theme v3 supersedes those. There is no red or coral in this');
push('   system at all: a problem is a slate circle, its consequence a yellow pill.');
push('   ========================================================================== */');
push('');
push(':root {');

for (const [heading, tokens] of COLOUR_GROUPS) {
  push('');
  push(`  /* --- ${heading} ${'-'.repeat(Math.max(3, 68 - heading.length))} */`);

  for (const [name, key, note] of tokens) {
    const themeValue = theme.C[key];

    if (!themeValue) {
      throw new Error(`theme.js has no colour ${key} — the theme file changed shape.`);
    }

    const dev = DEVIATIONS[key];

    if (dev) {
      push('');
      for (const line of dev.why) push(`  /* ${line} */`);
      push(`  --bs-${name}:`.padEnd(24) + dev.value + ';' + ` /* theme says ${hex(themeValue)} */`);
      continue;
    }

    const decl = `  --bs-${name}:`.padEnd(24) + hex(themeValue) + ';';
    push(note ? `${decl.padEnd(40)} /* ${note} */` : decl);
  }
}

// SUB is a deviation and is not in the groups above; emit it explicitly.
push('');
for (const line of DEVIATIONS.SUB.why) push(`  /* ${line} */`);
push('  --bs-subtle:'.padEnd(24) + DEVIATIONS.SUB.value + ';' + `           /* theme: ${hex(theme.C.SUB)} */`);

// Aliases the existing stylesheet already uses, kept so this is a drop-in.
push('');
push('  /* --- Aliases used by the existing stylesheet -------------------------- */');
push('  --bs-headline:        var(--bs-ink);');
push('  --bs-on-dark:         var(--bs-white);');
push('  --bs-on-dark-muted:   var(--bs-muted);');
push('  --bs-bg-footer:       var(--bs-ink);');
push('  --bs-border-light:    var(--bs-mist);');
push('  --bs-border-secondary-btn: var(--bs-border-2);');
push('  --bs-secondary-btn-hover-bg: var(--bs-tint-g2);');
push('  --bs-meter-track:     var(--bs-mist-2);');
push('  --bs-tag-pending-bg:  var(--bs-mist);');
push('  --bs-tag-pending-border: var(--bs-border-2);');
push('  --bs-green-dot:       var(--bs-green);');
push('  --bs-green-verification: #C6E7B9;');
push('  --bs-green-verified:  #A6DA8E;');
push('  --bs-green-verified-border: #7DCC67;');
push('  --bs-yellow:          var(--bs-warn);');
push('  --bs-yellow-bg:       var(--bs-warn-bg);');
push('  --bs-yellow-border:   var(--bs-warn-line);');
push('  --bs-blue-hover:      var(--bs-blue-d);');
push('  --bs-pattern:         #E4F4F3;');
push('  --bs-pattern-opacity: 0.04;');
push('');
push('  /* WARN #FCC851 on WARN_BG #FBF3DF measures 1.40:1 — invisible as text.');
push('     The theme\'s own WARN_TXT is the readable pairing and is used wherever');
push('     the yellow carries a word or a glyph. OPEN-ITEMS item 9. */');
push('  --bs-yellow-text:     var(--bs-warn-txt);');

push(WEB_ONLY.trimEnd());
push(TYPE.trimEnd());
push(SHAPE_AND_LAYOUT.trimEnd());
push(motionTokens().trimEnd());
push('}');
push('');
push('/* Larger type only once there is room for it. Mobile is the source layout;');
push('   desktop is the expanded version, never the other way round. */');
push('@media (min-width: 48rem) { :root { --bs-gutter: 2rem; } }');
push('');
push('@media (min-width: 64rem) {');
push('  :root {');
push('    --bs-fs-header:    3rem;');
push('    --bs-fs-subheader: 1.375rem;');
push('    --bs-gutter:       2.5rem;');
push('  }');
push('}');
push('');
push('/* Reduced motion turns motion OFF, not down. Someone who asked their');
push('   operating system for less movement asked for a reason, and a 40ms');
push('   version of an animation is still an animation. */');
push('@media (prefers-reduced-motion: reduce) {');
push('  :root {');
push('    --bs-m1-settle: 0ms; --bs-m2-draw: 0ms; --bs-m3-pop: 0ms;');
push('    --bs-m4-verify: 0ms; --bs-m5-stagger: 0ms; --bs-m6-grow: 0ms;');
push('    --bs-transition: 0ms; --bs-t-fast: 0ms; --bs-t-base: 0ms;');
push('  }');
push('}');
push('');

const output = lines.join('\n');

/*
 * Every custom-property line must end in a semicolon.
 *
 * Trivial-looking, and it earned its place immediately: the first version of
 * the deviation branch above built its line with a .replace() that dropped the
 * semicolon, so --bs-warn-txt merged into the next declaration and both were
 * silently discarded by the browser. Invalid CSS does not throw — it vanishes,
 * and the only symptom is a colour quietly falling back to something else.
 */
const withoutComments = output.replace(/\/\*[\s\S]*?\*\//g, '');
const bad = [];

{
  // A declaration may wrap across lines — --bs-font does. So walk forward from
  // each opening line until a semicolon closes it, and only complain if the
  // next declaration or the closing brace arrives first.
  const src = withoutComments.split('\n');

  for (let i = 0; i < src.length; i++) {
    if (!/^\s*--bs-[a-z0-9-]+\s*:/.test(src[i])) continue;

    let closed = false;

    for (let j = i; j < src.length; j++) {
      // Order matters. Checking for the semicolon first lets the NEXT
      // declaration's semicolon close this one, which is exactly the bug this
      // is here to catch — so the boundary test has to come first.
      if (j > i && (/^\s*--bs-/.test(src[j]) || src[j].includes('}'))) break;
      if (src[j].includes(';')) { closed = true; break; }
    }

    if (!closed) bad.push([i + 1, src[i]]);
  }
}

if (bad.length) {
  console.error('Generated invalid CSS — these declarations have no semicolon:');
  for (const [n, line] of bad) console.error(`  line ${n}: ${line.trim()}`);
  process.exit(1);
}

if (process.argv.includes('--check')) {
  const current = readFileSync(OUT, 'utf8');
  if (current !== output) {
    console.error('tokens.css is stale — run `npm run build:tokens`.');
    process.exit(1);
  }
  console.log('tokens.css is up to date with theme.js');
} else {
  writeFileSync(OUT, output);
  const count = COLOUR_GROUPS.reduce((n, [, t]) => n + t.length, 0) + 1;
  console.log(`tokens.css written — ${count} theme colours, 5 lane tokens, 7 motion durations`);
}
