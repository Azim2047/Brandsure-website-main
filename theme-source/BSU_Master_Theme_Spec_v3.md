# BrandSure Video Decks — Master Theme Spec v3 (LIGHT)
*4 Sep 2026 · Verified against `BrandSure_Digital_Partner.pptx` (27 slides) and `BSU_Theme_File.docx`. **Supersedes v1 and v2.** Replaces §1.4–1.6 of the Master Build Architecture. Numbers, slide order, gates and C1–C15 unchanged.*

---

## 0. Where the last two versions went wrong

The Theme File and the Digital Partner deck **agree with each other**. Both are light. I should have treated the deck as the reference from the start instead of reading the hex list in isolation (v1) and then over-correcting to the social posters (v2). The posters are a separate, darker channel for Instagram; they don't govern the decks.

| | v1 | v2 | **v3 (correct)** |
|---|---|---|---|
| Surface | light + 4 dark slides | all dark | **White. One dark navy CARD per slide — never a dark slide** |
| Icon form | white glyph on a rounded-square tile | gradient line glyph, no tile | **White glyph in a SOLID CIRCLE** |
| "You" colour | pastel `#A6DA8E` | mint outline | **Vivid green `#22BE6C`, solid fill** |
| Dark tone | slate `#17232B` | navy `#02133E` | **Ink `#081E2E`** (cards) · **`#0E3247`** (hero panels) |
| Headline | plain, coloured phrase | plain, mint phrase | **Blue tick bar + ink bold** — the deck's signature |
| Negative | coral | coral | **No coral. Slate circle + yellow SALE LOST pill** |
| Type | Inter ✓ | Montserrat ✗ | **Inter** — 2,856 uses in the deck; v1 was right |
| Motif | "B" letter pattern | glass panel + sparkle | **Soft aqua/warm glow, bottom-right. Nothing else** |

Everything below is verified against the deck's XML — the frequency counts are how many times each value actually appears.

---

## 1. Colour

### Core
| Token | Hex | Count | Use |
|---|---|---|---|
| `INK` | `#081E2E` | 353× | Headlines · dark callout cards · the customer's step in a chain |
| `BLUE` | `#116E98` | 246× | **BrandSure's lane.** Icon circles, tick bar, numbers, the answer row |
| `BORDER` | `#E8F3F2` | 227× | Every card edge |
| `WHITE` | `#FFFFFF` | 225× | The slide · cards · all glyphs |
| `BODY` | `#4B6673` | 149× | Paragraphs · **problem-state icon circles** |
| `SUB` | `#7F96A0` | 88× | Captions, footer path, timestamps, inactive |
| `AQUA` | `#7DD1C9` | 82× | Connectors and chevrons · the glow · progress fill |
| `MUTED` | `#A9C4D2` | 77× | **Body text on dark cards** · inactive states |
| `GREEN` | `#22BE6C` | 57× | **Your lane.** A sale · ticks · success · included |
| `BORDER_2` | `#CFE4EA` | 55× | Stronger card edge, chips |
| `MIST` | `#F0F8F7` | 48× | Tinted panel fill, chip fill |
| `BLUE_DEEP` | `#12384E` | 18× | Hero panel gradient, deep fills |
| `NAVY` | `#0E3247` | 17× | Hero panel gradient start |
| `TEAL` | `#30A4A0` | 16× | **Shared lane** · your-team steps |
| `WARN` | `#FCC851` | 14× | SALE LOST pill, caution tag |
| `MIST_2` | `#DCEBF0` | 14× | Deductions that belong to nobody (GST, material, transport) |
| `WARN_BG` | `#FBF3DF` | 9× | Disclaimer strip (border `#FFEDC2`, text `#9A7B2E`) |
| `MUTED_D` | `#8FA9B6` | 22× | Secondary on dark, struck-through / "before" states |

### Rules
1. **The slide is white.** The dark navy card is a *component* — the payoff panel, one per slide at most. There are no dark slides.
2. **Blue is us, green is you.** Blue = BrandSure's work and BrandSure's money. Green = the owner's work, a closed sale, money he keeps, anything included. Teal = shared / his team inside our journey.
3. A problem is a **slate `#4B6673` circle**, and its consequence is a **yellow pill**. No red, no coral, anywhere.
4. Money that belongs to nobody (GST, material, transport) is `#DCEBF0` — quiet, never coloured.
5. Glow lives bottom-right only, aqua and warm-yellow blended, very low opacity. Never behind text.

### Gradients
- **Hero panel** — `linear-gradient(135deg, #0E3247, #1E5C74)` for the one full-bleed panel slide per video (the engagement / commercial slide).
- **Progress / meter** — `#7DD1C9 → #30A4A0` on a `#DCEBF0` track.
- **Corner glow** — radial `#7DD1C9` 38% and `#FCC851` 22%, overlapping, bottom-right, ~2× slide height.

---

## 2. Typography — Inter

