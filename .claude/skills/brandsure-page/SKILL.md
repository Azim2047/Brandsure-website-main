---
name: brandsure-page
description: Build, redesign or add interactions to any page of the BrandSure website (brandsure.in, the brandsure-v2 WordPress theme) — Home, Plans, Digital Partner, Growth Partnership, How It Works, Proof, About, Start, the four furniture service pages, blog or legal pages. Use this skill whenever the work touches this repo's theme, page data, components, motion, the WhatsApp panel, plan or pricing copy, or the BSU theme — including when the user just says "do the home page", "make plans more interactive", "add animation", "chat 18", or names a page. It carries the four public-disclosure Overrides that a wrong edit would breach, the theme's lane code and M1–M6 motion grammar, and the QA gates. Load it before editing anything under wp-content/themes/brandsure-v2, not after.
---

# Building a BrandSure page

One page per session. This skill is what makes each session produce the same
*kind* of work, so page nine looks like page one rather than like a different
website.

**Before anything else, read `docs/SOURCE-OF-TRUTH.md`.** This skill is the
working method; that file is the authority. Where they disagree, it wins.

---

## The two things that get people fired

Read `references/disclosure.md` in full before editing copy, data or schema.
The short version, because these are absolute:

1. **The public site never discloses the Growth Partnership commercials.** Not
   `20%`, `80%`, `commission`, `revenue share`, `36 months`, `₹1,20,000`,
   `₹3,00,000`, the weekly payment rhythm, `SWO`, or any GP money example.
   Not in copy, schema, metadata, comments, JSON, JavaScript, alt text or a
   FAQ. The Growth Partnership itself must stay **very** visible — we hide the
   mechanism, not the plan.
2. **Growth Partnership never passes `plan=`.** It passes
   `interest=Growth Partnership`. Agency and Digital Partner may pass `plan=`.

There is no figure for advertising spend anywhere on the public site — no
budget, no range, not even a "suggested" one.

`php wp-content/themes/brandsure-v2/tools/guard.php` enforces much of this and
must pass before you commit. It is not a formality: it has caught a withdrawn
price, a missing caveat and four legal pages carrying a dead phone number.

---

## The six steps

Same shape every time. Steps 1 and 4 are the ones people skip and the ones
that cost a rebuild.

### 1. Read the page as it is

Open its data file (`data/pages/<slug>.json`), its template
(`templates/page-<slug>.php`), and the rendered output. Then answer, in a
sentence each:

- What must the visitor understand first?
- What is the one action?
- **Which of the five questions does this page answer?** (below)
- What can be shortened or moved into optional detail?

Do not skip to the CSS. A page that animates beautifully and answers the wrong
question is worse than the static one, because it looks finished.

### 2. Decide the interactions

**99% of visitors are on a phone.** The reader is a furniture manufacturer or
showroom owner, 35–60, one-handed on Android, possibly inside WhatsApp's
browser, possibly on a lit showroom floor. They will not play with a website.
They arrive with five questions:

| | |
|---|---|
| **Q1** | What exactly do you do, and what do I still do? |
| **Q2** | Which of the three is for me? |
| **Q3** | What does it cost me? |
| **Q4** | Does this work for a business like mine? |
| **Q5** | What actually happens after I message? |

**An interaction that answers none of these does not get built.** That one
filter is what keeps "interactive" from becoming "fidgety", and it is the
difference between a site that feels alive and a site that feels like a demo.

`docs/INTERACTION-PLAN.md` has the agreed inventory and which page gets what.

### 3. Build

CSS first; JavaScript only where a control genuinely needs state.

- **Colour comes from the lane, never by hand.** Set `--bs-lane` and the fill
  follows. Blue is us, green is you, teal is shared, slate is a problem.
- **Motion follows M1–M6**, in the theme's fixed order, at the theme's
  durations. `references/theme.md` has the table and the per-page tempo.
- **Never edit `tokens.css` or `icons.svg`** — both are generated from
  `theme-source/theme.js`. Change the theme file and run `npm run build`.
- Content lives in `data/pages/*.json` for controlled pages, or WordPress for
  editable ones. **Never both** — that is Override 4, and it is why the site
  has one editable copy of everything.

### 4. Prove it degrades

The gates, in order of how much damage they prevent:

```bash
npm test          # guard → render → all five audits
```

- **JavaScript off**: every page fully readable, nav reachable, CTAs working.
  This is what stops an animation layer from costing a reader the content.
- **Reduced motion**: everything lands instantly. Off, not faster — a 40ms
  animation is still an animation.
- **48px touch targets** at 360/390/430.
- **No horizontal overflow** at eight widths.
- **WCAG AA** on every new coloured surface. Green `#22BE6C` on white is
  ~2.4:1: it carries fills and glyphs, never small text. `--bs-green-d` exists
  for green text.
- **No layout shift.** Opacity and transform only.

### 5. Screenshot

Before and after at 390px. `references/qa.md` has the snippet.

### 6. Commit

One page, one commit. Say what changed and why, name the source that required
it, and record anything you found but did not fix.

---

## What not to do

Every line here comes from the theme spec, Layer 4 v4, or the brief — and most
were written after somebody did the thing.

| Never | Because |
|---|---|
| A dark page or section | Theme rule 1: one navy payoff **card** per page. Not a dark page |
| Red or coral for a problem | There is no red in this system. Slate circle, yellow pill |
| Colour a word in a tick-bar headline | The bar **is** the accent. One pattern per headline, never both |
| A rounded-square or bordered icon tile | Theme §3: solid circle, white glyph, the fill is the lane code |
| Animate a money figure through intermediate values | It displays prices that are not real |
| Scroll hijacking, parallax, custom cursors, loading screens | Named in the brief; all fight a one-handed reader on mobile data |
| An animation library, a framework, a bundler | Forbidden by the brief and by v4 §9. The whole payload budget is 600 KB |
| Hover-only behaviour | 99% of visitors have no hover |
| Anything that exits | Theme §6: "Nothing exits." Elements arrive and stay |
| A second WhatsApp launcher on mobile | The sticky bar is the launcher. One control, one action |
| Put the public WhatsApp panel on `/start/` or a legal page | `/start/` has the four-number handoff; legal pages have no CTA at all |
| Invent a testimonial, client logo, result, photo or video URL | Only supplied, approved proof |

---

## Reference files

Read the one you need, when you need it.

- **`references/disclosure.md`** — the four Overrides in full, the guard's 14
  rules, and the approved public wording. **Read before touching copy, data,
  schema or a WhatsApp message.**
- **`references/theme.md`** — the lane code, M1–M6 with web equivalents, the
  firing order, per-page tempo, and the theme's four components. **Read before
  writing CSS or adding motion.**
- **`references/qa.md`** — every command, what each audit catches, the
  screenshot snippet, and the bugs that only a real browser finds.

## Project docs worth knowing

| File | What it settles |
|---|---|
| `docs/SOURCE-OF-TRUTH.md` | The authority for everything. Start here |
| `docs/INTERACTION-PLAN.md` | Which page gets which interaction, and the build order |
| `docs/NODE-TOOLCHAIN.md` | Why Node is build-time only and the site stays PHP |
| `docs/LAYER-4-V4-CHANGE-REGISTER.md` | Current commercial facts and what was withdrawn |
| `docs/OPEN-ITEMS.md` | Unresolved decisions. Check before assuming a fact |
| `docs/BUILD-MANIFEST.md` | What each chat did |
