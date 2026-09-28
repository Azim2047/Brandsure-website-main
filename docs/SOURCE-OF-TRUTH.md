# BrandSure Website v2 — Source of Truth

*Established Chat 0. This file governs the `brandsure-v2` rebuild. Nothing in the new build may
contradict it. If a supplied document contradicts this file, this file wins, because this file
already records the reconciliation.*

Scope: the public website at `brandsure.in`, built as the custom WordPress theme
`wp-content/themes/brandsure-v2/`. The old site (`/wp/`, the root `*.html` files, the reference
zips) is **reference only** and is not modified by this project.

---

## 1. Authority order

Applied top-down. A lower source never overrides a higher one.

| # | Source | Decides |
|---|---|---|
| **1** | **The four Overrides** (§2 below) | Public commercial disclosure. Beats everything, including Architecture v3. |
| **2** | `BrandSure_Website_Architecture_v3.md` | Sitemap, public/private split, page jobs, navigation, conversion journey, WordPress architecture |
| **3** | `BrandSure_Master_Numbers_and_Language_Sheet.md` | Prices, package names, capacities, every number, approved and banned words |
| **4** | Layer 4 video system (`BrandSure_Master_Build_Architecture.md`, PPT1–PPT5) + `BrandSure_Layer_Learnings_and_Visual_Conversion_Plan.md` | Sales psychology, what is revealed at which stage, Video 1–5 narrative |
| **5** | `BrandSure Customer Persona.docx` | How simply the site must communicate |
| **6** | `BSU Theme File.docx` | Visual design — colours, type, radii, components |
| **7** | Old site (`99_OLD_SITE_REFERENCE_ONLY`, `/wp/`, root `*.html`) | **Reference only.** SEO copy, URLs, metadata, founder copy, service-page information, proven UI ideas, existing assets |

**Never a source of truth:** the colours, fonts or commercial framing inside the PPT decks.
The decks use the presentation identity (navy `#0E2439` / gold `#C79A4B` / cream / Bookman Old
Style). **The website uses the BSU Theme File and nothing else.** The decks are a source for
*argument and sequence*, not for *look*.

**Never carried over from the old site:** pricing, package names, commercial terms, navigation,
the Growth Partnership explanation, homepage positioning.

**Never invent a commercial number.** Every rupee figure on the site must trace to Section A of
the Master Numbers Sheet, and must be one of the facts marked PUBLIC in §5 below.

---

## 2. The four Overrides (highest authority)

### Override 1 — Public commercial disclosure
The public site must **never** disclose, in any form: `20%`, `80%`, `commission`,
`revenue share`, `share of sales`, or any exact partnership percentage.

Not through visible copy, schema/JSON-LD, `<meta>`, HTML comments, JavaScript, any JSON served
publicly, `alt` text, or FAQ content.

Growth Partnership itself must be **very visible**. We hide the mechanism, not the plan.

Approved public wording (verbatim, the only commercial content the GP page carries):

> **We invest in the growth side with you.**
> BrandSure funds the agreed Google and Meta advertising under the Growth Partnership.
> *The complete commercial structure is explained with your business numbers after a business review.*

### Override 2 — Partnership term disclosure
The public site must **never** show: `36 months` (or any GP duration), the `₹1,20,000` setup,
the `₹3,00,000` deposit, the weekly payment rhythm, `SWO` / Standard Walk-in Offset, the
`₹33,000` sofa calculation, the `₹55 lakh` P&L, route-cost comparison bars, or any Video 4
economics.

Public Growth Partnership stages are named only, with **no duration attached**:

`Foundation → Stability → Profit per Order → Scale → Stable at Scale`

### Override 3 — Growth Partnership qualification
* Agency and Digital Partner CTAs **may** pass `plan=Agency` / `plan=Digital Partner`.
* Growth Partnership **must never** pass `plan=Growth Partnership`. It passes
  **`interest=Growth Partnership`**.
* Public GP CTA label: **"Check If My Business Qualifies"**.
* The AI decides the actual recommended route. The website never prescribes one.

### Override 4 — Content ownership
There must never be two editable copies of the same content. A page is *either* developer-owned
(JSON + template) *or* backend-editable (WordPress editor). Never both. See §8.

---

## 3. Conflicts found in the sources, and how they are resolved

These are real contradictions between supplied files. Each is settled here so it is not
re-litigated during the build.

