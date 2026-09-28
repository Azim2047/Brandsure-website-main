# Open Items

*Only items that are genuinely unresolved in the supplied files. Anything the project documents
already answer has been decided in `SOURCE-OF-TRUTH.md` and is not repeated here.*

Each item states what is blocked, what the sources actually say, and what I will do if no answer
arrives — so the build is never stalled waiting on a reply.

---

## 1. WhatsApp number — RESOLVED

**Supplied: `+91 76249 64199`.** Stored in `data/site.json` as `917624964199` — E.164 without
the `+`, which is the `wa.me` path segment.

Deliberately a different number from the public phone line below: WhatsApp goes to the AI
assistant, the phone goes to a person. Nothing in the build assumed they would be the same.

Verified in a browser with the real number in place: the gate produces a valid deep link
carrying the four numbers and the `Ref:` session code; a `plan=Growth Partnership` link typed by
hand is still rewritten to `Interested in:` (Override 3); the sticky bar and the four service
enquiry paths all resolve.

---

## 2. Events — the URL is in; the n8n and Supabase side does not accept them yet

**(a) The base URL is RESOLVED.** Supplied as
`https://n8n.srv1944094.hstgr.cloud/webhook/landing-event`; stored in `data/site.json` as the
base `https://n8n.srv1944094.hstgr.cloud/webhook`, to which `inc/events.php` appends
`event_path`. Confirmed against the Production URL on the WF-03 Webhook node itself. The
website now posts a real endpoint, and `tools/guard.php --launch` passes.

**(b) UNRESOLVED, and bigger than first recorded.** With the n8n instance readable, WF-03 and
the Supabase schema were inspected directly rather than reasoned about from the supplied JSON.
There are **six** independent reasons a website event does not land today, not the two this item
previously listed. Every one of them is on the n8n/Supabase side; none is in the website.

| # | Where | What happens | Fix |
|---|---|---|---|
| 1 | WF-03 | `active: false`, `triggerCount: 0`. An inactive workflow's production webhook returns 404, so nothing arrives at all. | Activate it. |
| 2 | Webhook node | `allowedOrigins: "https://go.brandsure.in"`. The site posts a JSON Blob, which is not a CORS-safelisted content type, so the browser sends a preflight — and the allowlist does not include `https://brandsure.in`. Blocked before it leaves the browser. | Add `https://brandsure.in` to the allowed origins. |
| 3 | Verify JWT → Normalize Event | `if (v.error \|\| !leadId) return []` — fail closed. The website sends no token and has no `lead_id`, because a visitor is anonymous until they message on WhatsApp. Dropped silently. | An anonymous branch keyed on `session_id` that skips the JWT check for `source: "website"`. |
| 4 | Normalize Event | The `VALID` whitelist holds the 15 funnel event names. None of the site's 13 is on it. Dropped silently. | Add the website names, or branch before the whitelist. |
| 5 | Apply Event | The SQL hardcodes `source` to `'landing'`. Even a patched Normalize would write website rows as funnel rows, losing the distinction Architecture v3 §8 exists to make. | Take `source` from the payload. |
| 6 | Supabase `lead_events` | `lead_id` is `NOT NULL` with a foreign key to `leads(lead_id)`, so an anonymous row cannot be inserted at all. And `source` carries `CHECK (source = ANY ('landing','whatsapp','system'))`, so `'website'` is **rejected by the database**. | A schema change: make `lead_id` nullable, and add `'website'` to the check. |

Items 5 and 6 are new information. This is not only a workflow edit — **the database cannot store
a website event as currently defined**, and that is worth knowing before anyone spends an
evening on the n8n side alone.

The stitch still needs designing too: when a first WhatsApp message arrives carrying
`Ref: <bs_vid>`, the anonymous rows with that `session_id` should be attached to the lead the
message creates. That is what makes the pre-gate browsing session worth recording at all.

Needed: a decision on who makes these six changes and when. Offered but not done — the n8n
workflow and the production database are BrandSure's, and neither is touched without a request.

If unanswered: the website is complete and correct. It posts well-formed events to the right
URL; they are refused at the far end. Nothing breaks, no page is affected, no CTA is harmed —
the funnel simply learns nothing about pre-gate behaviour. Turning it on is entirely their-side
work now.

---

## 3. First-touch WhatsApp prefill format — needs a joint decision