| Role | Weight | Size | Colour |
|---|---|---|---|
| Headline (Pattern A) | Bold 700 | **27 pt**, ≤ 2 lines | Ink |
| Headline (Pattern B) | Bold 700 | 30–34 pt | Ink + one Blue phrase |
| Eyebrow | Bold 700 caps, +13% tracking | 12 pt | Blue |
| Sub-headline | Regular 400 | **13.5 pt** | Body |
| Big number | Bold 700 | 24–34 pt | Blue (or White on a filled row) |
| Card title | Bold 700 | 15 pt | Ink (White on a coloured band) |
| Label / list row | Regular 400 | **12.5 pt** | Body |
| Tag / pill | Bold 700 caps | 12 pt | per pill |
| Takeaway | Regular 400 **italic** | 14 pt | Body |
| Footer | Regular 400 | **9 pt** | "BrandSure" Blue Bold + path in Subtle |
| Body on dark card | Regular 400 | 12.5 pt | `#A9C4D2` |

Fallback: `Inter, Calibri, Arial`. Install Inter on the recording machine.

### The two headline patterns
**Pattern A — tick bar (most slides).** A 7 × 33 px blue bar sits left of the headline; the sub-headline indents to align with the headline text, not the bar. **Never colour a word in Pattern A** — the bar is the accent.

**Pattern B — eyebrow + coloured phrase (section openers, money slides).** Blue caps eyebrow above, larger headline with exactly one blue phrase, no tick bar.

Pick one per slide. Never both.

---

## 3. Icons — white glyph, solid circle

**The only icon form in the system.** A Lucide glyph at 2 px stroke, filling 50% of a solid circle, always centred. Circle 0.55–0.9 in in rows; 1.1 in for a hero. **The circle fill is the lane code** — no borders, no tiles, no rounded squares.

| State | Circle | Glyph |
|---|---|---|
| **BrandSure does** | `#116E98` | White |
| **You do / a sale** | `#22BE6C` | White |
| **Shared · your team** | `#30A4A0` | White |
| **A problem / leak** | `#4B6673` | White |
| **Tick on a dark card** | `#22BE6C` at 16% | `#22BE6C` |
| **Quiet / on a tinted panel** | `#F0F8F7` | `#116E98` |

### Vocabulary
Meanings unchanged from v1; only the lane assignment shifts, because green now means "yours".

| Meaning | Lucide | Lane |
|---|---|---|
| You / the owner | `user-round` | You |
| Your showroom | `store` | You |
| Your factory | `factory` | You |
| Delivery | `truck` | You |
| Money — you keep | `coins` | You |
| Included / done | `check` | You |
| Your team | `hard-hat` | Shared |
| Customers / enquiries | `users` · `user` | Shared |
| An order (sofa) | `sofa` | Shared |
| Quotation | `receipt` | Shared |
| Advertising | `megaphone` | BrandSure |
| Call | `phone` | BrandSure |
| WhatsApp | `message-circle` | BrandSure |
| Appointment | `calendar-days` | BrandSure |
| Written down / CRM | `clipboard-list` | BrandSure |
| System / process | `settings` | BrandSure |
| Protection (SWO) | `shield-check` | BrandSure |
| Territory | `map-pin` | BrandSure |
| Website | `globe` | BrandSure |
| Creatives | `pen-tool` | BrandSure |
| Weekly report | `file-text` | BrandSure |
| Review / audit | `search` | BrandSure |
| Growth / stage | `trending-up` | BrandSure |
| Partnership | `handshake` | BrandSure |
| Money — ours | `indian-rupee` | BrandSure |
| Late / waiting | `clock` | Problem |
| Leak / loss | `droplets` | Problem |
| Nobody knows | `circle-help` | Problem |

---

## 4. The slide

```
 ┌ WHITE, 0.6in margins ───────────────────────────────────────┐
 │ ▌ HEADLINE  Inter Bold 27pt Ink        (Pattern A)          │
 │   sub-headline 13.5pt Body                                  │
 │                                                             │
 │   THE PICTURE (left ~60%)          ┌ DARK NAVY CARD ─────┐  │
 │   bars · chains · circles · chips  │ green circle icon   │  │
 │                                    │ white bold payoff   │  │
 │   takeaway, italic Body            │ ✓ muted tick lines  │  │
 │                                    └─────────────────────┘  │
 │  ┌ yellow disclaimer strip, full width, centred italic ──┐  │
 │  └───────────────────────────────────────────────────────┘  │
 │  ────────────────────────────────────────────────────────    │
 │  BrandSure · Video n · Section                          n    │
 └──────────────────────── glow, bottom-right ─────────────────┘
```