| # | Conflict | Resolution |
|---|---|---|
| 1 | Architecture v3 §5 specifies the `/plans` compare table GP cell as *"Revenue-share partnership · explained after review"*. Override 1 bans the phrase "revenue share". | **Override wins.** The cell reads **"Commercials explained after a business review."** The word "revenue share" appears nowhere public. |
| 2 | Architecture v3 §5.8 specifies the GP CTA prefill as `plan=Growth Partnership`. Override 3 forbids it. | **Override wins.** `interest=Growth Partnership`. |
| 3 | Architecture v3 §5 `/growth-partnership` item 4 says "The 36 months in five stages". Override 2 forbids the duration. | **Override wins.** Five named stages, no duration, no sales-direction figures. |
| 4 | Master Numbers Sheet A3 calls the third plan **"Virtual Growth Partnership"**. Architecture v3 and the Overrides call it **"Growth Partnership"**. | **Public name is "Growth Partnership."** "Virtual Growth Partnership" is an internal/deck alias and never appears on the site. |
| 5 | Master Numbers Sheet §B7 lists navy/gold/cream/Bookman as the design standard. BSU Theme File lists blue/aqua/Inter. | B7 is the **deck** standard. The **website** uses the BSU Theme File (§9). No navy/gold anywhere in the theme. |
| 6 | The sales-flow package (`part1_schema.sql`, copy pack, AI prompt) still names the plans **Standard / Expert**. | The website uses **Agency / Digital Partner / Growth Partnership**. The rename inside the AI stack is a funnel-side task, tracked in `OPEN-ITEMS.md` — the website does not depend on it, but the price-parity guard does. |
| 7 | Architecture v3 §8 says website events post to the existing n8n `/landing-event` webhook. That workflow's whitelist rejects every website event name and requires an existing `lead_id`. | Event **names and payload shape** are fixed here (see `EVENTS-AND-INTEGRATION.md`); the workflow change needed to accept them is an open item. No new webhook URL is invented. |

---

## 4. The approved three plan names

Exactly these, spelled exactly this way, everywhere on the public site:

1. **Agency**
2. **Digital Partner**
3. **Growth Partnership**

Never on the public site: "Standard", "Expert", "Virtual Growth Partnership", "VGP", "Package",
"Tier".

---

## 5. Commercial facts — public vs private

### 5.1 PUBLIC (may appear on `brandsure.in`)

| Fact | Value | Where |
|---|---|---|
| Agency monthly fee | ₹45,000 + GST | `/plans` |
| Digital Partner monthly fee | ₹75,000 + GST | `/plans`, `/digital-partner` |
| Setup fee (Agency and Digital Partner only) | ₹25,000 + GST, one-time | `/plans`, `/digital-partner` |
| Digital Partner month-1 cash | ₹1,00,000 + GST | `/digital-partner` |
| Suggested Agency ad budget | ₹50,000 – ₹2,00,000 / month, paid by the client directly | `/plans` |
| Digital Partner ad budget start | ₹1,00,000 / month, paid by the client directly | `/digital-partner` |
| Minimum term, Agency & Digital Partner | 3 months | `/plans`, `/digital-partner` |
| Digital Partner capacity | 1 city · up to 300 new enquiries / month | `/plans`, `/digital-partner` |
| Full system live | up to 45 days | `/digital-partner` |
| Break-even illustration | profit per order ₹15,000 → 5 orders covers the ₹75,000 fee | `/digital-partner` |
| Manufacturing credentials | 50,000 sq.ft own facility · 3 factories operated · ₹87 Cr+ annual sales handled | `/proof`, `/about` |
| Client result | ~₹45L/month → ₹87L+/month in about 5 months — **always** with the footnote *"One client result. Outcomes depend on product, city, budget and execution."* | `/proof` |
| Growth Partnership commercial content | The three approved sentences in Override 1. Nothing else. | `/growth-partnership` |
| Growth Partnership stages | Foundation → Stability → Profit per Order → Scale → Stable at Scale (no durations) | `/growth-partnership` |

Digital Partner add-on prices (website ₹90,000, catalogue levels, SEO, social, etc.) are
**not** published in this build. They are quoted, not listed. They stay out of the theme's data
files entirely.

### 5.2 PRIVATE (never on `brandsure.in`, in any channel, ever)

