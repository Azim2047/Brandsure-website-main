-- ============================================================
-- BrandSure WhatsApp AI Funnel — PART 1: Data Foundation
-- Postgres / Supabase migration. Idempotent: safe to re-run.
-- Everything else (n8n, AI, landing page) reads/writes these tables.
--
-- Apply:  Supabase → SQL Editor → paste this file → Run
-- Verify: then run verify/part1_acceptance.sql
-- ============================================================

-- ------------------------------------------------------------
-- 1. COMMERCIAL FACTS — the single source of truth for every
--    number the AI or the landing page is allowed to state.
--    NOTHING numeric is ever hard-coded anywhere else.
-- ------------------------------------------------------------
create table if not exists commercial_facts (
  key         text primary key,
  value       text not null,
  notes       text,
  updated_at  timestamptz not null default now()
);

insert into commercial_facts (key, value, notes) values
  ('standard_fee',                 '₹45,000 / month',                                   'Standard package monthly fee'),
  ('expert_fee',                   '₹75,000 / month',                                   'Expert package monthly fee'),
  ('partnership_share',            '20% of total sales inclusive of GST',               'Partnership revenue share'),
  ('partnership_term',             '36 months',                                         'Partnership commitment period'),
  ('partnership_setup_fee',        '₹1,20,000 + taxes',                                 'One-time setup fee'),
  ('partnership_deposit',          '₹3,00,000 refundable security deposit',             'Refundable deposit'),
  ('min_period_std_exp',           '3 months',                                          'Minimum period, Standard & Expert'),
  ('expert_capacity',              'up to 300 enquiries / month',                       'Expert handling capacity'),
  ('ads_payer_std_exp',            'Client pays advertising spend separately',          'Ad spend responsibility, Standard & Expert'),
  ('ads_payer_partnership',        'BrandSure funds the approved advertising spend',    'Ad spend responsibility, Partnership'),
  ('recommended_adspend_standard', '₹50,000 – ₹2,00,000 / month',                       'Recommended client ad budget, Standard'),
  ('governing_document',           'The signed agreement is the final source of all commercial terms', 'Legal framing line')
on conflict (key) do update set value = excluded.value, notes = excluded.notes, updated_at = now();

-- ------------------------------------------------------------
-- 2. LEADS — one row per phone number, forever. The AI's memory.
--
--    NOTE: the sequence MUST exist before the table, because the
--    lead_id default resolves 'leads_seq'::regclass at parse time.
-- ------------------------------------------------------------
create sequence if not exists leads_seq;

create table if not exists leads (
  lead_id             text primary key default ('BS-' || to_char(nextval('leads_seq'), 'FM00000')),
  phone               text not null unique,           -- E.164, e.g. +9198xxxxxx
  wa_name             text,                            -- WhatsApp profile name
  first_name          text,
  business_name       text,
  city                text,
  state               text,
  is_metro            boolean,
  is_serviceable      boolean,
  business_type       text check (business_type in
                        ('manufacturer_showroom','manufacturer','retailer','planning','other')),
  sales_band          text,                            -- e.g. '15-35L', 'prefer_private'
  enquiry_band        text,                            -- e.g. '101-200'
  enquiry_handler     text,                            -- who calls/follows up today
  main_problem        text,
  is_manufacturer     boolean,
  has_display         boolean,
  decision_authority  text,                            -- collected post-engagement
  start_timeline      text,                            -- collected post-engagement
  language            text not null default 'en' check (language in ('en','ml','kn','ta','hi')),

  -- routing & state machine
  route               text not null default 'UNSET' check (route in
                        ('UNSET','STANDARD','EXPERT','PARTNERSHIP','NURTURE','DISQUALIFIED')),
  track               text not null default 'NONE' check (track in ('NONE','FAST','APPLICATION')),
  conv_state          text not null default 'NEW',     -- current state-machine state
  diagnosis_text      text,                            -- AI-generated, rendered on landing page
  diagnosis_confirmed boolean not null default false,

  -- scoring (recalculated by Lead Scoring Engine)
  score_fit           int not null default 0,
  score_need          int not null default 0,
  score_intent        int not null default 0,
  score_authority     int not null default 0,
  score_total         int generated always as
                        (score_fit + score_need + score_intent + score_authority) stored,
  hard_conditions_ok  boolean,                         -- Partnership gates; null = not evaluated

  -- scheduler & window
  last_inbound_at     timestamptz,                     -- drives the 24h window calc
  next_action_at      timestamptz,                     -- ONE pending action per lead
  followup_sequence   text,                            -- 'A'..'F' or null
  followup_step       int not null default 0,

  -- landing state
  renewals_left       int not null default 1,
  video5_unlocked     boolean not null default false,
  resume_position     int not null default 0,          -- seconds

  -- control flags
  ai_paused           boolean not null default false,  -- human takeover
  opted_out           boolean not null default false,
  handoff             boolean not null default false,
  handoff_at          timestamptz,
  disqualified_reason text,

  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now()
);

