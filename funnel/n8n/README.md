# PART 2 — n8n Workflows

All five workflows are built and deployed against the Part 1 schema on Supabase project
`mzuzlfnkvsvrfmxdupvh`.

| Workflow | n8n ID | Trigger | Active | Verified |
|---|---|---|---|---|
| WF-01 Inbound Message Router | `syhzsnZ65q1EArCm` | webhook `wa-inbound` (GET + POST) | no | text inbound, STOP, silent-when-opted-out |
| WF-02 Validate Token | `jTwnM4UOIICNWbnx` | webhook `validate-token` | **yes** | invalid token, valid token + 12 facts |
| WF-03 Landing Event | `ubO7j7vD1lfzLvE5` | webhook `landing-event` | no | `page_opened` + replay dedupe |
| WF-04 Landing Link Generator | `J2C6P2DRwUq3PW4y` | called by another workflow | n/a | minted a real token end to end |
| WF-05 Follow-Up Scheduler | `PNzy0h6VROmJHoZF` | cron, every 5 min | no | halt-on-no-template, in-window routing |

Instance: `https://n8n.srv1944094.hstgr.cloud`, personal project
`akhinesh brandsure <akhinesh@brandsure.in>`.

```
funnel/n8n/
├── WF02_validate_token.json   ← importable JSON
├── WF03_landing_event.json
├── WF04_landing_link.json
└── sdk/
    ├── wf01_inbound_router.js  ← n8n Workflow SDK source, as authored
    └── wf05_followup_scheduler.js
```

## Before this can carry real traffic

1. **Meta Phone Number ID.** Three nodes ship with `REPLACE_PHONE_NUMBER_ID` in the send
   URL: WF-05 `Send Template`, WF-05 `Send Session Message`, WF-01 `Send WhatsApp Reply`.
   Everything up to the send is verified; the send itself returns
   `Object with ID 'REPLACE_PHONE_NUMBER_ID' does not exist` until this is filled in.
2. **Meta verify token.** WF-01 `Verify Reply` has `REPLACE_VERIFY_TOKEN`; it must match
   whatever you enter in Meta App → WhatsApp → Configuration.
3. **WF-01 collides with the existing WhatsApp automations** if they are bound to the
   same Meta phone number — two workflows would answer every inbound message. Check
   before activating.
4. **No approved templates exist yet.** `wa_templates` is empty, so every out-of-window
   step halts with `scheduler_halted_no_template`. That is correct behaviour, not a bug:
   Part 3 submits the templates that un-gate it.
5. **Message copy is placeholder.** WF-05's `COPY` map holds `[C1 placeholder] ...`
   strings pending the Part 3 copy pack.
6. **The voice path is untested.** WF-01's `Get Media URL` → `Download Audio` →
   `Transcribe` chain is built but needs a real voice note to exercise; the multipart
   upload to Whisper in particular has not been run.
7. **The RENEW branch is not wired.** WF-01 classifies `RENEW BS-xxxxx` into `deep_code`
   but does not yet decrement `renewals_left`, revoke old tokens and call WF-04 — the
   reply copy for that flow belongs to the AI brain, so it lands with Part 4.

## The parameter-passing rule (learned the hard way)

n8n's Postgres node takes Query Parameters as a **comma-separated string**, and it does
two things that break naive use:

- it splits on commas, so any value containing a comma shatters the list; and
- it **drops empty values**, so a null sent as `''` disappears and every later parameter
  shifts left.

The second is not in the documentation and is what actually broke WF-03 in testing: 13
parameters arrived as 9 and the sequence letter `C` landed in an integer column.

**Rule for every Postgres node in this project:** if a statement takes more than one
parameter, or any parameter can be null or contain a comma, pass a single base64-encoded
JSON blob and unwrap it in SQL:

```sql
WITH e AS (SELECT convert_from(decode($1,'base64'),'UTF8')::jsonb AS j)
SELECT j->>'lead_id' FROM e
```

