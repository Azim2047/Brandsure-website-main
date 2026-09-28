# Node.js in this project — where it runs, and where it must not

*Chat 17. What Node does here, why the shipped site stays PHP, and how to use the toolchain.*

---

## 1. Two corrections to start from

**The site is not HTML and CSS only.** It is a WordPress theme:

| | |
|---|---|
| PHP | **64 files, 7,125 lines** — templates, components, the ownership locks, SEO, schema, redirects, the guard |
| JavaScript | **1 file, 896 lines** — the mobile nav, the session code, the event beacon, the gate's live WhatsApp link, the video player, the WhatsApp panel, the motion layer |
| CSS | 3 files, ~2,900 lines |
| JSON | 16 data files — every page's content |

So JavaScript is already there and already doing the interactive work. What the site is short of is **interactions** — things a reader can operate — which is what `INTERACTION-PLAN.md` is about. Adding Node.js does not by itself add one interaction.

**brandsure.in cannot run a Node server.** Verified against the live site, not assumed:

```
blog_id 254504637 · platform "atomic" · is_site_launched true · is_coming_soon false
```

WordPress.com Atomic serves PHP. It gives SSH and SFTP, but it does not host a long-running Node process, and there is no port to bind. A Next/Express/Nuxt rebuild would mean leaving the hosting, the domain setup, the WordPress admin BrandSure edits content in, and 16 chats of work — and Layer 4 v4 §9 says the opposite in terms:

> Keep the existing WordPress structure if it matches v3… **Do not migrate to another framework just to apply this update.**

**(That `is_coming_soon: false` is new since Chat 14, when the site was still hidden. brandsure.in is public now. Worth confirming the deployed theme matches this branch — the network policy here blocks the live domain, so I could not check it from this session.)**

---

## 2. The boundary

```
   BUILD TIME                      │        RUN TIME
   Node.js — this machine, CI      │        PHP — WordPress.com Atomic
   ────────────────────────────────┼──────────────────────────────────
   theme.js  ──► tokens.css        │        A visitor gets
   theme.js  ──► icons.svg         │          · PHP-rendered HTML
   render harness ──► build/preview│          · one plain .css file
   Playwright QA                   │          · one plain .js file
   the PHP guard                   │          · an SVG sprite
                                   │
   Never shipped. build/ is        │        No Node. No bundler runtime.
   git-ignored.                    │        No hydration. No build output
                                   │        the browser has to interpret.
```

Node produces **plain files a PHP theme enqueues**. Nothing it makes needs Node to run.

---

## 3. What Node actually buys — and it is more than tooling convenience

### The headline: the theme file becomes executable

`theme.js` — the file BrandSure supplied — is a **CommonJS module**. Node can load it:

```
theme loaded — 27 colours, 12 type sizes, 29 icon meanings, 4 lanes, 8 motion moves
   ad     -> megaphone    lane bs      circle #116E98
   owner  -> user-round   lane you     circle #22BE6C
   worker -> hard-hat     lane shared  circle #30A4A0
   leak   -> droplets     lane problem circle #4B6673
```

The file states its own rule at the top: *"No hex literal belongs in a deck file — import from here."* Until now the website could not import from it, so a person transcribed it. Transcription cost exactly what transcription costs:

| | Before | After |
|---|---|---|
| Theme colours in `tokens.css` | 13 of 23 | **27 of 27** |
| Teal (the shared lane) | only inside a gradient | a real token |
| The lane system in CSS | **nowhere** | 5 `--bs-lane-*` tokens |
| Icon meanings with a glyph | 13 of 29 | **29 of 29** |
| Icon form | bordered rounded squares — v1's *rejected* form | solid circles, the lane as the fill |
| Glyph source | Lucide, plus two I drew by hand | **all Lucide, unmodified, 2px stroke** |
| Motion durations | hand-typed | generated from `MOTION` |

That is not tidying. The lane code — *blue is us, green is you* — is the theme's central idea and the site's whole value proposition in one colour rule, and it existed in the CSS **not at all**. The Lane Switch in `INTERACTION-PLAN.md` is only buildable because `--bs-lane` now exists.

### The rest

| Command | What it does |
|---|---|
| `npm run build` | Regenerates `tokens.css` and `icons.svg` from `theme-source/theme.js` |
| `npm run render` | Renders all 21 public surfaces to `build/preview/` as complete documents |
| `npm run dev` | Renders, serves, and re-renders on change — for building a page and watching it |
| `npm run qa` | The five browser audits |
| `npm run guard` | The PHP build guard (14 rules) |
| `npm test` | guard → render → qa |
| `npm run check` | Also fails if the generated files are stale. This is the CI command |

Until now the render harness and the audits lived in `/tmp`. They died twice mid-session. **A test suite in `/tmp` is a test suite that exists until the next session and then does not** — which is the real reason they are in the repo.

---

## 4. What is deliberately *not* here

| Not used | Why |
|---|---|
| A bundler (Vite, webpack, esbuild) | One 896-line JS file and three stylesheets. A bundler would add a build step between editing and seeing, for no reduction in bytes |
| A framework (React, Vue, Next) | Forbidden by the original brief and by v4 §9; the hosting cannot serve it; and the interactions planned are DOM toggles, not application state |
| An animation library (GSAP, AOS, Framer) | The brief forbids it for effects simple code achieves. The whole non-video payload budget is 600 KB |
| A CSS framework | Every colour and size comes from the theme file. A framework would be a second, competing source |
| Node in production | Atomic serves PHP |

The dependency list is **two devDependencies**: `lucide-static` (the theme's own icon set) and `playwright` (the audits). Neither ships.

---

## 5. Using it

```bash
npm install                  # once
npm run dev                  # build, render, serve, watch  → http://127.0.0.1:8080

npm test                     # guard + render + all five audits
npm run qa targets           # just one audit
```

**Editing a theme colour:** change `theme-source/theme.js`, run `npm run build`, commit the generated diff alongside it. Never edit `tokens.css` or `icons.svg` by hand — both carry a header saying so, and `npm run check` fails if they are stale.

**A new theme file from BrandSure:** replace the two files in `theme-source/`, run `npm run build`, and the diff on the generated files is the complete answer to "what changed".

### The one rule that keeps the boundary honest

**Nothing in `build/` is ever committed or deployed.** It is git-ignored. If a future change makes the shipped site depend on something Node produced at build time in a way PHP cannot reproduce, that is the boundary breaking — and the symptom will be the live site differing from the preview.

---

## 6. The audits, and what each one caught

Each exists because it found something a screenshot would not have.

| Audit | Found |
|---|---|
| `targets` | 13 footer links **16px tall** on all 21 pages, and the menu button at 34px wide |
| `layout` | Overflow and clipping at 8 widths; the compare table's off-screen `thead` correctly exempted |
| `contrast` | Ten elements on one under-contrast token, across seven pages |
| `nojs` | That the motion layer must not be able to hide content — the gate for every animation added from here |
| `motion` | **11 of 12 home-page sections could be left permanently blank** by jumping to the bottom of the page, because an IntersectionObserver never fires for an element that never intersects |

The `nojs` and `motion` audits are the two that matter most for what comes next. The interaction plan adds a lot of movement, and these are what stop movement from costing a reader the content.
