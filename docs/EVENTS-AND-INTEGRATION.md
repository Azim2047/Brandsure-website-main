# Events & Funnel Interface

*The contract between the public website and the existing n8n / WhatsApp AI funnel.*

**Nothing here is invented.** Every endpoint path, payload field and code word below is taken
from the supplied `BSU Sales Flow` package (`WF03_landing_event.json`,
`PART2_n8n_Build_Spec.md`, `Part 5 - Landing page build/index.html`,
`part1_schema.sql`). Where the website needs something the package does not define, it is
**listed as an open item, not filled in with a guess** — see `OPEN-ITEMS.md`.

---

## 1. What already exists (from the supplied package)

| Thing | Value as supplied |
|---|---|
| Event endpoint | `POST {N8N_BASE}/landing-event` — path fixed, **base host not supplied** (the landing page ships `https://YOUR-N8N-DOMAIN/webhook`) |
| Token validation | `POST {N8N_BASE}/validate-token` — used by `go.brandsure.in` only, not by the website |
| Inbound WhatsApp | `POST {N8N_BASE}/wa-inbound` — Meta → n8n |
| Transport used by the landing page | `navigator.sendBeacon` with a JSON blob |
| Event payload keys accepted | `lead_id` *(required)*, `event` *(required)*, `video`, `watched_seconds`, `position_seconds`, `route`, `ts`, `session_id` |
| Accepted event names (whitelist in WF-03) | `page_opened`, `video_started`, `video_25`, `video_50`, `video_75`, `video_90`, `video_completed`, `video5_unlocked`, `terms_viewed`, `cta_clicked`, `question_clicked`, `page_resumed`, `heartbeat`, `expiry_page_shown`, `renew_requested` |
| Behaviour on an unknown event, or a payload with no `lead_id` | **Dropped silently** (`return []` in the Normalize Event node) |
| Storage | `lead_events` (`lead_id`, `source`, `event`, `video`, `payload`, `session_id`), scored by `fn_recalc_score()` |
| `source` values in use | `landing`, `system` |
| WhatsApp deep-link codes recognised by WF-01 | `CONFIRM`, `TERMS`, `QUESTION`, `CLARIFY`, `RENEW`, `HELLO` — each **followed by an existing `BS-#####` lead id** (`/^(CONFIRM\|TERMS\|QUESTION\|CLARIFY\|RENEW\|HELLO)\s+(BS-\d+)/`) |
| Opt-out words | `STOP`, `UNSUBSCRIBE`, `NOT INTERESTED` |
| Meta CAPI stages queued from landing events | `page_opened`, `video_watched` (from `video_90`) |

**The consequence, stated plainly:** as WF-03 stands today, **every website event would be
dropped.** The website's event names are not on the whitelist, and an anonymous website visitor
has no `lead_id`. Architecture v3 §8 assumes the website posts to this same webhook; that is the
right design, but it requires a change on the n8n side. The website half of the contract is
specified below and built in Chat 11; the n8n half is Open Item 2.

---

## 2. The website event interface (this build's half of the contract)

`inc/events.php` + `assets/js/brandsure.js` emit these, and only these. Names come from
Architecture v3 §8 and do not change once wired.

| Event | Fires when | Purpose |
|---|---|---|
| `page_view` | any public page loads | entry-path analysis |
| `video1_start` | Video 1 poster tapped | hook strength |
| `video1_25` / `video1_50` / `video1_75` / `video1_complete` | YouTube player state | retention — where the hook dies |
| `plan_viewed` | `/plans/` scrolled to the plan cards | interest |
| `plan_selected` | a per-plan CTA tapped | Agency vs Digital Partner vs Growth Partnership mix |
| `start_gate_open` | `/start/` reached | intent |
| `gate_field_filled` | the fourth input completed | friction point |
| **`gate_whatsapp_click`** | the gate CTA tapped | **PRIMARY KPI** |
| `service_enquiry_click` | a service-page WhatsApp tap | standalone revenue |
| `builtby_view` | `/built-by-brandsure/` opened with a `ref` | referral value per client |

**Where the sticky bar fires, and where it does not.** The WhatsApp + Call bar is on every page,
but `service_enquiry_click` means one thing: somebody asked about a standalone service. Firing
it from the home page would put home-page taps into the number that is meant to show what the
four service pages are worth, and nobody reading that number later would know. So the bar fires
it only on those four pages. Taps from the bar elsewhere are **not counted** — that is the
honest position while Architecture v3 §8's list has no site-wide WhatsApp event. An uncounted
tap costs a number we never had; borrowing `gate_whatsapp_click` for it would corrupt the site's
primary KPI, which is a real cost.

`plan_viewed` fires from an `IntersectionObserver` at half visibility, not on page load — the
difference between "the cards were on the page" and "the cards were read". A browser without
`IntersectionObserver` sends nothing rather than falling back to a scroll handler.

### Payload

Sent with `navigator.sendBeacon`, matching the landing page's existing transport:

```json
{
  "source": "website",
  "event": "gate_whatsapp_click",
  "session_id": "<bs_vid>",
  "path": "/start/",
  "ts": "2026-09-03T10:41:02.118Z",
  "meta": { "plan": "Digital Partner" }
}
```

