# The interaction and motion plan

*Chat 16 — planning only, nothing built. How `brandsure-v2` goes from crisp-but-static to
genuinely interactive, one page at a time, using the BSU Master Theme v3 as the source.*

---

## 0. The one thing that makes this easy

**The theme file already contains a motion grammar.** §6 of `BSU_Master_Theme_Spec_v3.md`
defines six moves (M1–M6), the order they fire in, and a different tempo for each of the five
videos. `theme.js` exports them as `MOTION` and maps every component to one via
`COMPONENT_MOTION`.

So there is nothing to invent. The job is a translation: **the deck's motion grammar becomes the
website's motion grammar**, move for move. A deck plays at the viewer; a web page answers back —
that difference is where interactivity gets added, and nowhere else.

The uploaded zip is **byte-identical** to `ACTIVE/Theme/` inside the Layer 4 pack (verified by
sha256 on both the spec and `theme.js`). Same authority, no conflict, nothing to reconcile.

**One caveat that governs everything below.** This is a spec for *video decks*, not for a
website. Layer 4 v4 §8 gives the conversion rule, and it is the only licence to adapt:

> Website adaptation: light surfaces with restrained navy callouts, consistent lane meanings.
> Translate slide typography into responsive web sizes rather than copying points into CSS.

So: point sizes never become CSS pixels; slide layout never becomes page layout. What *does*
carry over one-to-one is the **lane colour system**, the **icon form**, the **four components**
and the **motion grammar**. Those four are the plan.

---

## 1. Three gaps between the theme and what is built

Measured, not asserted.

> **Status, Chat 17.** All three gaps below are now **closed structurally** —
> `tokens.css` and `icons.svg` are generated from `theme-source/theme.js` by
> `npm run build`, so the theme file is the single source and hand-transcription
> is out of the loop. The findings are kept as written because they are the
> reason the generator exists, and because they say what to watch for if anyone
> is ever tempted to hand-edit a generated file again.
>
> Generating also turned up a fourth thing the transcription had hidden: the
> theme's own `WARN_TXT #9A7B2E` fails WCAG AA at 4.00:1 on white and 3.62:1 on
> the disclaimer strip it was written for. It is overridden with its
> measurement attached. See `docs/NODE-TOOLCHAIN.md`.

### Gap 1 — the icon form is wrong

The theme could not be more emphatic. §3 is titled *"Icons — white glyph, solid circle"* and
says: *"The only icon form in the system… **The circle fill is the lane code** — no borders, no
tiles, no rounded squares."* The v3 correction table exists largely to kill the alternatives.

The build uses `2.75rem` rounded squares with a 1px border and a `--bs-radius-button` corner
(`.bs-chain__tile`), and elsewhere bare stroke glyphs inheriting `currentColor`.

That is **v1's rejected form**, reintroduced by accident. It also costs the site the thing the
circle buys: colour-as-meaning without a legend.

### Gap 2 — the icon vocabulary is half-built

The theme names **29 meanings**, each bound to a Lucide glyph and a lane. The sprite has **19
symbols**, covering 13 of them. **16 meanings have no glyph:**

`owner` · `keep` · `customer` · `quotation` · `gear` · `shield` · `territory` · `creative` ·
`report` · `review` · `growth` · `handshake` · `rupee` · `clock` · `leak` · `unknown`

The last three matter most: `clock`, `leak` and `unknown` are the **problem lane** — the slate
circles that carry the entire Video 1 hook, which is the home page's job.

### Gap 3 — the lane system is not encoded

The theme's central rule is *"Blue is us, green is you"*, with teal for shared work and slate for
a problem. `theme.js` encodes it as `CIRCLE` and `ICON_LANE`, so a glyph's colour is derived from
its meaning and can never be chosen by hand.

`tokens.css` carries **13 of the theme's 23 colours**. Missing: `GREEN_D`, `NAVY`, `BLUE_DEEP`,
`MUTED`, `MUTED_D`, `BORDER_2`, `BORDER_G`, `MIST_2`, `TINT_G`, `WARN_TXT`. More importantly
there is **no lane abstraction at all** — nothing in the CSS says "this icon is BrandSure's
lane", so every component picks a colour itself and the meaning is carried by convention.

**That is why the site reads as static even where it moves.** The lanes are the story; if the
colour does not change, nothing has been said.

---

## 2. Interaction ≠ animation volume

The brief asks for "very interactive". The same authorities cap how much can move:

| Source | Constraint |
|---|---|
| Theme §6 | *"Nothing exits. No Bounce, Spin, Swivel, or sound. Emphasis reserved for M4."* |
| v4 §8 | *"Use subtle motion; respect reduced-motion settings."* |
| Earlier brief | No scroll hijacking, custom cursors, heavy parallax, constant bouncing, loading screens, no large animation library, no hover-only behaviour |
| v4 §10 | Non-video payload under 600 KB, LCP under 1.5 s on 4G |

These are not in tension once the distinction is made:

- **Animation** is what the page does *at* the reader. More of it does not make a site feel
  alive; past a low threshold it makes it feel slow and cheap.
- **Interaction** is what the reader *operates* — a control that changes what they see and
  teaches them something they came to find out.

**The site's real deficit is interaction, not animation.** Today a reader can tap the nav, tap a
CTA, open an FAQ, open the WhatsApp sheet, and type four numbers. Everything else is read-only.
That is what "crisp and non-interactive" is describing.

### The discipline: every interaction answers a question the reader already has

The audience is a furniture manufacturer or showroom owner, 35–60, one-handed on an Android
phone, possibly inside WhatsApp's browser, possibly on a lit showroom floor. They are not going
to play with a website. They arrive with five questions:

| | Question |
|---|---|
| **Q1** | What exactly do you do, and what do I still do? |
| **Q2** | Which of the three is for me? |
| **Q3** | What does it cost me? |
| **Q4** | Does this work for a business like mine? |
| **Q5** | What actually happens after I message? |

**An interaction that does not answer one of those does not get built.** That single rule is what
keeps "interactive" from turning into "fidgety", and it is the filter I will apply page by page.

---

## 3. The motion grammar, translated

Theme §6, move for move. Durations are the theme's own.

| Move | Theme effect | Web equivalent | Applies to |
|---|---|---|---|
| **M1 · SETTLE** | Float In from bottom, 0.4 s | `opacity` + `translateY(14px→0)`, 400 ms | Headlines, subs, labels, rows, footer, the dark payoff card |
| **M2 · DRAW** | Wipe from left, 0.5 s | `clip-path: inset()` or `scaleX` from `transform-origin: left`, 500 ms | Tick bar, split bars, rulers, chevrons, meter fills, the aqua spine |
| **M3 · POP** | Zoom from centre, 0.3 s | `scale(0.9→1)` + opacity, 300 ms | Icon circles — **only** circles |
| **M4 · VERIFY** | Zoom 0.25 s → Pulse ×1 | `scale` then one `scale(1→1.06→1)`, 500 ms total | Green ticks, pills, badges, RECOMMENDED tag — **always last** |
| **M5 · FLOW** | M3 per node, 0.15 s stagger | `transition-delay` ladder | Chains, lane rows, funnels |
| **M6 · GROW** | Wipe from bottom 0.6 s → number M1 | `scaleY` from bottom, then the number M1s in, 900 ms | Stat rows, stacks, bars |

**The order is fixed and is not mine to change** — theme §6: *tick bar (M2) → headline (M1) →
sub (M1) → picture → dark card → green accents (M4).* The green beat lands last, every time. That
single rule is most of what makes the decks feel composed rather than busy, and it will do the
same here.

**Page transitions.** The deck uses Fade 0.5 s between slides and Push From Bottom 0.6 s into a
gate. The web equivalent is the View Transitions API: a cross-fade between pages, and a
bottom-push into `/start/`. Chrome-only, no JavaScript, and it no-ops silently everywhere else —
the single largest gain in perceived interactivity per line of code on this list.

### What we already have

Chat 15 built roughly the M1 and M5 layer: hero entrance in reading order, one-shot section
reveals, the chain lighting left to right, press states, FAQ fades, a sticky-header state. That
is the floor. **M2, M3, M4 and M6 do not exist yet**, and M4 — the green verification beat — is
the one the theme treats as the signature.

---

## 4. The interaction inventory

Each one names the question it answers. Anything without one is not on the list.

### Tier 1 — build these

**A · The Lane Switch** — *Q1, Q2*
A three-way control (Agency / Digital Partner / Growth Partnership) above the eight-node chain.
Tapping one recolours the chain live: blue nodes are BrandSure's, green are the owner's, teal
shared. The reader sees the difference between the three plans as **one picture changing**,
instead of three cards to hold in their head.

This is the theme's C-Chain plus the lane code, made operable, and it is the most valuable
single thing on this list — it answers the two questions the whole site exists to answer, in one
control, in about two seconds. No JS: the three chains render stacked and labelled, exactly as
they do today.

**B · Tap a chain node** — *Q1, Q5*
Each of the eight steps expands one line: what happens here, and what it costs when nobody owns
it. Turns a decorative icon row into the site's core explainer. Uses `clock` / `leak` /
`unknown` in slate — the problem lane the sprite is currently missing.