`20%` · `80%` · "revenue share" · "share of sales" · "commission" · "you keep" · any partnership
percentage · `36 months` or any GP duration · `₹1,20,000` setup · `₹3,00,000` deposit · the
weekly Monday/Tuesday/Wednesday rhythm · `SWO` / Standard Walk-in Offset · the `₹33,000` sofa
split (`₹9,376`, `28.4%`) · the `₹55L` month P&L (`₹13.26L`) · the 17% working-capital reserve ·
`₹19.4L` / `₹51.3L` investment figures · early-exit buy-out at cost + 40% · marketplace 1% ·
route-cost comparisons and the named benchmark brands (Wakefit, Pepperfry, The Sleep Company) ·
GP sales-direction figures (₹10L → ₹3Cr) · Video 4 and Video 5 content of any kind.

These live behind qualification: the WhatsApp AI, `go.brandsure.in/a/[token]`, Videos 4–5,
and Doc C/D.

### 5.3 Video disclosure level

| Video | Public? |
|---|---|
| Video 1 — Hook | **Public.** Home, blog post ends, `/start`. |
| Video 2 — Choose Your Plan | Gated. Never embedded publicly. `/plans` links to `/start` instead. |
| Video 3 — Digital Partner | **Public, permitted** on `/digital-partner`. Contains no partnership economics. |
| Video 4 — GP Money | Gated. Never referenced by title publicly. |
| Video 5 — GP How We Work | Gated. Never referenced by title publicly. |

### 5.4 Two guards

`inc/guard.php` protects **developer-owned** content: it reads files, runs in
CI with no WordPress and no database, and fails the build. Rules 1–14 below.

`inc/content-guard.php` protects **WordPress editor** content, which the build
guard cannot see at all because it lives in the database and is written long
after any build. From the moment an editable page exists, that is the largest
hole in Override 1: a staff member pasting a sentence out of a proposal into
a service page could publish the partnership percentage, and nothing in the
project would notice. Two layers close it:

* **Save time**, the real defence — a post containing a private fact is held
  as a draft rather than published, and the editor is told which phrase
  stopped it and why. This is the only moment a human is present and able to
  rewrite the sentence.
* **Render time**, the last resort — for content that reached the database by
  import, migration or direct edit, the matched phrase is elided from the
  output and the incident logged. A page with one elided phrase is
  embarrassing; publishing the mechanism is not recoverable.

Both use `BrandSure_Guard::blocklist()`. **One definition, shared** — two
blocklists would drift, and the day they drifted is the day one of them
stopped catching something.

### The build guard's rules

`inc/guard.php` fails the build (and blocks deploy) if any of the following is true. This is the
mechanical enforcement of §5.1/§5.2 — it is written in Chat 1 and run in every later chat.

1. **Global blocklist** — any of these strings appears in anything rendered on a public
   template, including JSON-LD, meta tags, comments, JS, and `alt` text:
   `20%`, `80%`, `revenue share`, `revenue-share`, `share of sales`, `commission`,
   `SWO`, `walk-in offset`, `weekly payment`, `36 months`, `1,20,000`, `3,00,000`,
   `9,376`, `13.26`, `33,000`, `Wakefit`, `Pepperfry`, `Sleep Company`, `19.4`, `51.3`.

   `36 months` is global, not only a Growth Partnership page rule: Override 2
   makes the term private everywhere and no public page needs it. The 3-month
   minimum on Agency and Digital Partner is a different figure and is
   unaffected.

   *"You keep"* is blocked in the two forms the private fact actually takes —
   the phrase followed by a figure (`you keep ₹9,376`), and the decks'
   uppercase badge (`YOU KEEP`) — rather than as a bare string. A bare match
   also fires on Video 2 S5's own approved headline, *"how much of the growth
   work do you want to keep doing yourself?"*, and a rule that flags approved
   copy teaches everyone to reach for the escape hatch, which is how a real
   leak eventually gets waved through.
2. **Growth-Partnership page blocklist** — additionally on `/growth-partnership/`:
   `36 month`, `36-month`, any month count attached to a stage, `deposit`, `setup fee`,
   `security deposit`, any `₹` figure at all.
3. **Route contract** — any public link or prefill containing `plan=Growth Partnership`.
**What the guard checks.** Every rule below runs against what can actually
   reach a visitor. Two kinds of line are skipped, for a reason rather than
   for convenience: a JSON key beginning with an underscore, which
   `inc/data.php` strips recursively at load so no component can render it;
   and a line that is *entirely* a PHP comment, which is never output. A line
   with code on it is still checked, so `$fee = '20%'; // note` is caught.
   Without this the guard flags the sentence *"this page must never show a
   deposit"* written beside the code that enforces it, which leaves only two
   options — delete the documentation, or reach for the escape hatch — and
   the second is how a real leak eventually gets waved through.