```javascript
return [{ json: { p: Buffer.from(JSON.stringify(payload)).toString('base64') } }];
```

Base64's alphabet contains no comma, and JSON null survives as a real SQL NULL. WF-03,
WF-05 `Log + Advance`, WF-01 `Upsert Lead + Log + Window` and WF-01 `Template-Fail
Suppress` all use this form. The remaining multi-parameter nodes (WF-04 `Store Token`,
WF-01 `Update Delivery Status`, WF-05 `Stop Sequence`) are safe only because every one of
their values is guaranteed non-empty and comma-free — `Stop Sequence` defaults `msg_key`
to `'none'` precisely to hold that invariant.

## Status — DEPLOYED AND CONNECTED

Both workflows now run against the funnel schema on Supabase project
`mzuzlfnkvsvrfmxdupvh` via the `Postgres account` credential (`6qn1m9qWuhRwzfF7`),
verified by execution 51 (n8n reads all 10 funnel tables) and execution 52 (WF-02
returns `{"status":"invalid"}` for a bad token, fails closed, no errors).

WF-03 cannot be exercised end to end until WF-04 exists to mint a real JWT.

### How the connection was finally established

Worth recording, because most of the elapsed time went here rather than into the
workflows. The failures came in this order, each one layer further down:

| Symptom | Cause |
|---|---|
| `relation "landing_tokens" does not exist` | credential pointed at a different application's database |
| `EAI_AGAIN Supabase Funnel` | the credential's *name* had been typed into the Host field |
| `ENETUNREACH 2406:...` | direct host `db.<ref>.supabase.co` is IPv6-only; the n8n server has no IPv6 route |
| `Port … 54326543` | `5432` and `6543` concatenated in the Port field |
| `Host not found` — *including with a literal IP* | edits were not persisting to the stored credential; only deleting and recreating it took effect |
| connected, `0 tables` | the Supabase project had been replaced; Part 1 needed re-applying to the new one |

Two lessons for later parts: an error naming a *hostname* is a credential-content
problem, while a literal IP producing a DNS error means the record isn't being saved at
all. And when a Supabase project is replaced, re-running `0001_part1_schema.sql` against
the new one is the whole recovery — it is idempotent by design.

## Superseded blocker (resolved)

Instance: `https://n8n.srv1944094.hstgr.cloud` (self-hosted), personal project
`akhinesh brandsure <akhinesh@brandsure.in>`.

| Workflow | ID | Webhook | Active |
|---|---|---|---|
| WF-02 Validate Token | `jTwnM4UOIICNWbnx` | `/webhook/validate-token` | yes |
| WF-03 Landing Event | `ubO7j7vD1lfzLvE5` | `/webhook/landing-event` | no |

Both had originally been imported into a *single* workflow as the uncorrected drafts,
and activated. WF-02 has been rewritten in place to hold only the validate-token path;
WF-03 was split out as its own workflow carrying the corrected event receiver.

### Blocker: the Postgres credential points at the wrong database

`supabase` (`vVtQF0102FRcoLp5`) is attached to both workflows but does **not** target the
funnel project. Proven by a live execution (id 35) with a deliberately invalid token:

```
NodeOperationError: relation "landing_tokens" does not exist
  node: Load Lead   credential: supabase (vVtQF0102FRcoLp5)
```

A read-only probe through that same credential identified the database it *is* on —
Postgres 17.6, 18 public tables:

```
alerts, appointments, audit, call_reviews, calls, kb_chunks,
kb_chunks_repair_backup_20260828, leads, message_log, orders, org_numbers,
orgs, password_resets, screen_time, serviceable_pincodes, shifts, staff, staff_orgs
```

**This matters beyond "wrong database".** That schema contains its own `leads` and
`message_log` tables — the same names Part 1 uses — belonging to a different, live
application (orders, staff, appointments, password_resets). Pointing funnel workflows at
it risks writing into another system's tables rather than failing cleanly.

