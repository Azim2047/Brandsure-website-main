# tools/

Node and PHP tooling. **None of it ships** — brandsure.in is WordPress.com
Atomic and serves PHP. See `docs/NODE-TOOLCHAIN.md` for the boundary.

| File | What it is |
|---|---|
| `lib/load-theme.mjs` | Loads the supplied `theme-source/theme.js`. It is CommonJS and this package is ESM, so it is evaluated in a sandbox rather than renamed — the file stays byte-identical to the one BrandSure sent |
| `build-tokens.mjs` | `theme.js` → `assets/css/tokens.css`. Checks its own output is valid CSS |
| `build-icons.mjs` | `theme.js` ICON vocabulary → `assets/img/icons.svg`, using real Lucide glyphs, with the lane as the circle fill |
| `render.php` | Renders all 21 public surfaces to `build/preview/` by stubbing WordPress and including the real templates |
| `dev.mjs` | build + render + serve + watch |
| `qa/lib.mjs` | Preview server and browser plumbing for the audits |
| `qa/run.mjs` | The five audits: targets, layout, contrast, nojs, motion |

Both generators take `--check`, which exits non-zero if the file on disk is
stale. `npm run check` uses it — that is what stops a hand-edit of a generated
file from surviving review.

## Why render.php stubs WordPress instead of running it

A WordPress install cannot start in CI in under a second, and the audits need
to run on every change. The harness stubs about 80 WordPress functions and then
includes the **real** templates, the **real** components and the **real** data —
so its output is the theme's own, not a mock of it. That is the only reason the
audits mean anything.

Where a stub and WordPress would disagree, the stub is wrong and the test is
worthless. So the stubs stay dumb: `esc_html` really escapes, `is_page` really
compares the slug, and nothing pretends to have a database.