4. **Language sheet** — any banned word in developer-owned copy (`data/*.json`)
   **and in the migration seeds** (`migration/*.html`). The seeds matter as much
   as the JSON: they are the starting content for pages people read, and copy
   carried over from the old site is exactly where a banned word survives —
   that is where `retargeting`, `SKU` and two untranslated uses of `CRM` were
   caught. Banned words are: `funnel`, `attribution`,
   `retargeting`, `CRO`, `nurturing`, `conversion optimisation`, `customer journey`,
   `lifecycle`, `marketing automation`, `performance architecture`, `lower-funnel`, `SKU`,
   `product-market fit`, `ROAS`, `45 services` as a headline, untranslated `CRM`.
5. **Price parity** — every price in `data/commercial.json` matches the corresponding key in
   Supabase `commercial_facts` (one HTTP check). Blocked until conflict #6 (the
   Standard/Expert rename) is resolved; see `OPEN-ITEMS.md`.
6. **Schema honesty** — `FAQPage` schema is emitted only on a page that renders a visible FAQ
   block.
7. **Word budget** — no public page's visible **prose** exceeds 350 words.
   Labels, table cells, plan names, stage names and rupee figures are not
   prose and are not counted. That distinction comes from the sources
   themselves, not from convenience: the Master Build Architecture audits
   *"≤ 12 prose words on screen beyond diagram labels"* (§1.2, §1.9), and
   deliberately protects the compare table as a slide owners photograph,
   where *"a diagram would lose the numbers"* (§1.7 C15). The guard's
   heuristic is length — three words or fewer is a label, four or more is a
   sentence the reader has to read — and it reports both totals so a page
   drifting toward wordiness is still visible. `title` and `meta_description`
   are excluded too: both are written for a search result and neither is
   rendered in the body, so counting them would only push pages into writing
   worse metadata. Every other rule still checks them.