Nothing has been written so far: WF-02 only reads, WF-03 is inactive, and WF-03's
statement opens with `INSERT INTO lead_events`, which does not exist there, so the whole
CTE chain aborts atomically. But WF-01's planned upsert targets `leads` and `message_log`
by name — build it only after the credential is corrected.

**Do not repoint `supabase` in place.** Create a separate credential for the funnel
(`db.vjnxhkhqckehckanzgcu.supabase.co`, or the pooler host on port 6543, user
`postgres.vjnxhkhqckehckanzgcu`, SSL require) and attach that. The existing credential is
in use by the KB and WhatsApp workflows on this instance.

The same execution confirms the JWT credential (`JWT Auth account`,
`4fLfz7cpCeIyhTNS`) works: it rejected the probe token with `Value is not a valid JWT
token` and set `error`, which is what the fail-closed check in `Assemble Response`
reads.

## What was wrong with the drafts

### 1. WF-03 would have failed on every single request — **blocking**

n8n's Postgres node passes query parameters as a **comma-separated string** (its own
type definition: *"Comma-separated list of the values you want to use as query
parameters… reference them in your query as $1, $2, $3…"*). The draft passed the event
payload as parameter `$4`:

```js
p4: JSON.stringify({ watched_seconds: ..., position_seconds: ..., route: ..., ts: ... })
```

That value **always contains commas** — three of them, for any event. n8n splits on
those commas, so 11 intended parameters arrive as 14 misaligned ones and everything
from `$4` onward lands in the wrong slot.

The fix: no parameter may contain a comma. The payload is no longer passed as a JSON
string at all — it is assembled inside the statement with `jsonb_build_object()` from
scalar parameters, and the Code node strips commas from every free-text value.

### 2. NULL parameters became empty strings — **blocking, and silently corrupting**

The same comma-string mechanism renders a JavaScript `null` as `''`. The draft sent
`null` for `$9` (`next_action_at`), `$10` (CAPI stage) and `$11` (sequence) on every
event that has no mapping — `terms_viewed`, `video5_unlocked`, `heartbeat`,
`expiry_page_shown`, `renew_requested`. Verified against the live database:

| What the draft does | Result |
|---|---|
| `''::timestamptz` | `ERROR: invalid input syntax for type timestamp with time zone: ""` |
| `'' IS NOT NULL` | **true** — so the CAPI guard passes on an empty value |
| `INSERT INTO capi_queue … stage = ''` | **accepted** — junk row queued to Meta |
| `UPDATE leads SET followup_sequence = ''` | **accepted** — scheduler state corrupted |

So the events that did not hard-error would have quietly written junk CAPI rows and an
empty `followup_sequence`, which WF-05 then can't match to any sequence — the lead
stops being followed up and nothing reports an error.

Fixed by sending `''` deliberately as the null sentinel and unwrapping it in SQL with
`NULLIF($n,'')`. Numeric parameters are never blank.

### 3. `/landing-event` had no authentication — **security**

The draft trusted `lead_id` from the request body on an open webhook. Lead ids are
sequential (`BS-00001`, `BS-00002`, …), so anyone could POST any event for any lead.
The spec's own smoke test is the exploit:

```bash
curl -X POST https://<n8n>/webhook/landing-event \
  -d '{"lead_id":"BS-00001","event":"page_opened","session_id":"test1"}'
```

Swap `page_opened` for `video5_unlocked` and the Partnership economics video opens.
That directly contradicts the master plan's QA gate — *"Video-5 lock cannot be bypassed
by URL param, devtools-visible flag, or scrubbing"* — and it lets anyone inflate or
poison every intent score and CAPI signal in the funnel.

Fixed two ways:

- WF-03 now verifies the same JWT the page already holds and takes `lead_id` and
  `route` **from the verified claims**, ignoring whatever the body says. A missing or
  bad token drops the event silently.
- `video5_unlocked` is no longer a client assertion. It is derived in SQL from the
  recorded `video_90` milestone on video 4.