**C · The clock spine** — *Q5*
The Digital Partner timeline, 9:04 a.m. → Saturday. Theme's Video 3 signature verbatim: *"circles
pop in clock order down the aqua spine; the green 'walks in ready' circle is last."* The spine
draws with M2 as the reader scrolls, nodes M3 as they are passed, the green arrival is the M4
beat. Scroll-driven CSS where supported, a one-shot reveal where not.

**D · Plan focus in the comparison** — *Q2, Q3*
Tap a plan name; its answer lifts in every row and the other two recede. On mobile — where the
table is now stacked cards — the chosen plan's line is emphasised inside each card. Cheap,
and it converts a reference table into something a reader can interrogate.

**E · The gate, upgraded** — *conversion*
The four-number form on `/start/` is the only interaction on the site that produces revenue, so
it gets the most care: per-field readiness ticks (M4, green), a four-step progress meter using
the theme's own `PROGRESS` token (aqua→teal on a mist track), and the live WhatsApp message
preview that already exists made visible rather than implicit.

**F · Cross-page View Transitions** — *feel*
Home → Plans → Digital Partner as one continuous surface rather than three page loads. Two lines
of CSS, no JS, silently absent on browsers without it.

### Tier 2 — likely, decided at page time

**G · Lane cards** (*Q1*) — theme's C-LaneCards: a blue "BrandSure handles" band and a green "You
handle" band, rows cascading with M5, each tick glowing M4. Mostly a motion piece; the theme's
Video 5 signature.

**H · Stat row** (*Q3*) — chip cards on a mist panel, M6 on entry. **No count-up on any money
figure** — a number animating through ₹12,000 on its way to ₹45,000 has displayed a price that is
not real, which here is a commercial problem, not a visual one.

**I · The RECOMMENDED pill** (*Q2*) — theme's Video 2 signature: the gauge draws, three owner
circles pop left to right, and MOST START HERE lands as the M4 beat. The pill exists; the beat
does not.

**J · Reading progress** — a 2px aqua→teal line under the sticky header, using `PROGRESS`. On a
long page read one-handed this is genuinely useful, not decoration. One `animation-timeline:
scroll()` declaration, zero JS.

**K · Smooth accordion height** — `::details-content` with `interpolate-size: allow-keywords`
gives a true height transition with no measurement and no layout thrash. Falls back to the fade
built in Chat 15.

### Ruled out, and why

| Not doing | Why |
|---|---|
| Count-up on any figure | Displays prices that are not real |
| Parallax, scroll hijacking, custom cursors, loading screens | Named in the brief; all three fight a one-handed reader on mobile data |
| An animation library (GSAP, AOS, Framer) | Brief forbids it for effects simple code achieves; the payload budget is 600 KB total |
| Hover-only reveals | 99% of visitors have no hover |
| Dark sections | Theme rule 1 — one navy payoff card per page, never a dark page |
| Red or coral for a problem | Theme §1 rule 3 — slate circle, yellow pill. There is no red in this system |
| Colouring a word in a Pattern A headline | Theme §2 — the tick bar *is* the accent. Never both |
| Anything exiting | Theme §6 — *"Nothing exits."* Elements arrive and stay |
| C-SplitBar publicly | It is the ₹33,000 money breakdown — Video 4, GP-private. Build it for the private funnel, never for brandsure.in |

---

## 5. Per-page tempo — taken from the theme, not invented

Theme §6 gives each video a tempo and a signature. The videos map onto pages, which means **each
page gets a different rhythm from the same grammar** — the thing that will stop the site feeling
uniformly animated.

| Page | Video | Tempo | Signature, verbatim from the theme |
|---|---|---|---|
| **Home** | 1 · Hook | fastest, 0.12 s stagger | *"Problem slides: slate circles pop, then each yellow SALE LOST pill glows (M4) as narration names the loss."* |
| **Plans** | 2 · Choose | medium | *"The gauge draws, three owner circles pop left→right, MOST START HERE pill is the M4 beat."* |
| **Digital Partner** | 3 · DP | clock-like | *"The 9:04 → Sat timeline: circles pop in clock order down the aqua spine; the green 'walks in ready' circle is last."* |
| **How It Works** | 5 · How we work | orderly | *"Lane cards: blue header band wipes, its rows cascade, then the green card does the same. Each tick glows on its narrated line."* |
| **Growth Partnership** | — | slowest | Video 4's tempo without Video 4's content. Restraint reads as seriousness, which is the right register for a page whose answer is "after a review" |
| **Proof, About, Services, blog** | — | Home's grammar, quieter | No new moves. A page that introduces a move nobody else uses is a page that looks like a different website |

