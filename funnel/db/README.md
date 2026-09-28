# PART 1 — Data Foundation

The database layer every other part of the BrandSure WhatsApp AI funnel depends on.
10 tables + 1 view in Supabase/Postgres.

```
funnel/db/
├── migrations/
│   ├── 0001_part1_schema.sql           ← apply this
│   └── 0001_part1_schema_rollback.sql  ← destructive, dev/staging only
└── verify/
    └── part1_acceptance.sql            ← run this after applying
```

## Apply

1. Supabase → SQL Editor → paste `migrations/0001_part1_schema.sql` → Run.
   Idempotent — safe to re-run. Expect three `trigger ... does not exist, skipping`
   NOTICEs on a first run; they are the `drop trigger if exists` guards.
2. Paste `verify/part1_acceptance.sql` → Run. Read the result table.
   **`failures` must be 0 across 17 checks.** The script creates a test lead on
   `+919800000001`, exercises every guarantee, and deletes what it created.
3. Copy the project **URL** and **service_role key** into n8n credentials for Part 2.
   The service_role key goes nowhere else — never into the landing page, never into
   a browser, never into this repo.

## Status

Applied and verified against Postgres 16 with Supabase's role model emulated
(`anon` / `authenticated` / `service_role` with Supabase's default grants):
migration runs clean, is idempotent across repeated runs, rollback drops to zero
objects, rebuild restores all 11 objects, and all 17 acceptance checks pass.

## Changes made to the draft `part1_schema.sql`

### 1. Sequence created before the table — **was blocking**

The draft declared `leads.lead_id` with a default of
`('BS-' || to_char(nextval('leads_seq'), 'FM00000'))` but created `leads_seq`
*after* the table. Postgres resolves `'leads_seq'::regclass` at parse time, so the
draft fails at the `leads` table:

```
ERROR: relation "leads_seq" does not exist
```

Everything from `leads` onward — 8 tables, the view, the triggers, all the RLS —
never got created. `create sequence` now precedes `create table leads`.

### 2. The view bypassed RLS — **was a live data leak**

`v_lead_window` selects `lead_id, phone` from `leads`. A Postgres view runs with its
**owner's** privileges by default, so RLS on `leads` does not apply to reads through
it. With Supabase's default grants, the public `anon` key could read every lead's
phone number through the view — while the brief's own acceptance check
(`select * from leads` as anon → 0 rows) passed and hid it. Demonstrated on the
draft definition:

```
 variant                 | rows_visible | leaked_phone
-------------------------+--------------+---------------
 ORIGINAL view (as anon) |            1 | +919800000099
```

Fixed with `security_invoker = on` (PG 15+, guarded by a version check) plus explicit
`revoke all ... from anon, authenticated`. Two independent locks, because this one is
a phone-number list. Acceptance check 17 now covers it.

### 3. Minor

- `updated_at` triggers added on `commercial_facts` and `wa_templates`. Both carried
  an `updated_at` column that nothing maintained, so "when did this price last change?"
  was unanswerable — the whole point of `commercial_facts` being the single source of truth.
- `idx_heartbeats_lead` on `lead_heartbeats (lead_id, ts desc)`. Heartbeats arrive every
  30s per open session and are read per-lead for session-length; without an index that
  becomes the funnel's first slow query.
- `landing_tokens` column alignment (cosmetic only).

Nothing else was changed. Every table, column, constraint, check and comment from the
draft is intact.

## Notes for Part 2 (n8n)

**Milestone inserts must use the matching inference clause.** `uq_milestone_event` is a
*partial* unique index, so a plain `on conflict do nothing` will not match it and the
insert will raise. This exact form is verified by acceptance check 8:

```sql
insert into lead_events (lead_id, source, event, video, payload, session_id)
values ($1, 'landing', $2, $3, $4, $5)
on conflict (lead_id, event, coalesce(video, 0))
  where event in ('page_opened','video_started','video_25','video_50',
                  'video_75','video_90','video_completed','video5_unlocked',
                  'terms_viewed','cta_clicked')
do nothing;
```

Repeatable events (`objection`, `page_resumed`, `question_clicked`, `renew_requested`,
`expiry_page_shown`, `human_alert`) are outside the index and insert freely — do **not**
put an `on conflict` clause on those. Heartbeats go to `lead_heartbeats`, never to
`lead_events`.

**Dedupe is per lead + event + video.** `video_75` on video 4 and `video_75` on video 5
are different rows. Verified by check 6.

**`updated_at` is transaction time**, not statement time (`touch_updated_at()` uses
`now()`). Several rows updated in one n8n transaction share a timestamp. This is
intended; don't use `updated_at` to order events within a transaction — that is what
`lead_events.ts` is for.

**The landing page never touches the database.** Only n8n holds the service_role key.
RLS is on for all 10 tables with zero anon policies, so a leaked anon key exposes
nothing — see checks 16 and 17.

**Template safety is structural.** The Part 2 send step must join
`wa_templates` on `meta_status = 'approved'`; an unapproved template then physically
cannot be sent (check 15).

## Acceptance checks covered

| # | Check |
|---|---|
| 1 | `commercial_facts` seeded with 12 rows |
| 2 | New lead gets `BS-#####`, score 0, state `NEW` |
| 3 | `score_total` generated column cannot drift from its components |
| 4 | Duplicate phone rejected — one row per phone, forever |
| 5 | Milestone event inserted twice is rejected (no double-counted intent) |
| 6 | The same milestone on a different video is a distinct event |
| 7 | Repeatable events (`objection`) insert freely |
| 8 | The exact idempotent insert n8n must use for milestones works |
| 9 | Heartbeats insert freely, never collide with the dedupe |
| 10 | Acknowledgements are one-per-key per lead |
| 11–13 | `v_lead_window`: closed with no inbound → open after inbound → closed after 24h |
| 14 | `updated_at` trigger overwrites any written value |
| 15 | An unapproved template is not joinable as sendable |
| 16 | `anon` reads zero rows from `leads` |
| 17 | `anon` reads zero rows from `v_lead_window` |