**Unresolved.** WF-01's deep-link parser recognises only
`^(CONFIRM|TERMS|QUESTION|CLARIFY|RENEW|HELLO)\s+(BS-\d+)` — a code word followed by an
**existing** lead id. Every supplied example is a return visit from the gated funnel, where the
lead already exists.

A first-touch visitor from `/start/` has no `BS-#####`. The supplied files never define what
that first message looks like, so the four numbers, the `bs_vid` session code and the `plan=` /
`interest=` route have no agreed carrier.

Needed: one agreed prefill format. My recommendation, for approval rather than assumption —
keep it human-readable, because the owner sees it in their own chat before sending, and any
machine-looking string reduces the send rate:

```
Hi BrandSure, I want to grow my furniture business.
Monthly sales: <a>
Ad spend now: <b>
Showrooms: <c>
Monthly production: <d>
Ref: <bs_vid>
```

with the route appended as a plain line (`Interested in: Digital Partner`) rather than as a
query-string token. n8n parses the labelled lines; `Ref:` does the session stitch.

**Status: the fallback is now built** (Chat 3). The template lives in `data/site.json` under
`whatsapp_templates.gate`, marked provisional there. Changing it is a data edit, not a code
change.

What n8n needs to parse, exactly as the site now sends it:

```
Hi BrandSure, I want to grow my furniture business.
Monthly sales: 8 lakh
Ad spend now: 20,000
Showrooms: 1
Monthly production: 40 sofas
Plan: Digital Partner          ← or "Interested in: Growth Partnership", or absent
Ref: 7fk2m9qp1x                ← the bs_vid session code
```

Three behaviours the parser can rely on:

* **A line with an empty value is dropped before sending.** Someone who fills two of the four
  fields sends two labelled lines, never `Showrooms:` with nothing after it.
* **With JavaScript off, only the greeting is sent** — every labelled line is a placeholder, so
  all of them drop. The AI then asks its normal qualification questions. Degraded, not broken.
* **`Plan:` never carries Growth Partnership.** A hand-typed or stale `plan=` link for it is
  coerced to `Interested in:` in the browser as well as in PHP (Override 3). Verified in a real
  browser.

Still needed: confirmation of this format, and the WF-01 parser change to accept a first-touch
message that carries no `BS-#####` id.

---

## 4. Standard / Expert → Agency / Digital Partner rename in the AI stack