**This is a contract change for the landing page:** every `/landing-event` POST must now
include the page's token as `token`. The page has it — it is in the URL it was opened
with.

**Residual risk, stated honestly:** JWT auth stops third parties. It does not stop the
lead themselves from replaying their own `video_90`. Closing that fully means validating
cumulative watch time server-side against the 30-second heartbeats in `lead_heartbeats`
(reject a `video_90` for a 9-minute video when fewer than ~18 heartbeats exist for that
session). Worth doing before the Partnership route carries real traffic; it is not in
the Part 2 spec, so it is not built here.

### 4. WF-02 could treat a failed JWT check as a pass — **security, lower severity**

`Verify JWT` runs with `onError: continueRegularOutput`, and the draft's only guard was
`if (v.error || rows.length === 0)`. That depends on the JWT node putting an `error` key
where the code expects it. If it ever doesn't, verification fails open.

In practice the database lookup limits the damage — the token string still has to exist
in `landing_tokens` — which is worth being explicit about: **the real security of this
link rests on token unguessability and DB state, not on the JWT signature.**

Fixed to fail closed by construction: the response is `valid` only when the JWT produced
a `lead_id` claim **and that claim matches the row loaded from the database**. A JWT node
that errored produces no claim, so it can never be mistaken for a successful verify.

### 5. CORS was wide open

Both webhooks shipped `allowedOrigins: "*"`. Set to `https://go.brandsure.in`. Note this
is browser-only protection and does nothing against a direct POST — item 3 is what
actually secures the endpoint.

## Verified behaviour

The corrected WF-03 statement was run against the live Part 1 schema across four
scenarios — a null-heavy `page_opened`, a replay of it, a `heartbeat`, and a
`video_90` on video 4:

| Assertion | Result |
|---|---|
| `score_intent` after `page_opened` ×2 | **2** — replay did not double-score |
| `video5_unlocked` after `video_90` (video 4) | **true**, in the same statement |
| `lead_events` rows | **2** — heartbeat correctly excluded, replay deduped |
| `lead_heartbeats` rows | **1** |
| `capi_queue` rows / empty-stage junk rows | **2 / 0** |
| `followup_sequence` / `followup_step` | **C / 0** |
| payload `watched_seconds` | **188**, built by `jsonb_build_object` |

Every optional field was sent empty in scenario 1 — the exact case that hard-errors on
the draft.

## Deploying

1. Create the three credentials from spec §0.1 (`Supabase Postgres`, `BrandSure JWT`,
   `WhatsApp Cloud API`). The Postgres credential wants the pooler host, port 6543,
   user `postgres.vjnxhkhqckehckanzgcu`, **SSL: require**.
2. Import both JSON files. Replace `REPLACE_PG` and `REPLACE_JWT` by selecting the
   credentials in each node.
3. Activate. URLs become `https://<n8n>/webhook/validate-token` and
   `https://<n8n>/webhook/landing-event`.
4. Smoke test — note the event call now needs a real token, so generate one with WF-04
   first:

```bash
# invalid token -> {"status":"invalid"}
curl -s -X POST https://<n8n>/webhook/validate-token \
  -H 'Content-Type: application/json' -d '{"token":"garbage"}'

# event: token is now REQUIRED and lead_id is ignored if sent
curl -s -X POST https://<n8n>/webhook/landing-event \
  -H 'Content-Type: application/json' \
  -d '{"token":"<jwt from WF-04>","event":"page_opened","session_id":"test1"}'
```

Then in Supabase: `select score_intent, followup_sequence, next_action_at from leads
where lead_id='<id>';` → `2`, `C`, ≈now+3h. Repeat the same POST → still one event row,
score still 2.

## Note on n8n projects

Team projects are **not licensed** on this n8n instance (`teamProjectsEnabled: false`),
so these workflows land in the personal project. Use the `WF-0x` name prefix to keep
them grouped until team projects are available.
