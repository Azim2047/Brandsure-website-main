/*
 * The QA suite. `npm run qa`, or `npm test` to run the PHP guard and the
 * render first.
 *
 * Five audits, each one written because it caught something real:
 *
 *   targets   48px touch targets (Layer 4 v4). Found 13 footer links 16px tall
 *             on all 21 pages.
 *   layout    horizontal overflow and clipped text at eight widths.
 *   contrast  WCAG AA. Found ten elements on one under-contrast token.
 *   nojs      every page readable with JavaScript off. This is what stops an
 *             animation layer from hiding content.
 *   motion    nothing left invisible after scrolling, and reduced-motion is
 *             off rather than fast. Found 11 of 12 home sections that could be
 *             left permanently blank by jumping to the bottom of the page.
 *
 * Run one:  node tools/qa/run.mjs targets
 */

import { serve, browser, pages, report, ALL_WIDTHS, MOBILE_WIDTHS } from './lib.mjs';

const MIN_TAP = 48;

/* ---------------------------------------------------------------- targets */

async function targets(ctxFor, files) {
  const fails = [];
  let checked = 0;

  for (const width of [360, 390, 430]) {
    const ctx = await ctxFor({ width, height: 844, touch: true });

    for (const file of files) {
      const p = await ctx.newPage();
      await p.goto(`${ctx.origin}/${file}`, { waitUntil: 'networkidle' });

      const small = await p.evaluate((MIN) => {
        const sel = 'a[href], button, summary, input, select, textarea, [role="button"], [tabindex]:not([tabindex="-1"])';
        const out = [];

        for (const el of document.querySelectorAll(sel)) {
          const cs = getComputedStyle(el);
          if (cs.display === 'none' || cs.visibility === 'hidden' || +cs.opacity === 0) continue;
          if (!el.getClientRects().length) continue;

          // WCAG 2.5.8 exempts a target inline in a block of text, and padding
          // links inside a paragraph would wreck the line spacing around them.
          if (el.closest('.bs-prose')) continue;

          const rects = [...el.getClientRects()];
          let w = Math.max(...rects.map((r) => r.width));
          let h = Math.max(...rects.map((r) => r.height));

          // A stretched link (::after inset:0) has the hit area of its whole
          // card. Measuring the inline box reports a failure that is not real,
          // so ask the browser what a tap at the card's top corners would hit.
          const after = getComputedStyle(el, '::after');

          if (after?.position === 'absolute' && after.content !== 'none') {
            const host = el.offsetParent;

            if (host) {
              const b = host.getBoundingClientRect();
              const hit = [[b.left + 6, b.top + 6], [b.right - 6, b.top + 6]]
                .every(([x, y]) => document.elementFromPoint(x, y)?.closest('a') === el);
              if (hit) { w = b.width; h = b.height; }
            }
          }

          if (h + 0.5 < MIN || w + 0.5 < MIN) {
            out.push(`${el.tagName.toLowerCase()}.${(el.className || '').toString().split(' ')[0]} ${Math.round(w)}x${Math.round(h)}`);
          }
        }

        return out;
      }, MIN_TAP);

      checked++;
      for (const s of new Set(small)) fails.push(`${width}px ${file}: ${s}`);
      await p.close();
    }

    await ctx.close();
  }

  return report('targets ', fails, `no target under ${MIN_TAP}px across ${checked} page-widths`);
}

/* ----------------------------------------------------------------- layout */

async function layout(ctxFor, files) {
  const fails = [];

  for (const width of ALL_WIDTHS) {
    const ctx = await ctxFor({ width, height: 900 });

    for (const file of files) {
      const p = await ctx.newPage();
      await p.goto(`${ctx.origin}/${file}`, { waitUntil: 'networkidle' });

      const r = await p.evaluate(() => {
        const de = document.documentElement;
        const over = de.scrollWidth - de.clientWidth;
        const clipped = [];

        for (const el of document.querySelectorAll('h1,h2,h3,p,td,th,a,button,span')) {
          const cs = getComputedStyle(el);
          // Visually-hidden content clips by definition — the compare table's
          // off-screen thead on phones is correct, not a defect.
          if (cs.clipPath && cs.clipPath !== 'none') continue;
          if (el.closest('thead') && getComputedStyle(el.closest('thead')).clipPath !== 'none') continue;
          if (cs.overflowX === 'auto' || cs.overflow === 'auto') continue;

          if (el.scrollWidth > el.clientWidth + 2 && (el.textContent || '').trim()) {
            clipped.push((el.className || el.tagName).toString().slice(0, 30));
          }
        }

        return { over, clipped: [...new Set(clipped)].slice(0, 3) };
      });

      if (r.over > 0) fails.push(`${width}px ${file}: overflows by ${r.over}px`);
      if (r.clipped.length) fails.push(`${width}px ${file}: clipped ${r.clipped.join(', ')}`);
      await p.close();
    }

    await ctx.close();
  }

  return report('layout  ', fails, `no overflow or clipped text, ${ALL_WIDTHS.length} widths x ${files.length} pages`);
}

/* --------------------------------------------------------------- contrast */