**Unresolved, and Architecture v3 §0 already flags it** ("Naming drift ... one rename pass is
required") but records no decision.

Supabase `commercial_facts` seeds `standard_fee`, `expert_fee`, `min_period_std_exp`,
`expert_capacity`, `ads_payer_std_exp`, `recommended_adspend_standard`. The Part 3 copy pack and
the Part 4 system prompt use the same names.

The website will not adopt Standard/Expert. This does not block any page, but it does block
guard rule 5 — the price-parity check that stops someone updating ₹75,000 in one place and not
the other. That check is the whole reason the AI, the funnel and the site provably quote the
same numbers, so it is worth closing.

Needed: confirmation that the rename may be applied to `commercial_facts`, the copy pack and the
AI system prompt, and by whom.

If unanswered: guard rule 5 ships disabled with a `TODO` naming this item, and the website
quotes the Numbers Sheet directly.

---

## 5. Public phone and email — RESOLVED

**Supplied: `+91 88911 99199` and `info@brandsure.in`.**

This settles the conflict rather than picking a side of it. The old site's schema carried
`info@brandsure.in` and `+91-8884446668`; Numbers Sheet A4 carried `anasuu@brandsure.in`. The
email is confirmed as the old site's; the phone is **a third number**, newer than either source,
so `8884446668` appears nowhere in this build and neither does the personal address.

Stored as `phone_display` `+91 88911 99199` for reading and `phone_tel` `+918891199199` for the
`tel:` link — a phone number a visitor reads and a phone number a phone dials are not the same
string, and keeping both means neither is derived by guesswork at render time.

Now live in: the sticky Call bar on every page, and the `email` and `telephone` fields of the
Organization schema, which had been omitted rather than invented while this was open.

One thing to confirm at your convenience, not a blocker: **the footer does not print the phone
number or the email address as text.** Both are reachable — the Call button is in the sticky bar
on every page and the email is in the Organization schema — but neither is written out where
someone can copy it. That was the right call while the values were unknown; now that they are
real, say the word and they go in the footer.

---

## 6. Growth Partnership public page — no approved copy beyond three sentences

**Partly unresolved, and it is a content gap rather than a contradiction.** Override 1 gives the
exact approved commercial paragraph, and Override 2 gives the five stage names. Architecture v3
§5 lists the remaining sections (nested boxes, what we invest in, what stays yours, the honest
qualification line) — but their public wording exists nowhere. Every fuller description of
Growth Partnership in the supplied files is Video 4/5 material and is forbidden in public.

So `/growth-partnership/` has a specified structure and roughly 60 approved words to fill it.

Needed at Chat 6: approved public copy for the "what we invest in" swimlane labels (six items,
no rupee values), the seven "always yours" items, and the qualification paragraph.

**Status: the page is built and the draft copy needs sign-off** (Chat 6). It was written from
the structure of Video 5 S1 and S2 — which is non-commercial — translated into persona language.
No commercial mechanism can leak from those two slides because neither contains one.

**Approved and untouchable** (do not edit without a new instruction):

* the hero headline — *"We invest in the growth side with you."*
* the hero body — *"BrandSure funds the agreed Google and Meta advertising under the Growth
  Partnership."*
* the first paragraph of "How the commercials work" — *"The complete commercial structure is
  explained with your business numbers after a business review."*
* the five stage names — Foundation, Stability, Profit per Order, Scale, Stable at Scale.

**Draft, needs your approval** — each is marked `"_draft": true` in
`data/pages/growth-partnership.json`, so what is and is not signed off is visible in the file
itself:

| Section | What it says |
|---|---|
| Nested boxes | *"A growth layer around your business. Not a takeover."* plus the two box labels and the closing line |
| What we put in | six labels and their one-line notes — no value anywhere |
| Stage bodies | one line per stage describing what gets built. **No duration on any of them** |
| What stays yours | the seven items, and the line about no ownership, no factory, no bank account |
| Not offered to everyone | the honest-qualification paragraph |
| Second commercial paragraph | says only that the explanation happens privately and later |
| Closing CTA | headline and body |

Reviewing it: the fastest check is that the rendered page contains no `₹`, no percentage, no
month or year count, no "deposit" and no "setup fee". Guard rule R2 enforces exactly that on
this page and fails the build otherwise, so the question left for a human is whether the words
are *right*, not whether they are *safe*.

---

## 7. Video 1 asset

**Unresolved.** The build architecture specifies Video 1 in full and the site's whole public
journey routes through it, but no recorded file, YouTube id or poster image is supplied.
Video 1 is listed as not yet recorded (deck build order, Chat 9: *"Record V1, V2 → test with 3
prospects"*).

Needed: the YouTube id and a poster image, or confirmation of the launch order.

If unanswered: Home ships with the Video 1 block present and its id empty in `data/videos.json`.
The theme renders the gate card in place of the player when the id is empty, so the page is
complete and the conversion path still works — the video slots in as a data edit.

---

## 8. Client list for `/built-by-brandsure/` and `/proof/`

**Unresolved.** Architecture v3 makes `?ref=clientslug` the warmest entry in the system and
`/proof/` lists "client sites", but no client names, slugs, logos or URLs appear in any supplied
file, and the one verified client result is deliberately anonymous.

Needed: which client websites may be named publicly, their slugs, and written permission where
required.

**Status: both pages are built and shipped without it** (Chat 7). `/built-by-brandsure/`
shows the generic variant to every visitor and `/proof/` carries a short block explaining why
there is one result rather than a wall of logos — which reads better than a thin list would
have.

The `?ref=` mechanism is built and tested, so this really is a data edit when you have the
permissions: add `{ "slug": "...", "name": "...", "url": "..." }` to the `clients` array in
`data/proof.json` and nothing else changes. Verified with a temporary entry — a matching `ref`
renders *"You just visited Acme Furniture's website"*, an unknown or malformed one falls back
to the generic line rather than breaking, and the parameter is sanitised.

---

## 9. Two BSU Theme File colours fail contrast — deviations applied

### 9a. The warning tag

**Needs confirmation, not a decision.** The Theme File specifies the warning
tag as `#FCC851` text on a `#FBF3DF` fill. Measured, that pairing is **1.40:1**.
The minimum for body-size text is 4.5:1, so as specified the label is
effectively invisible — this is a defect in the source, not a preference.

Applied now: `--bs-yellow` is kept exactly as specified for accents, borders
and fills, where it is correct. A second token `--bs-yellow-text` (`#8A6410`)
is used wherever the yellow carries text or a glyph. It reads as the same
colour and measures 4.85:1 on the specified fill.

Needed: confirmation that this is acceptable, or a different approved text
colour for the warning state.

If unanswered: the deviation stands. It is documented in `tokens.css` at the
token, so it cannot be mistaken for drift.

### 9b. The subtle text colour — found in Chat 12's audit

**Same shape of problem, found by measuring rather than by looking.** The
Theme File specifies `--bs-subtle` as `#7F96A0`. Every use of that token in
the theme sets the colour of text or of a glyph beside text: the date on a
post card, the "You do" lane labels, the service-page byline, the swipe hint
under the compare table, the case context line on Home and Proof. On white it
measures **3.10:1**, and on the tint background **2.88:1** — below the 4.5:1
that body-size text needs. Ten elements across seven pages, all one colour.

This is the sort of thing that survives a build precisely because it looks
fine to someone who already knows what the words say.

Applied now: the token is corrected in place to `#5A7080`, which reads as the
same cool grey and measures 5.17:1 on white, 4.79:1 on the tint and 4.56:1 on
the card border colour, while staying clearly lighter than `--bs-body`
(6.09:1) so the hierarchy still reads. Unlike the yellow, this token has no
non-text use, so there is no second variant.

Correcting it exposed a second, older mistake: the footer's copyright line was
using this light-surface token on the dark footer, where it had been passing
by accident. It now uses `--bs-on-dark-muted`, which is the token for that
job.

Needed: confirmation, or an approved alternative grey.

If unanswered: the deviation stands, documented at the token. Every rendered
page now passes AA at 390px with no exceptions.

---

## 10. Logo — only a light-on-dark asset exists

**Partly solved, needs a proper asset.** The only logo in the supplied bundle
is `brandsure-logo.png`, 128×34, in which the word "Brand" is **white**. It is
built for dark backgrounds. On the white header the word disappeared and the
lockup read as "Sure".

Applied now: the PNG is a palette image, so a light-background variant was
derived by recolouring only its neutral palette entries to `#081E2E`, leaving
the aqua/blue/green mark untouched. Antialiasing is carried in the alpha
channel, so no edge quality is lost. The header uses that variant
(`brandsure-logo-dark-text.png`); the footer uses the original, which is
correct on dark.

Needed: the real logo files — ideally one SVG lockup, or the light-background
PNG at 2× — plus confirmation that the derived variant's ink colour is right.

If unanswered: the derived variant stands. It is pixel-identical to the
original apart from the wordmark colour.

---

## 11. Microsoft Clarity is already active on the site — the policies must say so

**Answered by looking, in Chat 14, and the answer is yes: something is tracking.** This item
previously asked whether GA or a Meta pixel would be added before launch. Reading the live
install answered a question nobody had asked: **Microsoft Clarity 0.10.29 is installed and
active** on brandsure.in right now.

Clarity is session-replay and heatmap analytics. It records how people move through pages, sets
its own cookies, and sends that to Microsoft. It is a great deal more than the first-party
`bs_vid` event the theme sets.

**The Cookie Policy and Privacy Policy as written are therefore wrong for this install.** Chat 10
corrected them in the opposite direction — the old policies claimed Google Analytics, Google Ads
remarketing and the Meta pixel, none of which the *theme* uses, so those claims were removed and
the policies now describe a site whose only cookie is `bs_vid`. That was accurate for the theme
and is inaccurate for the site the theme is about to run on.

Three ways out, and this is a decision rather than a fix:

1. **Deactivate Clarity.** The policies are already correct, nothing else to do. The theme's own
   event stream covers the funnel questions that matter.
2. **Keep Clarity.** Both policies need a section naming it, what it records, whose cookies they
   are, and a link to Microsoft's privacy terms — **written and published before the site goes
   public, not after.** Session replay of a form-filling visitor is exactly the processing a
   privacy policy exists to disclose.
3. **Keep Clarity and add GA or a Meta pixel too.** As (2), plus a consent mechanism the site
   does not currently have and would then need.

Still needed either way: confirmation of whether GA or a Meta pixel is also planned, since
Architecture v3 §8 puts Meta CAPI in the private funnel and it is a fair guess something is
wanted on the public site.

If unanswered: **the site should not be made public.** This is the one open item that is a
compliance problem rather than an inconvenience — a privacy policy that omits active session
recording is worse than no privacy policy, and unlike everything else on this list it cannot be
fixed after launch without having already been wrong in public.

---

## 12. A share image — 1200 × 630, supplied by BrandSure

**Raised in:** Chat 11, building `inc/seo.php`.
**Blocks:** how the site looks when anybody pastes a link into WhatsApp.

The only image asset in the theme is the logo, at 128 × 34 pixels. Every platform that renders a
link preview — WhatsApp above all, which is where this site's links will actually travel —
requires an image of at least 200 × 200 and renders 1200 × 630 properly. Pointing `og:image` at
a 128 × 34 logo produces either a broken thumbnail or nothing, depending on the platform, which
is worse than the clean text-only card you get by omitting the tag.

So no `og:image` is emitted. A link to brandsure.in shared on WhatsApp today shows the title and
description, correctly, with no picture.

Needed: one 1200 × 630 image. A photograph of a factory floor or a finished sofa would do more
than a logo on a background — the reader is a manufacturer, and the recognition this site trades
on is visual.

If unanswered: the text-only card stands. It is not broken, only plainer than it could be.

---

## 13. `hello@brandsure.in` and the single WhatsApp number — CONFIRM BEFORE LAUNCH

**Raised in Chat 15. This is the only change in that chat that overrides something BrandSure
supplied directly, so it should not go live unread.**

`ACTIVE/numbers.v2.js` (5 September 2026) is named the authority for contact details by Layer 4
v4 §2, and the accompanying update prompt says in terms: *"Do not use older chat memory to
override these files."* It gives:

```
WHATSAPP: "+91 8891 199 199",
EMAIL:    "hello@brandsure.in",
```

Both differ from what this build carried, and what it carried came from BrandSure in an earlier
chat. The file wins on the stated authority, so it has been applied. Two things follow that only
BrandSure can settle:

**(a) The WhatsApp line is now the same number as the phone line.** They were deliberately
different — `data/site.json` recorded that the WhatsApp number reached the AI assistant while
the phone number reached a person. numbers.v2.js gives one number for both, which quietly undoes
that split. If it should stand, put `917624964199` back into `contact.whatsapp_number`; nothing
else changes, and every wa.me link on the site is built from that one field.

**(b) `hello@brandsure.in` must actually receive mail.** It is now the published contact in
Organization schema *and* the grievance contact on all four legal pages, which previously said
`info@brandsure.in`. Under the Indian IT Rules a grievance address that bounces is a compliance
problem rather than a typo. If the mailbox does not exist, either create it or revert
`contact.email` — guard R12 keeps the legal pages in step with whichever is chosen, so there is
no way to change one and forget the other.

Both previous values are written into `data/site.json`'s own notes, so reversing either is a
one-field edit rather than an archaeology exercise.

If unanswered: the site launches on numbers.v2.js's values, which is the defensible reading of
the authority chain — but (b) is the kind of thing that is embarrassing to discover from a
bounced complaint.

---

## Not open — decided, recorded, and listed here only so they are not re-asked

| Question | Where it is answered |
|---|---|
| Which colours the website uses | `SOURCE-OF-TRUTH.md` §9 — BSU Theme File. Deck navy/gold is never a website colour. |
| Whether the private funnel gets restyled to the BSU theme (Architecture v3 §10 decision 1) | Out of scope for the website build. The public site does not move off the BSU theme, which is the only part this project controls. |
| Public name of the third plan | `SOURCE-OF-TRUTH.md` §3 conflict 4 — **Growth Partnership**. |
| Whether `/plans/` may say "revenue-share partnership" | No. `SOURCE-OF-TRUTH.md` §3 conflict 1 — Override 1 wins. |
| Whether the GP CTA passes `plan=` | No. Override 3 — `interest=Growth Partnership`. |
| Whether Video 3 may be public | Yes. Architecture v3 §5 — it contains no partnership economics. |
| Whether the four service URLs get redirected | No. `REDIRECTS.md` §3. |
| Whether Other Services goes in the header | No. Footer only. |
| Generic bands vs named brands on the route-cost comparison | Moot for the website — route-cost comparisons are private (Override 2) and never appear publicly. |