- **Cards** — white, radius 12 px, 1 px `#E8F3F2`, shadow `0 5px 9px rgba(17,110,152,0.07)`.
- **Dark payoff card** — `#081E2E`, radius 12 px, no border. One green circle icon at the top, white bold statement, then muted tick lines. This is where the emotional beat goes on every slide that has one.
- **Disclaimer strip** — `#FBF3DF` fill, `#FFEDC2` border, `#9A7B2E` centred italic, full content width, directly above the footer rule. **Every caveat lives here** — "check GST with your CA", "one client result", "illustration only". No inline disclaimers.
- **Footer** — hairline `#E8F3F2` rule, then "BrandSure" in Blue Bold followed by the path in Subtle; page number right.
- **Hero panel slide** — once per video, the navy gradient panel bleeds to the slide edges with white text and pill tags. This is the only time the whole slide reads dark.

---

## 5. Components — the four patterns the deck actually uses

**C-Chain** — filled rounded step blocks (radius 9 px), not tiles. Numbered white circle top-left, label bottom-left in white bold, aqua chevron between blocks. Block colour = whose step it is: Blue ours · Teal your team · Ink the customer · Green the sale. Legend of colour swatches below.

**C-LaneCards** — a coloured header band (blue for us, green for you) with a white/tinted pill on the right, over a white body of icon-circle rows. The green card's border is `#BEE9CF`; the blue card's is `#CFE4EA`.

**C-StatRow** — 3–5 white chip cards (radius 8 px, `#CFE4EA` border) on a `#F0F8F7` tinted panel. Blue number, subtle label, no icons. For the commercial slides owners screenshot.

**C-SplitBar** — one bar carved into segments. Deductions `#DCEBF0` with body text; BrandSure's share solid Blue with white text; the YOU KEEP segment solid Green with white text, plus a green pill badge below reading "YOU KEEP ₹X".

Everything else in C1–C15 is a variation on these four.

---

## 6. Motion — v1 grammar restored

Solid circle icons pop; they don't trace. v2's M3 change is reverted.

| Move | Effect | Duration | Applied to |
|---|---|---|---|
| **M1 · SETTLE** | Float In, from bottom | 0.4 s | Headlines, subs, labels, list rows, footer |
| **M2 · DRAW** | Wipe, From Left | 0.5 s | Split bars, rulers, chevrons, meter fills, the tick bar |
| **M3 · POP** | Zoom, Object Center | 0.3 s | Icon circles |
| **M4 · VERIFY** | Zoom 0.25 s → Pulse ×1 | 0.5 s | Green ticks, pills, YOU KEEP badge, RECOMMENDED tag — **always last** |
| **M5 · FLOW** | M3 per node, 0.15 s stagger | — | Chains, lane rows, funnels, quarter rulers |
| **M6 · GROW** | Wipe From Bottom 0.6 s → number M1 | 0.9 s | Stacks, route bars, money |

- Transition: **Fade (Smoothly) 0.5 s**; gate slides **Push From Bottom 0.6 s**.
- Order: tick bar (M2) → headline (M1) → sub (M1) → picture → dark card → green accents (M4).
- The dark payoff card enters as one group, Float In, after the left-hand picture is complete.
- Nothing exits. No Bounce, Spin, Swivel, or sound. Emphasis reserved for M4.

### Per-video signature
| Video | Tempo | Signature |
|---|---|---|
| **1 · Hook** | fastest, 0.12 s stagger | Problem slides: slate circles pop, then each yellow SALE LOST pill glows (M4) as narration names the loss. |
| **2 · Choose** | medium | The gauge draws, three owner circles pop left→right, MOST START HERE pill is the M4 beat. |
| **3 · Digital Partner** | clock-like | The 9:04 → Sat timeline: circles pop in clock order down the aqua spine; the green "walks in ready" circle is last. |
| **4 · Money** | slowest | SplitBar segments wipe with 0.2 s gaps — deductions, then blue, then green YOU KEEP with the badge. Dark card lands last. |
| **5 · How we work** | orderly | Lane cards: blue header band wipes, its rows cascade, then the green card does the same. Each tick glows on its narrated line. |

### Getting it into the file
`pptxgenjs` writes no animation. Every shape is named for its move (`M1.headline`, `M2.tickbar`, `M3.circle`, `M4.pill`), so either apply once in PowerPoint from a recipe using the Selection Pane, or inject `<p:timing>` XML post-build from those names. Option B is worth it now that all five decks share these four components.

---

## 7. Sign-off

- [ ] White surface, dark navy card as a component — never a dark slide
- [ ] Blue = us, green = you, teal = shared, slate = a problem
- [ ] Solid circle icons, white glyphs
- [ ] Tick-bar headline as the default pattern
- [ ] All caveats in the yellow strip; no coral or red in the system
- [ ] Inter
- [ ] Motion Option A or B

Video 1 gets rebuilt on this first. The v3 deck I sent earlier is on the wrong theme and needs redoing.

---

*Set: `BSU_Master_Theme_Spec_v3.md` (this) · `BSU_Master_Theme_Board_v3.png` (visual) · `theme.js` (tokens).*