async function contrast(ctxFor, files) {
  const fails = [];
  const ctx = await ctxFor({ width: 390, height: 844 });

  for (const file of files) {
    const p = await ctx.newPage();
    await p.goto(`${ctx.origin}/${file}`, { waitUntil: 'networkidle' });

    const bad = await p.evaluate(() => {
      const lum = (c) => {
        const [r, g, b] = c.map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
      };
      const parse = (s) => (s.match(/[\d.]+/g) || []).slice(0, 3).map(Number);

      const backdrop = (el) => {
        for (let n = el; n; n = n.parentElement) {
          const bg = getComputedStyle(n).backgroundColor;
          const a = (bg.match(/[\d.]+/g) || [])[3];
          if (bg && bg !== 'transparent' && a !== '0') return parse(bg);
        }
        return [255, 255, 255];
      };

      const out = [];

      for (const el of document.querySelectorAll('p,h1,h2,h3,h4,li,a,span,dt,dd,button,summary,label,td,th')) {
        const text = (el.textContent || '').trim();
        if (!text || el.children.length) continue;

        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden' || +cs.opacity === 0) continue;
        if (!el.getClientRects().length) continue;

        const size = parseFloat(cs.fontSize);
        const bold = +cs.fontWeight >= 700;
        const floor = size >= 24 || (size >= 18.66 && bold) ? 3 : 4.5;

        const l1 = lum(parse(cs.color));
        const l2 = lum(backdrop(el));
        const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);

        if (ratio < floor) {
          out.push(`${(el.className || el.tagName).toString().slice(0, 26)} ${ratio.toFixed(2)}:1 (needs ${floor})`);
        }
      }

      return [...new Set(out)];
    });

    for (const b of bad) fails.push(`${file}: ${b}`);
    await p.close();
  }

  await ctx.close();
  return report('contrast', fails, `every element meets its WCAG AA floor, ${files.length} pages`);
}

/* ------------------------------------------------------------------- nojs */

async function nojs(ctxFor, files) {
  const fails = [];
  const ctx = await ctxFor({ width: 390, height: 844, js: false });

  for (const file of files) {
    const p = await ctx.newPage();
    await p.goto(`${ctx.origin}/${file}`, { waitUntil: 'load' });

    const r = await p.evaluate(() => {
      const faded = [...document.querySelectorAll('body *')]
        .filter((el) => parseFloat(getComputedStyle(el).opacity) < 0.9 && el.getBoundingClientRect().height > 0)
        .map((el) => (el.className || el.tagName).toString().slice(0, 30));

      const wa = document.querySelector('[data-bs-wa]');
      const nav = document.querySelector('.bs-header__nav');

      return {
        faded: [...new Set(faded)].filter((c) => !c.includes('footer__logo')).slice(0, 3),
        motionClass: document.documentElement.classList.contains('bs-motion'),
        navReachable: !nav || getComputedStyle(nav).display !== 'none',
        waOptions: wa ? wa.querySelectorAll('.bs-wa__choice').length : null,
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      };
    });

    if (r.faded.length) fails.push(`${file}: invisible with JS off — ${r.faded.join(', ')}`);
    if (r.motionClass) fails.push(`${file}: bs-motion set with JS off`);
    if (!r.navReachable) fails.push(`${file}: nav unreachable with JS off`);
    if (r.waOptions === 0) fails.push(`${file}: WhatsApp options unreachable with JS off`);
    if (r.overflow > 0) fails.push(`${file}: overflows with JS off`);
    await p.close();
  }

  await ctx.close();
  return report('nojs    ', fails, `all ${files.length} pages readable and navigable with JavaScript off`);
}

/* ----------------------------------------------------------------- motion */

async function motion(ctxFor, files) {
  const fails = [];

  for (const reduced of [false, true]) {
    const ctx = await ctxFor({ width: 390, height: 844, reduced });

    for (const file of files) {
      const p = await ctx.newPage();
      await p.goto(`${ctx.origin}/${file}`, { waitUntil: 'networkidle' });

      // Jump straight to the bottom. This is the case an IntersectionObserver
      // misses — sections that go from below the viewport to above it without
      // ever intersecting — and it left 11 of 12 home sections blank.
      await p.evaluate(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'instant' }));
      await p.waitForTimeout(reduced ? 200 : 1000);

      const hidden = await p.evaluate(() =>
        [...document.querySelectorAll('[data-bs-reveal], [data-bs-hero]')]
          .filter((el) => parseFloat(getComputedStyle(el).opacity) < 0.99)
          .map((el) => (el.className || el.tagName).toString().slice(0, 30)));

      const label = reduced ? 'reduced-motion' : 'after full scroll';
      for (const h of new Set(hidden)) fails.push(`${file} (${label}): still invisible — ${h}`);
      await p.close();
    }

    await ctx.close();
  }

  return report('motion  ', fails, 'nothing left invisible after scrolling; reduced-motion lands instantly');
}

/* -------------------------------------------------------------------- run */

const AUDITS = { targets, layout, contrast, nojs, motion };

const wanted = process.argv.slice(2).filter((a) => AUDITS[a]);
const running = wanted.length ? wanted : Object.keys(AUDITS);

const files = await pages();

if (!files.length) {
  console.error('build/preview is empty — run `npm run render` first.');
  process.exit(1);
}

const server = await serve();
const chrome = await browser();

const ctxFor = async ({ width, height, touch = false, js = true, reduced = false }) => {
  const ctx = await chrome.newContext({
    viewport: { width, height },
    hasTouch: touch,
    isMobile: touch,
    javaScriptEnabled: js,
    reducedMotion: reduced ? 'reduce' : 'no-preference',
  });
  ctx.origin = server.origin;
  return ctx;
};

console.log(`QA — ${files.length} surfaces at ${server.origin}\n`);

let failed = 0;
for (const name of running) failed += await AUDITS[name](ctxFor, files);

await chrome.close();
await server.close();

console.log(failed ? `\n${failed} audit(s) failed.` : `\nAll ${running.length} audits passed.`);
process.exit(failed ? 1 : 0);