create index if not exists idx_leads_next_action on leads (next_action_at)
  where next_action_at is not null and opted_out = false and ai_paused = false;
create index if not exists idx_leads_route on leads (route, track);

-- Convenience view: is the free-form 24h window open right now?
create or replace view v_lead_window as
select lead_id, phone,
       (last_inbound_at is not null and last_inbound_at > now() - interval '24 hours') as window_open,
       last_inbound_at
from leads;

-- A view runs with its OWNER's privileges by default, which would let the
-- anon key read every lead's phone number straight through the view even
-- though RLS is on the table. security_invoker makes RLS apply to the
-- caller; the revokes below are the belt-and-braces second lock.
do $$
begin
  if current_setting('server_version_num')::int >= 150000 then
    execute 'alter view v_lead_window set (security_invoker = on)';
  end if;
end $$;

do $$
declare r text;
begin
  foreach r in array array['anon','authenticated'] loop
    if exists (select 1 from pg_roles where rolname = r) then
      execute format('revoke all on v_lead_window from %I', r);
    end if;
  end loop;
end $$;

-- ------------------------------------------------------------
-- 3. MESSAGE LOG — every message in and out. Transcript source,
--    template delivery status, window audit trail.
-- ------------------------------------------------------------
create table if not exists message_log (
  id              bigserial primary key,
  lead_id         text not null references leads(lead_id),
  direction       text not null check (direction in ('in','out')),
  channel         text not null default 'whatsapp',
  body            text,                                -- text content or transcription
  media_type      text,                                -- text|voice|image|document|interactive
  is_template     boolean not null default false,
  template_name   text,
  wa_message_id   text,
  delivery_status text,                                -- sent|delivered|read|failed|capped
  meta            jsonb not null default '{}',         -- buttons shown, intent, state, etc.
  ts              timestamptz not null default now()
);
create index if not exists idx_msglog_lead on message_log (lead_id, ts desc);
create index if not exists idx_msglog_wamid on message_log (wa_message_id);

-- ------------------------------------------------------------
-- 4. LEAD EVENTS — landing-page + system events (the 11-event spec),
--    plus objections and internal flags. Append-only.
-- ------------------------------------------------------------
create table if not exists lead_events (
  id          bigserial primary key,
  lead_id     text not null references leads(lead_id),
  source      text not null check (source in ('landing','whatsapp','system')),
  event       text not null,        -- page_opened|video_started|video_25|video_50|video_75|
                                    -- video_90|video_completed|video5_unlocked|terms_viewed|
                                    -- cta_clicked|question_clicked|page_resumed|heartbeat|
                                    -- expiry_page_shown|renew_requested|objection|human_alert|...
  video       int,                  -- 1, 4, 5 or null
  payload     jsonb not null default '{}',
  session_id  text,
  ts          timestamptz not null default now()
);
-- Server-side dedupe applies ONLY to one-time funnel milestones.
-- Repeatable events (objection, page_resumed, question_clicked,
-- renew_requested, expiry_page_shown, human_alert) insert freely.
create unique index if not exists uq_milestone_event
  on lead_events (lead_id, event, coalesce(video, 0))
  where event in ('page_opened','video_started','video_25','video_50',
                  'video_75','video_90','video_completed','video5_unlocked',
                  'terms_viewed','cta_clicked');
-- heartbeats must not collide with the dedupe constraint:
create table if not exists lead_heartbeats (
  lead_id    text not null references leads(lead_id),
  session_id text not null,
  ts         timestamptz not null default now()
);
create index if not exists idx_events_lead on lead_events (lead_id, ts desc);
create index if not exists idx_heartbeats_lead on lead_heartbeats (lead_id, ts desc);