---

## 6. Build order

One page per chat, as asked. **Chat 17 is the foundation and is not a page** — without it every
page invents its own lane colours and its own easing, and by page four there are four systems.

| Chat | Scope | Why here |
|---|---|---|
| **17** | **Foundation — part done.** ✅ All 27 theme colours, the `--bs-lane-*` abstraction, all 29 icon meanings as solid circles from real Lucide, M1–M6 durations — all now *generated* from `theme-source/theme.js` by `npm run build`, so they cannot drift. ✅ The render harness and five audits moved into the repo as `npm test`. **Still to do: M1–M6 as reusable CSS utilities, and View Transitions.** | Everything downstream depends on it, and it is mostly mechanical |
| **18** | **Home** — Video 1 tempo. Lane Switch (A), chain nodes (B), the slate/yellow problem beat | Highest traffic; sets the register for the rest |
| **19** | **Plans** — Video 2 tempo. Plan focus (D), gauge draw, the RECOMMENDED beat (I) | The decision page |
| **20** | **Digital Partner** — Video 3 tempo. The clock spine (C), stat row (H) | The main recommended offer |
| **21** | **How It Works** — Video 5 tempo. Lane cards (G) | Where Q1 is answered at length |
| **22** | **Start** — the gate (E) | Last, deliberately: by then the four-number handoff is the only thing left that can be improved without touching anything upstream, so it can be changed with confidence |
| **23** | **Proof + Growth Partnership** | Quieter pages, shared grammar |
| **24** | **Services hub + four service pages** | Same treatment applied four times |
| **25** | **About, blog, legal, final QA** | Light touch; legal pages stay plain |

### The shape of each page chat

Same six steps every time, so the work is predictable and reviewable:

1. **Read the page as it is** — what it says, in what order, and which of Q1–Q5 it answers
2. **Decide the interactions** — from §4, filtered by that question test; anything new justified against it
3. **Build** — CSS first, JS only where a control genuinely needs state
4. **Prove it degrades** — JS off, reduced motion on, 360/390/430 + desktop, 48px targets, no CLS
5. **Screenshot** — before and after at 390px
6. **Commit** — one page, one commit, with the guard passing

---

## 7. Guardrails, stated up front so they can be checked

Every one of these is measurable, and each becomes a QA gate rather than an intention.

| Guardrail | Gate |
|---|---|
| Content never depends on motion | Every page fully readable with JS disabled — the check that already exists |
| Reduced motion is **off**, not faster | No element mid-transition with `prefers-reduced-motion: reduce` |
| No layout shift from any animation | Opacity and transform only; CLS 0 |
| No new blocking weight | JS stays a single file; total non-video payload under 600 KB (v4 §10) |
| Touch parity | Nothing reachable only by hover; every target ≥48px |
| Contrast survives the lane colours | WCAG AA on every new coloured surface — green on white and teal on white both need checking, and `GREEN #22BE6C` on white is around 2.4:1, so it may carry **fills and glyphs but not small text**. `GREEN_D #1BA85C` exists in `theme.js` for exactly that reason |
| Disclosure unaffected | The existing audit runs on every page; an animated or interactive surface is still a surface, and a lane switch must never expose a GP commercial |
| One dark block per page | Theme rule 1 |

### The browser question

All of the modern CSS this plan leans on works in the Chromium here (141): scroll-driven
animations, `@starting-style`, `interpolate-size`, `::details-content`, View Transitions,
`:has()`, container queries, `color-mix()`.

The audience is on mid-range Android, so Chrome is the realistic target and it auto-updates —
but some of these are recent, and I have not measured what the real visitors run. **Every one is
used as progressive enhancement: absent support means no animation, never a broken page.** That
is the only reason it is safe to plan around them. Once analytics exist, the assumption is worth
replacing with a number.

---

## 8. What this plan does not cover

- **Anything private.** C-SplitBar, the ₹33,000 breakdown, GP economics and the token-gated
  videos are Video 4/5 material and stay off brandsure.in.
- **The video embeds themselves.** No approved exports or hosting IDs exist yet
  (`OPEN-ITEMS.md` item 7); the poster-and-tap-to-load pattern already built is the right shape
  and needs assets, not code.
- **The four open decisions** that gate real content: the `hello@brandsure.in` mailbox and single
  WhatsApp number (item 13), the share image (item 12), the GP copy sign-off (item 6), and the
  six n8n changes (item 2). None of them blocks this work, and none of them is fixed by it.

---

*Nothing in this file is built. It is the plan for chats 17–25.*
