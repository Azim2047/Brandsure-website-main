# Mobile, motion and the WhatsApp panel

*Chat 15. The rules the phone layout, the animation and the WhatsApp component follow, and the
evidence each one was checked against. 99% of visitors are on a phone: mobile is the design, and
desktop is the version with more room.*

---

## 1. Touch targets — 48px, enforced by one token

Layer 4 v4: *"Keep mobile body text readable, touch targets at least 48 px."* The build was at
44px, the WCAG 2.5.5 floor. Every interactive rule now reads `var(--bs-tap)` from `tokens.css`;
none carries its own number, so the floor can only be raised in one place.

What that caught, measured in a browser at 360/390/430 across all 21 surfaces:

| Control | Was | Now |
|---|---|---|
| Every button (`.bs-btn`) | 44px | 48px |
| **Footer links — 13 of them, on all 21 pages** | **16px tall, 8px apart** | 48px rows on mobile; compact again ≥768px |
| Menu toggle | 34 × 44 | 48 × 48 |
| Header logo, plan detail link, "Read" link, pagination | 44px | 48px |
| Blog card title | 46px text run | the whole 350 × 332 card |

**Result: no target below 48px on any surface at any of the three widths.**

Two deliberate exceptions, both verified rather than waved through:

* `.bs-byline` stays at 14px. It is attribution metadata, not content.
* Links inside editor prose are exempt — WCAG 2.5.8 exempts targets inline in a block of text,
  and padding links inside a paragraph would wreck the line spacing around them.

## 2. Readable text

Body is 16px at line-height 1.6. The change was to **notes**: the sentence under a table, under
a set of figures, under the five-order illustration. Ten of them were 14px, and 14px is the size
a reader's eye treats as optional. They are the most important sentences in their sections — they
say what the number does *not* mean. They now use `--bs-fs-note` (16px); what marks them as
secondary is colour and position, not smallness.

## 3. The comparison — no longer a desktop table on a phone

It was a 40rem table in a horizontal scroller with a "swipe to see all three plans" hint. With
99% of visitors on phones, that hid two of the three plans from almost everyone.

Below 44rem each row is now a small card: the row label as its heading, then one labelled line
per plan, in reading order. Every `<td>` carries a `data-label` with its column heading, so this
is CSS over the *same* table — one table in the markup, not a table plus a phone copy of it that
can drift. The `<thead>` moves off-screen rather than being removed, so screen readers still get
the real row and column headers. Above 44rem it is the dense screenshot-friendly table again,
because owners photograph it.

## 4. Motion

Three rules, and they are all about not breaking the page:

1. **Content first.** Every animated "before" state lives under `.bs-motion`, a class
   `brandsure.js` puts on `<html>`. Script blocked, slow or broken → the finished page. Nothing
   in the stylesheet can hide content on its own.
2. **Once.** A section reveals the first time it is reached and never again.
3. **Opacity and transform only.** Both composited, neither triggers layout, so no animation can
   cause a layout shift.

150–300ms for something the reader did; 300–500ms for a section arriving. **No figure is ever
counted up** — a number animating through ₹12,000 on its way to ₹45,000 has displayed a price
that is not real, which on this site is a commercial problem rather than a visual one.

What animates, and why that thing: the hero enters in reading order; sections rise once; the
eight-step chain lights left to right 70ms apart because it is the one diagram genuinely about
sequence; plan cards stagger; a tapped plan card marks itself chosen, because the tap leaves the
page and mobile data can make that look like nothing happened; FAQ answers fade in; the header
lifts on a shadow once the page has moved.

**Why a scroll sweep and not an IntersectionObserver.** The first version used one and it left
content permanently invisible. An observer reports intersection *changes*: jump from the top of a
page to the bottom in one frame — an anchor link, a restored scroll position, a flick — and every
section in between goes from below the viewport to above it without ever intersecting. No
callback, no reveal, opacity nailed at 0. **Eleven of twelve home-page sections reproduced blank
this way.** A sweep asks whether the element's top has passed the bottom of the viewport, which
is true however the reader got there. Targets drop off the list as they reveal, and the listener
removes itself when the list empties.

