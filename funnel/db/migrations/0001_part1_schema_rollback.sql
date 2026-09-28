-- ============================================================
-- PART 1 rollback. DESTRUCTIVE: drops every Part 1 object and
-- all lead data with it. Only for rebuilding a dev/staging
-- project from scratch. Never run against production.
-- ============================================================

drop view if exists v_lead_window;

drop table if exists capi_queue;
drop table if exists handoffs;
drop table if exists wa_templates;
drop table if exists landing_tokens;
drop table if exists acknowledgements;
drop table if exists lead_heartbeats;
drop table if exists lead_events;
drop table if exists message_log;
drop table if exists leads;
drop sequence if exists leads_seq;
drop table if exists commercial_facts;

drop function if exists touch_updated_at();