-- ------------------------------------------------------------
-- 5. ACKNOWLEDGEMENTS — each commercial acceptance, individually.
--    Video completion is NEVER inserted here.
-- ------------------------------------------------------------
create table if not exists acknowledgements (
  id              bigserial primary key,
  lead_id         text not null references leads(lead_id),
  ack_key         text not null,   -- fee|ad_spend|min_period|scope|share_20|term_36|
                                   -- setup_fee|deposit|showroom_closes|data_sharing|all_dms_attend
  route           text not null,
  acknowledged_at timestamptz not null default now(),
  wa_message_id   text,            -- proof: the message where they confirmed
  unique (lead_id, ack_key)
);

-- ------------------------------------------------------------
-- 6. LANDING TOKENS — signed-link registry (n8n also signs a JWT;
--    this table is the revocation + renewal ledger).
-- ------------------------------------------------------------
create table if not exists landing_tokens (
  token        text primary key,     -- the signed token string (or its hash)
  lead_id      text not null references leads(lead_id),
  route        text not null,
  created_at   timestamptz not null default now(),
  expires_at   timestamptz not null,
  renewed_from text references landing_tokens(token),
  revoked      boolean not null default false
);
create index if not exists idx_tokens_lead on landing_tokens (lead_id);

-- ------------------------------------------------------------
-- 7. FOLLOWUP TEMPLATES REGISTRY — every out-of-window template,
--    its Meta approval status and category. The scheduler refuses
--    to send anything not 'approved' here.
-- ------------------------------------------------------------
create table if not exists wa_templates (
  template_name  text primary key,
  language       text not null default 'en',
  category       text not null check (category in ('utility','marketing','authentication')),
  body_preview   text,
  meta_status    text not null default 'draft'
                   check (meta_status in ('draft','submitted','approved','rejected','paused')),
  used_by        text,              -- which sequence/step uses it, e.g. 'B3','C3','E3'
  updated_at     timestamptz not null default now()
);

-- ------------------------------------------------------------
-- 8. HANDOFFS — the CRM opportunity snapshot at handoff time.
-- ------------------------------------------------------------
create table if not exists handoffs (
  id             bigserial primary key,
  lead_id        text not null references leads(lead_id),
  route          text not null,
  summary        jsonb not null,    -- the full internal handoff summary block
  meeting_at     timestamptz,
  meeting_status text default 'pending'
                   check (meeting_status in ('pending','booked','attended','missed','rebooked','won','lost')),
  created_at     timestamptz not null default now()
);

-- ------------------------------------------------------------
-- 9. CAPI QUEUE — funnel-stage events to push back to Meta.
--    n8n drains this; row deleted/flagged on success.
-- ------------------------------------------------------------
create table if not exists capi_queue (
  id          bigserial primary key,
  lead_id     text not null references leads(lead_id),
  stage       text not null,       -- conversation_started|qualification_completed|route_qualified|
                                   -- page_opened|video_watched|commercials_acknowledged|
                                   -- call_booked|call_attended|client_won
  event_time  timestamptz not null default now(),
  sent        boolean not null default false,
  sent_at     timestamptz,
  response    jsonb
);
create index if not exists idx_capi_unsent on capi_queue (sent) where sent = false;

-- ------------------------------------------------------------
-- 10. updated_at triggers
-- ------------------------------------------------------------
create or replace function touch_updated_at() returns trigger as $$
begin new.updated_at = now(); return new; end;
$$ language plpgsql;

drop trigger if exists trg_leads_touch on leads;
create trigger trg_leads_touch before update on leads
  for each row execute function touch_updated_at();

-- commercial_facts and wa_templates also carry updated_at; keep them honest
-- so "when did this price last change?" is always answerable.
drop trigger if exists trg_facts_touch on commercial_facts;
create trigger trg_facts_touch before update on commercial_facts
  for each row execute function touch_updated_at();

drop trigger if exists trg_templates_touch on wa_templates;
create trigger trg_templates_touch before update on wa_templates
  for each row execute function touch_updated_at();

-- ============================================================
-- Row Level Security: enable and lock down. n8n uses the
-- service_role key, which bypasses RLS. The landing page never
-- talks to the DB directly — only through n8n webhooks.
-- ============================================================
alter table commercial_facts enable row level security;
alter table leads            enable row level security;
alter table message_log      enable row level security;
alter table lead_events      enable row level security;
alter table lead_heartbeats  enable row level security;
alter table acknowledgements enable row level security;
alter table landing_tokens   enable row level security;
alter table wa_templates     enable row level security;
alter table handoffs         enable row level security;
alter table capi_queue       enable row level security;
-- (no anon policies created on purpose: anon/authenticated get nothing)
