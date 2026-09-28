# Disclosure — the four Overrides, and what enforces them

The highest-stakes rules in the project. A layout mistake costs an afternoon; a
disclosure mistake puts commercial terms on a public page.

---

## Override 1 — public commercial disclosure

The public brandsure.in site must **never** disclose: `20%`, `80%`,
`commission`, `revenue share`, `share of sales`, or the exact partnership
percentage.

Not through **any** of: visible copy · schema · metadata · HTML comments ·
JavaScript · JSON sent publicly · alt text · a FAQ answer · a `wa.me` prefilled
message · a `data-` attribute.

**Growth Partnership itself must be VERY visible.** We hide the commercial
mechanism, not the plan. Removing the plan from the site is as wrong as
publishing its terms.

### The approved public wording — use these, do not paraphrase

> We invest in the growth side with you.

> BrandSure funds the agreed Google and Meta advertising under the Growth
> Partnership.

> The complete commercial structure is explained with your business numbers
> after a business review.

> Product, pricing, showroom and business systems are introduced progressively,
> as your business becomes ready for each one.

---

## Override 2 — partnership term

Never publicly: `36 months` · `₹1,20,000` setup · `₹3,00,000` deposit · the
Monday/Tuesday/Wednesday payment rhythm · `SWO` / Showroom Walk-In Offset ·
the `₹33,000` sofa calculation · the `₹55 lakh` P&L · route-cost comparisons ·
anything from Video 4.

**No public duration, and no public stage list.** The five-stage public roadmap
was withdrawn in Chat 15: Layer 4 v4 §4 retires the public five-stage diagram,
and numbers.v2.js had already replaced those five with four *private, dated*
stages. Naming stages publicly invites the question of how long each takes, and
that answer is private. Describe progressive work instead.

---

## Override 3 — qualification routing

| Plan | Parameter |
|---|---|
| Agency | `plan=Agency` |
| Digital Partner | `plan=Digital Partner` |
| Growth Partnership | **`interest=Growth Partnership`** — never `plan=` |

Public GP call to action: **"Check If My Business Qualifies"**.

`bs_whatsapp_link()` in `inc/events.php` enforces this in code as well as in
data — it rewrites `plan` to `interest` if a Growth Partnership value ever
reaches it. Guard rule R3 checks every link on every rendered page.

---

## Override 4 — content ownership

**Never maintain the same copy in both JSON and WordPress.** Every page is one
of two classes:

| | Content lives in | Editor |
|---|---|---|
| **Controlled** | `data/pages/<slug>.json` | disabled; `post_content` forced empty |
| **Editable** | WordPress | no JSON file exists, and the guard fails if one appears |

Three runtime locks hold this: the editor lock, the content lock
(`wp_insert_post_data`), and the template lock (`template_include`).

---

## Current commercial facts

From `numbers.v2.js` (5 Sep 2026) and Layer 4 v4. Full history in
`docs/LAYER-4-V4-CHANGE-REGISTER.md`.

| Fact | Value |
|---|---|
| Agency | ₹45,000 + GST/month · ₹25,000 + GST setup |
| Digital Partner | ₹75,000 + GST/month · ₹25,000 + GST setup |
| DP month one | ₹1,00,000 + GST — **fees**, not an ad budget, not an all-in cost |
| Minimum term | 3 months, then month to month |
| DP capacity | 1 city · handling capacity for up to **300 new enquiry records** a month |
| Advertising | **Paid separately. There is no standard budget and no figure.** |
| Contact | `hello@brandsure.in` · `+91 88911 99199` · WhatsApp `918891199199` |

### Three qualifiers that are not optional

Guard rule R13 fails the build if the number appears without them:

- **Capacity** — "not a promise of 300 enquiries, visits or orders". A capacity
  figure alone becomes "300 leads" by the time it reaches the second person who
  hears it.
- **Break-even** — five orders covers **the monthly fee only**, at an *assumed*
  ₹15,000 profit an order. Excludes setup, advertising, GST and everything
  else. Call it an illustration; never a business break-even.
- **The client result** — always with its "one client result, outcomes depend
  on product, city, budget and execution" footnote.

### Withdrawn — must not come back (guard R14)

`agency_adspend_range` (₹50,000–₹2,00,000) · `digital_partner_adspend_start`
(₹1,00,000/month) · `growth_partnership_stages` (the public five).

### Retired plan names (guard R14)

Standard · Expert · Virtual Partner · Virtual Growth Partner · Virtual Growth
Partnership. Only **Agency**, **Digital Partner**, **Growth Partnership** exist.

R14 matches plan-label context, not the bare word — two service pages
legitimately say "Standard-product thinking". A rule that flags approved copy
is a rule everyone learns to skip.

---

## The guard's 14 rules

`php wp-content/themes/brandsure-v2/tools/guard.php` — must print PASS.

| | |
|---|---|
| R1 | global disclosure blocklist |
| R2 | Growth Partnership's stricter list on its own page |
| R3 | route contract (Override 3) |
| R4 | banned language — including "CRM" without translating it on the spot |
| R5 | price parity with Supabase *(disabled pending the rename)* |
| R6 | FAQ honesty |
| R7 | word budget (a target for marketing pages, not a truncation rule) |
| R8 | ownership consistency (Override 4) |
| R9 | launch readiness |
| R10 | page metadata — title ≤60, description ≤160 |
| R11 | no function declared at file scope in `components/` or `templates/` |
| R12 | seed contact parity with `data/site.json` |
| R13 | mandatory qualifiers |
| R14 | withdrawn facts and retired plan names |

**If a guard rule fires on legitimate content, fix the rule — not the content,
and never the escape hatch.** A rule that flags approved copy teaches everyone
to bypass it. Every rule above has been corrected at least once for this reason.
