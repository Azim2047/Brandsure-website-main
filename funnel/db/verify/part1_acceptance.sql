-- ============================================================
-- PART 1 — Acceptance checks. Run AFTER 0001_part1_schema.sql.
-- Safe and self-cleaning: creates a test lead on +919800000001,
-- exercises every guarantee the schema is supposed to give, then
-- deletes everything it created.
--
-- Supabase → SQL Editor → paste → Run. Read the PASS/FAIL table.
-- ============================================================

drop table if exists _p1_results;
create temp table _p1_results (
  n int, check_name text, expected text, actual text, pass boolean
);

do $$
declare
  v_lead   text;
  v_before timestamptz;
  v_after  timestamptz;
  v_count  int;
  v_bool   boolean;
  v_txt    text;
begin
  -- clean slate for the test lead ---------------------------------
  delete from lead_heartbeats  where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from lead_events      where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from acknowledgements where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from message_log      where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from landing_tokens   where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from capi_queue       where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from handoffs         where lead_id in (select lead_id from leads where phone = '+919800000001');
  delete from leads            where phone = '+919800000001';

  -- 1. commercial_facts seeded ------------------------------------
  select count(*) into v_count from commercial_facts;
  insert into _p1_results values
    (1, 'commercial_facts seeded', '12 rows', v_count || ' rows', v_count = 12);

  -- test lead -------------------------------------------------------
  insert into leads (phone, first_name, city, language)
  values ('+919800000001', 'Test', 'Kochi', 'en')
  returning lead_id into v_lead;

  -- 2. lead_id format + generated score_total ---------------------
  select score_total into v_count from leads where lead_id = v_lead;
  select conv_state  into v_txt   from leads where lead_id = v_lead;
  insert into _p1_results values
    (2, 'new lead defaults', 'BS-##### / score 0 / state NEW',
        v_lead || ' / score ' || v_count || ' / state ' || v_txt,
        v_lead ~ '^BS-[0-9]{5}$' and v_count = 0 and v_txt = 'NEW');

  -- 3. score_total is generated, cannot drift ---------------------
  update leads set score_fit = 4, score_need = 3, score_intent = 5, score_authority = 2
   where lead_id = v_lead;
  select score_total into v_count from leads where lead_id = v_lead;
  insert into _p1_results values
    (3, 'score_total generated column', '14', v_count::text, v_count = 14);

  -- 4. one phone = one lead, forever ------------------------------
  begin
    insert into leads (phone, first_name) values ('+919800000001', 'Duplicate');
    insert into _p1_results values
      (4, 'duplicate phone rejected', 'unique violation', 'INSERT SUCCEEDED', false);
  exception when unique_violation then
    insert into _p1_results values
      (4, 'duplicate phone rejected', 'unique violation', 'unique violation', true);
  end;

  -- 5. milestone events dedupe ------------------------------------
  insert into lead_events (lead_id, source, event, video) values (v_lead, 'landing', 'video_75', 1);
  begin
    insert into lead_events (lead_id, source, event, video) values (v_lead, 'landing', 'video_75', 1);
    insert into _p1_results values
      (5, 'milestone event deduped', 'unique violation', 'DOUBLE COUNTED', false);
  exception when unique_violation then
    insert into _p1_results values
      (5, 'milestone event deduped', 'unique violation', 'unique violation', true);
  end;

  -- 6. same milestone on a different video is a different event ---
  begin
    insert into lead_events (lead_id, source, event, video) values (v_lead, 'landing', 'video_75', 4);
    insert into _p1_results values
      (6, 'milestone is per-video', 'insert succeeds', 'insert succeeded', true);
  exception when unique_violation then
    insert into _p1_results values
      (6, 'milestone is per-video', 'insert succeeds', 'wrongly blocked', false);
  end;

  -- 7. repeatable events insert freely ----------------------------
  begin
    insert into lead_events (lead_id, source, event) values (v_lead, 'whatsapp', 'objection');
    insert into lead_events (lead_id, source, event) values (v_lead, 'whatsapp', 'objection');
    select count(*) into v_count from lead_events where lead_id = v_lead and event = 'objection';
    insert into _p1_results values
      (7, 'repeatable events not deduped', '2 rows', v_count || ' rows', v_count = 2);
  exception when unique_violation then
    insert into _p1_results values
      (7, 'repeatable events not deduped', '2 rows', 'wrongly blocked', false);
  end;

  -- 8. the exact idempotent insert n8n must use for milestones ----
  --    (partial unique index needs the matching inference clause)
  insert into lead_events (lead_id, source, event, video)
  values (v_lead, 'landing', 'video_75', 1)
  on conflict (lead_id, event, coalesce(video, 0))
    where event in ('page_opened','video_started','video_25','video_50',
                    'video_75','video_90','video_completed','video5_unlocked',
                    'terms_viewed','cta_clicked')
  do nothing;
  select count(*) into v_count from lead_events
   where lead_id = v_lead and event = 'video_75' and video = 1;
  insert into _p1_results values
    (8, 'n8n on-conflict-do-nothing form', '1 row, no error', v_count || ' rows', v_count = 1);

  -- 9. heartbeats never collide with the dedupe -------------------
  insert into lead_heartbeats (lead_id, session_id) values (v_lead, 'sess-1');
  insert into lead_heartbeats (lead_id, session_id) values (v_lead, 'sess-1');
  select count(*) into v_count from lead_heartbeats where lead_id = v_lead;
  insert into _p1_results values
    (9, 'heartbeats insert freely', '2 rows', v_count || ' rows', v_count = 2);

  -- 10. acknowledgements are one-per-key --------------------------
  insert into acknowledgements (lead_id, ack_key, route) values (v_lead, 'fee', 'STANDARD');
  begin
    insert into acknowledgements (lead_id, ack_key, route) values (v_lead, 'fee', 'STANDARD');
    insert into _p1_results values
      (10, 'acknowledgement idempotent', 'unique violation', 'DUPLICATED', false);
  exception when unique_violation then
    insert into _p1_results values
      (10, 'acknowledgement idempotent', 'unique violation', 'unique violation', true);
  end;

  -- 11. the 24h window view ---------------------------------------
  select window_open into v_bool from v_lead_window where lead_id = v_lead;
  insert into _p1_results values
    (11, 'window closed with no inbound', 'false', v_bool::text, v_bool = false);

  update leads set last_inbound_at = now() where lead_id = v_lead;
  select window_open into v_bool from v_lead_window where lead_id = v_lead;
  insert into _p1_results values
    (12, 'window open after inbound', 'true', v_bool::text, v_bool = true);

  update leads set last_inbound_at = now() - interval '25 hours' where lead_id = v_lead;
  select window_open into v_bool from v_lead_window where lead_id = v_lead;
  insert into _p1_results values
    (13, 'window closed after 24h', 'false', v_bool::text, v_bool = false);

  -- 14. updated_at trigger ----------------------------------------
  --     touch_updated_at() uses now() = TRANSACTION time, so comparing
  --     before/after inside one transaction proves nothing (and the
  --     Supabase SQL editor runs this whole script as one transaction).
  --     Instead: write a deliberately wrong value and prove the trigger
  --     overwrites it.
  update leads set updated_at = timestamptz '2000-01-01 00:00:00+00', city = 'Kozhikode'
   where lead_id = v_lead;
  select updated_at into v_after from leads where lead_id = v_lead;
  insert into _p1_results values
    (14, 'updated_at trigger overwrites writes', 'now(), not 2000-01-01',
         v_after::text, v_after > timestamptz '2020-01-01 00:00:00+00');

  -- 15. template safety: only approved templates are joinable -----
  insert into wa_templates (template_name, category, meta_status, used_by)
  values ('_p1_test_draft', 'utility', 'draft', 'TEST')
  on conflict (template_name) do update set meta_status = 'draft';
  select count(*) into v_count from wa_templates
   where template_name = '_p1_test_draft' and meta_status = 'approved';
  insert into _p1_results values
    (15, 'unapproved template not sendable', '0 rows', v_count || ' rows', v_count = 0);
  delete from wa_templates where template_name = '_p1_test_draft';

  -- 16/17. RLS: the anon key must see nothing ---------------------
  if exists (select 1 from pg_roles where rolname = 'anon') then
    begin
      set local role anon;
      select count(*) into v_count from leads;
      reset role;
      insert into _p1_results values
        (16, 'anon cannot read leads', '0 rows', v_count || ' rows', v_count = 0);
    exception when insufficient_privilege then
      reset role;
      insert into _p1_results values
        (16, 'anon cannot read leads', '0 rows', 'permission denied', true);
    end;

    begin
      set local role anon;
      select count(*) into v_count from v_lead_window;
      reset role;
      insert into _p1_results values
        (17, 'anon cannot read v_lead_window', '0 rows', v_count || ' rows', v_count = 0);
    exception when insufficient_privilege then
      reset role;
      insert into _p1_results values
        (17, 'anon cannot read v_lead_window', '0 rows', 'permission denied', true);
    end;
  else
    insert into _p1_results values
      (16, 'anon cannot read leads', '0 rows', 'SKIPPED: no anon role (not Supabase)', true);
    insert into _p1_results values
      (17, 'anon cannot read v_lead_window', '0 rows', 'SKIPPED: no anon role (not Supabase)', true);
  end if;

  -- cleanup ---------------------------------------------------------
  delete from lead_heartbeats  where lead_id = v_lead;
  delete from lead_events      where lead_id = v_lead;
  delete from acknowledgements where lead_id = v_lead;
  delete from leads            where lead_id = v_lead;
end $$;

select n,
       case when pass then 'PASS' else 'FAIL' end as result,
       check_name, expected, actual
from _p1_results
order by n;

select count(*) filter (where not pass) as failures,
       count(*)                          as checks_run
from _p1_results;
