# QA — commands, gates, and the bugs only a browser finds

## Commands

```bash
npm install                 # once
npm run build               # theme.js -> tokens.css + icons.svg
npm run render              # all 21 surfaces -> build/preview/
npm run dev                 # build + render + serve + watch
npm test                    # guard -> render -> all five audits
npm run qa targets          # one audit: targets | layout | contrast | nojs | motion
npm run check               # test, and fail if generated files are stale (CI)
```

`build/` is git-ignored. Nothing in it is ever committed or deployed.

## The gates

Everything here is measurable. "It looks fine" is not a result.

| Gate | Standard |
|---|---|
| Guard | `PASS — 0 errors` |
| Touch targets | ≥48px at 360/390/430 |
| Layout | no horizontal overflow or clipped text at 360/375/390/412/430/768/1024/1280 |
| Contrast | WCAG AA — 4.5:1 body, 3:1 large |
| JavaScript off | every page readable, nav reachable, WhatsApp options reachable |
| Reduced motion | nothing mid-transition; everything lands instantly |
| Layout shift | CLS 0 — opacity and transform only |
| Payload | non-video under 600 KB, LCP under 1.5s on 4G |

### Exemptions that are correct, not bugs

- Links inside `.bs-prose` — WCAG 2.5.8 exempts a target inline in a block of
  text, and padding links inside a paragraph wrecks the line spacing.
- The compare table's `<thead>` on phones — visually hidden, so it "clips" by
  definition. It stays in the accessibility tree on purpose.
- `.bs-footer__logo` at `opacity: .95` — deliberate.
- A stretched card link measures as its inline box. The audit probes what a tap
  at the card's **top** corners actually hits (the bottom corners hold a second
  link, so probing there gives a false negative).

## Screenshots

```js
import { chromium } from 'playwright';
const b = await chromium.launch();
const p = await (await b.newContext({
  viewport: { width: 390, height: 844 }, deviceScaleFactor: 2,
  hasTouch: true, isMobile: true,
})).newPage();
await p.goto('http://127.0.0.1:8080/plans.html', { waitUntil: 'networkidle' });
await p.waitForTimeout(1100);                 // let the entrance finish
await p.screenshot({ path: 'shot.png' });
await b.close();
```

**Scroll with `behavior: 'instant'`.** The page sets `scroll-behavior: smooth`,
so a plain `scrollTo` or `scrollIntoView` captures mid-flight and you get a
screenshot of a half-scrolled page with a white band in it.

## Bugs a browser found and reading the code did not

Keep these in mind — they are the shapes of mistake this stack produces.

**An invisible scrim swallowed every tap on the site.** `.bs-wa__scrim` had
`display: block` under a class chain. `[hidden]` gets `display: none` from the
UA stylesheet at almost no specificity, so the author rule beat it — leaving a
fixed, full-viewport, `opacity: 0` element over every page while supposedly
closed. **Nothing was clickable**, and nothing looked wrong. Fixed with
`:not([hidden])` plus a global `[hidden] { display: none !important }`.

**Eleven of twelve home sections could be left permanently blank.** An
IntersectionObserver never fires for an element that never intersects — see
`theme.md` §3. The reveal is a scroll sweep now.

**The scroll restore glided.** Closing the WhatsApp sheet restored the reader's
position with a plain `scrollTo`, which *animated* because the page sets
`scroll-behavior: smooth`; a touch mid-glide stranded them elsewhere. Restoring
a position is not navigation — `behavior: 'instant'`.

**A generated stylesheet emitted a declaration with no semicolon.** Invalid CSS
does not throw. It vanishes, taking the next declaration with it, and the only
symptom is a colour quietly falling back. `build-tokens.mjs` now checks its own
output, and the check has a negative test.

**The theme's own `WARN_TXT` fails WCAG** — 4.00:1 on white, 3.62:1 on the strip
it was specified for. Caught by the contrast audit within minutes of generating
tokens from the theme for the first time. A supplied value being wrong is a
real possibility; measure rather than assume.

## When an audit fails

1. **Reproduce it** before changing anything. `npm run qa <name>` is seconds.
2. **Ask whether the audit is right.** Three findings in Chat 13 were the
   auditor's wrong assumptions, not site defects. When the check is wrong, fix
   the check and write the reason at it — a corrected check is worth as much as
   a corrected page.
3. **Fix the cause, not the symptom.** If a guard rule fires on approved copy,
   the rule is wrong. Never the escape hatch.
4. **Re-run the whole suite**, not just the audit you were working on. The
   token regeneration that fixed one thing broke contrast somewhere else.