* `source: "website"` distinguishes these rows from the funnel's `landing` rows in the same
  `lead_events` table, exactly as Architecture v3 §8 requires.
* `session_id` is `bs_vid`, a first-party cookie (see §4). There is **no `lead_id`** — the
  visitor is anonymous until they message on WhatsApp.
* `meta` is a small flat object. It carries `plan` or `interest` on `plan_selected` and
  `gate_whatsapp_click`, and `ref` on `builtby_view`. **`meta` never carries a personal detail
  and never carries a commercial fact.**

### What the website will never send

No name, phone number, email or the four gate numbers themselves. The four numbers travel to
BrandSure **only** inside the WhatsApp message the owner sends, from their own device — they are
never POSTed from the browser. The event stream records that the gate was used, not what was in
it.

---

## 3. The gate handoff

```
/start/  →  four inputs  →  wa.me deep link, prefilled + session code
                                    │
                                    ▼
                     n8n /wa-inbound → create/load lead → parse the four numbers
                                    │
                                    ▼
                     AI: confirm identity → diagnose → prospect confirms
                                    │
                                    ▼
                     AI prescribes the route → issues a signed token →
                     sends go.brandsure.in/a?t=<jwt>
```

The four numbers from `/start/` map onto the AI's qualification fields so the assistant skips
what it already has. That is the whole point of the gate: the site filters, the AI diagnoses,
and nothing is explained twice.

### The prefilled message

Shape, from the supplied files:

```
https://wa.me/<WA_NUMBER>?text=<urlencoded message>
```

* `WA_NUMBER` — **not supplied.** Every deck and the landing page carry the placeholder
  `wa.me/91XXXXXXXXXX`; the Numbers Sheet flags it as open item A5. Open Item 1.
* The message body must (a) carry the four numbers as the owner typed them, (b) carry the
  `bs_vid` session code so n8n can stitch the pre-gate browsing session to the lead record
  created on first contact, and (c) carry the self-selected route as `plan=Agency`,
  `plan=Digital Partner`, or — for Growth Partnership — **`interest=Growth Partnership`**
  (Override 3; `plan=Growth Partnership` is forbidden and is a guard failure).
* **The provisional format is now implemented** and is set out line by line in
  `OPEN-ITEMS.md` item 3. It is labelled lines a human can read before sending. It remains
  provisional because WF-01's existing parser only recognises a code word followed by an
  existing `BS-#####` id, which a first-touch visitor does not have — so the n8n side still
  needs a change, and the format still needs confirming.

---

## 4. `bs_vid` — the session code

* First-party cookie, set on first public page view, `SameSite=Lax`, `Secure`, 180-day expiry,
  path `/`.
* Value: 10 characters, `[a-z0-9]`, generated client-side with `crypto.getRandomValues`. Random
  only — it encodes nothing about the visitor.
* Purpose: stitch the anonymous pre-gate session to the lead record n8n creates on the first
  WhatsApp message. It is the same value in the event payload's `session_id` and in the
  WhatsApp prefill.
* Not used for advertising, not shared with a third party, and named in the cookie policy.

---

## 5. Route parameters — the public contract

| Plan | Parameter passed | Public CTA label |
|---|---|---|
| Agency | `plan=Agency` | *Ask about Agency* |
| Digital Partner | `plan=Digital Partner` | *Ask about Digital Partner* |
| Growth Partnership | **`interest=Growth Partnership`** | ***Check If My Business Qualifies*** |

`plan=Growth Partnership` must never appear in a link, a prefill, a data file or the JavaScript
bundle. The guard treats it as a build failure. The reason is that `plan=` reads to the AI as a
self-selected route, and Growth Partnership is never self-selected — the AI decides the actual
recommended route after qualification.

The four service pages pass their own parameter (`service=marketing` / `consulting` / `design` /
`labour`) so n8n routes those enquiries into their own branch rather than the main growth path.

---

## 6. Naming alignment the funnel still owes

Supabase `commercial_facts` seeds keys `standard_fee` and `expert_fee`, and the Part 3 copy pack
and Part 4 system prompt still say **Standard / Expert**. The website says **Agency / Digital
Partner / Growth Partnership** and will not adopt the old names.

Guard rule 5 (price parity between `data/commercial.json` and `commercial_facts`) cannot pass
until the rename lands, so it ships disabled with a `TODO` naming the blocker. Open Item 4.

---

## 7. Configuration, not code

These values live in `data/site.json` (or `wp-config.php` for anything host-specific), never
inline in a template or in the JS bundle:

| Key | Status |
|---|---|
| `n8n_base` | **to be supplied** — no URL is invented |
| `event_path` | `/landing-event` (known) |
| `whatsapp_number` | **to be supplied** |
| `phone_display` / `phone_tel` | from the old site's Organization schema: `+91-8884446668` — to be confirmed as still current |
| `email` | `info@brandsure.in` (old site) / `anasuu@brandsure.in` (Numbers Sheet A4) — to be confirmed |

Until `n8n_base` is supplied, `inc/events.php` no-ops cleanly: it logs in `WP_DEBUG` and sends
nothing. A missing endpoint must never break a page or block the WhatsApp CTA.