`prefers-reduced-motion: reduce` turns motion **off**, not down. A 40ms animation is still an
animation.

## 5. The WhatsApp panel

Mobile: the sticky bar is the launcher — "Chat on WhatsApp" opens a bottom sheet, "Call" dials.
**No second floating button on mobile.** Desktop: a floating button opens the same thing as a
corner panel.

It is **not a chat window**, and the code never pretends otherwise: no "online now", no
response-time promise, no unread badge, no scripted agent. The footnote says the action opens
WhatsApp on the reader's own phone with the message already written.

Every option is a real `wa.me` link with a real prefilled message, so with JavaScript off the
panel is a plain list at the end of the page and every option still works.

**Where it does not go**, enforced in `bs_whatsapp_panel_enabled()` rather than remembered by a
template: `/start/` (the four-number gate is the action there, and its handoff carries figures
already typed), the four legal pages, and private token pages.

**Context.** On a service page it leads with that service. On
`/furniture-labour-hiring-india/` it offers **only** labour hiring — v4 §5 says not to push that
buyer into the main growth qualification, and offering them a plan is doing exactly that.

The one-time "Need help choosing?" invitation appears after real engagement (55% scroll or 45
seconds), never on load, and once refused stays refused for the browsing session.

**Tracking.** Five names: `whatsapp_launcher_click`, `whatsapp_panel_open`,
`whatsapp_option_select`, `whatsapp_outbound_click`, `call_click`. An outbound click is **not** a
received message and **not** a qualified lead; nothing here is called a lead or a conversation.
The received conversation is counted at the WhatsApp end against the session code in the message,
and it is a different number.

---

## 6. Two bugs worth remembering

Both were invisible in the markup and invisible on screen, and both were found by driving a real
browser rather than reading the code.

**The scrim that swallowed the whole site.** `.bs-wa__scrim` had `display: block` under a class
chain. `[hidden]` gets `display: none` from the UA stylesheet at almost no specificity, so the
author rule beat it — and a fixed, full-viewport, `opacity: 0` element sat over every page
intercepting every tap while supposedly closed. **Nothing on the site was clickable.** Fixed with
`:not([hidden])` and a one-line `[hidden] { display: none !important }` so no future rule can do
it again.

**The scroll restore that glided.** The page sets `scroll-behavior: smooth`, so restoring the
reader's position after the sheet closed *animated* — the page visibly slid back, and a touch
mid-glide cancelled it and stranded them elsewhere. Restoring a position is not navigation;
`behavior: 'instant'` now, with the plain form as the fallback.

---

## 7. Evidence

Run against all 21 rendered surfaces, complete documents, over HTTP.

| Check | Result |
|---|---|
| Touch targets, 360 / 390 / 430 | none below 48px |
| Horizontal overflow and clipped text, 360 / 375 / 390 / 412 / 430 / 768 / 1024 / 1280 | none |
| WCAG AA contrast | no element below its floor |
| JavaScript off | all 21 pages fully visible, nav reachable, WhatsApp options reachable, no overflow |
| Panel: open, step, back, Escape, focus trap, scroll lock and restore | correct on mobile and desktop; focus returns to the launcher; 0 of 14 tabs escaped the dialog |
| Panel exclusions | absent on `/start/` and all four legal pages |
| Panel context | labour hiring offers only labour hiring |
| Reduced motion | nothing left faded or mid-transition |
| Disclosure, language, route contract | clean across 21 documents |
| `php tools/guard.php` | PASS, 0 errors |

**Not tested:** a real Android handset and the WhatsApp in-app browser. Everything above is
viewport simulation in headless Chromium. The two things that genuinely need a device are the
`wa.me` handoff actually opening WhatsApp with the message intact, and the sheet's behaviour with
the on-screen keyboard raised.