8. **Ownership consistency** — a WordPress-editable page has no `data/pages/*.json`
   (that would be Override 4's forbidden second copy), a controlled page with a
   template has one, and any `migration/*.html` seed still present is listed so
   an imported seed does not linger looking editable.
9. **Launch readiness** — the WhatsApp number, the public phone and email, and
   the n8n base URL. Warnings during the build, errors under `--launch`: they
   are known gaps with documented fallbacks today and fatal on the day the
   site goes live.
10. **Page metadata** — every controlled page has a `title` and a
    `meta_description`, and neither runs past what a search result shows
    (roughly 60 and 160 characters, warnings rather than errors). This exists
    because a page missing them does not fail visibly: it renders perfectly
    and quietly falls back to a bare title with no description. When the rule
    was first run it caught all seven descriptions written in Chats 2–9, every
    one of which would have been truncated mid-sentence; they were rewritten
    rather than the limit raised.
11. **Template scope** — no function is declared at file scope in `components/`
    or `templates/`. WordPress includes a template part with `require`, not
    `require_once`, so a component that declares a function fatals the second
    time it is used on one page — and a component that cannot be used twice is
    not a component. This has happened twice: `chain.php` in Chat 4, which took
    the plans page down because three cards each drew a chain, and
    `gp-bridge.php`, found by Chat 12's audit, which had never fired only
    because nothing calls it twice yet. The difference between the two is luck.
    Functions belong in `inc/`, which is loaded once with `require_once`.
12. **Seed contact parity** — every `tel:` and every `@brandsure.in` address in
    a `migration/*.html` seed matches `data/site.json`. Added in Chat 14, which
    found all four legal seeds still carrying the old site's phone number one
    call before they were imported into WordPress. It earned its keep again in
    Chat 15, when the contact email changed: the rule is what makes changing
    one contact and forgetting the other four pages impossible.
13. **Mandatory qualifiers** — a number that needs a caveat cannot be rendered
    without it. `digital_partner_capacity` requires `capacity_qualifier`,
    `digital_partner_breakeven` requires the word *illustration*, `client_result`
    requires its footnote. Layer 4 v4 lists capacity language among its required
    negative checks, and the failure it guards against is not somebody writing
    something false — it is somebody writing *"up to 300 new enquiry records a
    month"* and a reader carrying away *"300 leads"*. The caveat is what stops
    that, so it is not a footnote anyone can drop while tidying a layout. The
    rule reads the decoded JSON and skips `_comment` and `_note` keys: a first
    version pattern-matched the text, fired on the very comments written to
    explain it, and — when that was patched with a regex — silently ate most of
    each file and passed everything.
14. **Withdrawn facts and retired names** — `agency_adspend_range`,
    `digital_partner_adspend_start` and `growth_partnership_stages` stay
    withdrawn, and the five retired plan names stay retired. Both halves exist
    because the same thing keeps happening: a figure is removed from one file
    and survives in another. The plan-name half matches a plan *label*, not the
    bare word, because two service pages legitimately say *"Standard-product
    thinking"* and *"Standard low-ticket tactics"* — and a rule that fires on
    approved copy is a rule everyone learns to skip.

---

## 6. Sitemap

```
PRIMARY  (in the header nav)
/                              Home
/plans/                        The three plans
/digital-partner/              Digital Partner detail (Video 3 public)
/growth-partnership/           Desire and structure only
/how-it-works/                 The chain, who does what, FAQ
/proof/                        Credentials, the case, client sites
/about/                        Founder, team, Instagram
/start/                        THE GATE — four numbers → WhatsApp

SPECIAL ENTRY  (not in nav; linked from client websites)
/built-by-brandsure/?ref=clientslug

OTHER SERVICES  (footer only, standalone offers, SEO)
/services/                                        "Other Services" index
/furniture-growth-marketing-india/
/furniture-business-consulting-india/
/furniture-product-design-services-india/
/furniture-labour-hiring-india/                   own enquiry path, no GP bridge

CONTENT
/blog/
/blog/<post>/

LEGAL
/privacy-policy/   /terms-and-conditions/   /cookie-policy/   /disclaimer/

REDIRECT
/pricing/  →  301  →  /plans/          (the only 301; see REDIRECTS.md)
```

The four service URLs keep their slugs and are **not** redirected. `/services/` is not
redirected either.

---

## 7. Navigation

**Header (identical on desktop and mobile):**

`Home` · `Plans` · `How It Works` · `Proof` · `About` — plus the primary CTA button **`Start`**.

`Digital Partner` and `Growth Partnership` are reached from `/plans/`, not from the header.
**Other Services never appears in the primary navigation.** It lives in the footer.

**Footer:**
* Column "Other Services" — the five service URLs (`/services/` + the four pages).
* Column "Company" — About, Proof, Blog, Contact.
* Column "Legal" — the four legal pages.
* Sticky mobile bar on every public page: WhatsApp + Call. No page is a dead end; every page
  ends with a route to `/start/`.

---

## 8. Content ownership (Override 4)

Two ownership classes. A page belongs to exactly one. Full mechanism in
`CONTENT-ARCHITECTURE.md`; the rule is here because it is a source-of-truth rule.

**CONTROLLED — developer-owned, JSON + template, block editor disabled**
`/` · `/plans/` · `/digital-partner/` · `/growth-partnership/` · `/how-it-works/` · `/proof/` ·
`/start/` · `/built-by-brandsure/` · `/services/` (index only) · all commercial facts · all
funnel-critical messaging.

**EDITABLE — WordPress-owned, block editor, no JSON copy exists**
the four standalone service pages · `/about/` (team, Instagram) · `/blog/` and posts ·
the four legal pages.

Enforcement: a slug registry in `inc/content-ownership.php`. For a CONTROLLED slug the theme
disables the block editor and renders only from `data/`. For an EDITABLE slug the theme ships
**no** data file, and the guard fails the build if one appears. That is what makes double
editing structurally impossible rather than merely discouraged.

---

## 9. Theme tokens (BSU Theme File)

Authoritative values. Implemented once in `assets/css/tokens.css` as CSS custom properties;
nothing else in the theme may hard-code a colour.

### Colour

| Token | Value | Use |
|---|---|---|
| `--bs-blue` | `#116E98` | Primary actions, logo blue |
| `--bs-blue-hover` | `#0D5D81` | Primary button hover (15% darker) |
| `--bs-aqua` | `#7DD1C9` | Logo aqua, accents, highlights |
| `--bs-green-verification` | `#C6E7B9` | Verification success, tags |
| `--bs-green-verified` | `#A6DA8E` | Darker success alternate |
| `--bs-green-dot` | `#22BE6C` | Confirmation dot / tick |
| `--bs-green-verified-border` | `#7DCC67` | Success tag border |
| `--bs-yellow` | `#FCC851` | Warning / review-state tag text |
| `--bs-yellow-bg` | `#FBF3DF` | Warning tag background |
| `--bs-yellow-border` | `#FFEDC2` | Warning tag border |
| `--bs-headline` | `#081E2E` | Headlines, important labels |
| `--bs-body` | `#4B6673` | Paragraphs, descriptions |
| `--bs-subtle` | `#7F96A0` | Captions, inactive text |
| `--bs-bg` | `#FFFFFF` | Page and card background |
| `--bs-bg-footer` | `#17232B` | Footer / dark sections |
| `--bs-border` | `#E8F3F2` | Fine card borders |
| `--bs-border-light` | `#F0F8F7` | Extremely light borders |
| `--bs-border-secondary-btn` | `#CDE7E4` | Secondary button border |
| `--bs-secondary-btn-hover-bg` | `#F0FBFB` | Secondary button hover fill |
| `--bs-meter-track` | `#DDE7EC` | Progress meter track |
| `--bs-tag-pending-bg` | `#EAF0F3` | Pending tag background |
| `--bs-tag-pending-border` | `#D6E0E6` | Pending tag border |
| `--bs-pattern` | `#E4F4F3` | Background 'B' line pattern, 3–5% opacity |

### Type

Inter, in one scale. `--bs-font: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`.

| Token | Weight | Size |
|---|---|---|
| `--bs-fs-header` | 700 | 2.5rem / 40px |
| `--bs-fs-subheader` | 600 | 1.25rem / 20px |
| `--bs-fs-body-lg` | 400 | 1rem / 16px |
| `--bs-fs-body` | 400 | 0.875rem / 14px |
| `--bs-fs-button` | 600 | 0.875rem / 14px |

### Shape and depth

| Token | Value |
|---|---|
| `--bs-radius-button` | `10px` |
| `--bs-radius-card` | `20px` |
| `--bs-radius-pill` | `100px` |
| `--bs-radius-tag` | `16px` |
| `--bs-shadow-card` | `0 10px 30px rgba(17,110,152,0.05)` |
| `--bs-shadow-card-hover` | `0 15px 40px rgba(17,110,152,0.07)` |
| `--bs-gradient-signature` | `linear-gradient(135deg,#116E98 0%,#7DD1C9 65%,#C6E7B9 100%)` — decorative glow only, never a fill |
| `--bs-gradient-aqua` | `linear-gradient(135deg,#7DD1C9 0%,#30A4A0 100%)` — progress bars only |

### Forbidden

Navy `#0E2439` · gold `#C79A4B` · cream `#F3ECDD` · terracotta `#A85A32` and every other deck
colour · Bookman Old Style · any gradient not listed above · any new brand colour · heavy
shadows · generic SaaS styling.

### Layout rule

**Design at 390px first.** Desktop is the expanded version, never the source layout. Breakpoints
are min-width only. QA at 360 / 390 / 430 px before any desktop check.

---

## 10. Language

Write for a furniture manufacturer, sofa factory owner, upholstery business owner or showroom
owner reading on a phone. The page must be understandable within seconds.

**Use:** enquiry · call · WhatsApp · showroom visit · appointment · order · advance · production ·
delivery · payment · monthly sales · bank balance · profit left at month end · salesperson ·
factory capacity · ad money · customer · workers · orders · enquiries.

**Never on screen:** CRM (untranslated) · funnel · attribution · retargeting · CRO · nurturing ·
conversion optimisation · customer journey · lifecycle marketing · marketing automation · ROAS ·
performance architecture · lower-funnel · SKU · product-market fit · "45 services" as a headline.

`CRM` may appear at most once per page and only immediately translated as
*"one place where every enquiry is written down."*

**The one chain, always in this order, never a second diagram:**
`Ad → Enquiry → Call → Appointment → Showroom Visit → Order → Production → Delivery`

---

## 11. What the website is, and is not

The website is **not** the salesman. It is the **front door + trust + proof + routing**.

```
Visitor → trust → Video 1 → four-number gate → WhatsApp AI
```

The deeper sales education happens in the private funnel (WhatsApp AI → `go.brandsure.in/a/[token]`
→ Videos 2–5 → business review → founder). **Private funnel content is never moved back onto the
public site.**
