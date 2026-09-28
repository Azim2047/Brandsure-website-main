-- ============================================================
-- PART 3 — message copy + Meta template registry
-- Postgres / Supabase migration. Idempotent. Run after 0001.
--
-- Applied to project mzuzlfnkvsvrfmxdupvh as migration
-- part3_copy_and_template_registry.
--
-- CHANGED FROM THE SUPPLIED part3_copy_seed.sql:
--   * added an updated_at trigger on message_copy, matching the other
--     tables that carry the column
--   * the accompanying WF-05 join change is NOT the position()/substring
--     test the submissions doc specifies - see the note at the foot of
--     this file
-- ============================================================

-- Fix wa_templates key: one Meta template name spans two languages
alter table wa_templates drop constraint if exists wa_templates_pkey;
alter table wa_templates add primary key (template_name, language);

-- ------------------------------------------------------------
-- message_copy: the in-window bodies WF-05 sends, editable
-- without touching workflows. {{...}} merges resolved by n8n.
-- ------------------------------------------------------------
create table if not exists message_copy (
  key       text not null,
  language  text not null default 'en',
  body      text not null,
  buttons   jsonb not null default '[]',
  updated_at timestamptz not null default now(),
  primary key (key, language)
);
alter table message_copy enable row level security;

drop trigger if exists trg_copy_touch on message_copy;
create trigger trg_copy_touch before update on message_copy
  for each row execute function touch_updated_at();

insert into message_copy (key, language, body, buttons) values
-- Sequence A
('A1','en',$c$Your BrandSure assessment is saved, {{name}} — we stopped at the question about {{pending_topic}}. Continue?$c$, '["Continue","Later","Stop"]'),
('A1','ml',$c${{name}}, നിങ്ങളുടെ അസസ്മെന്റ് സേവ് ചെയ്തിട്ടുണ്ട് — {{pending_topic}} ചോദ്യത്തിൽ നിർത്തി. തുടരണോ?$c$, '["തുടരുക","പിന്നീട്","നിർത്തുക"]'),
('A2','en',$c$I can complete the assessment once I know {{missing}} — it helps me avoid recommending an unsuitable package. Continue?$c$, '["Continue","Close it"]'),
('A2','ml',$c${{missing}} അറിഞ്ഞാൽ അസസ്മെന്റ് പൂർത്തിയാക്കാം — അനുയോജ്യമല്ലാത്ത പാക്കേജ് നിർദ്ദേശിക്കാതിരിക്കാൻ ഇത് സഹായിക്കും. തുടരണോ?$c$, '["തുടരുക","ക്ലോസ് ചെയ്യൂ"]'),
('A3','en',$c$Should I keep your assessment open or close it for now?$c$, '["Keep open","Close for now"]'),
('A3','ml',$c$നിങ്ങളുടെ അസസ്മെന്റ് തുറന്നിരിക്കട്ടെ, അതോ തൽക്കാലം ക്ലോസ് ചെയ്യണോ?$c$, '["തുറന്നിരിക്കട്ടെ","ക്ലോസ് ചെയ്യൂ"]'),
-- Sequence B
('B1','en',$c$Quick check, {{name}} — were you able to open the private {{package}} explanation?$c$, '["Open it now","Link issue","Later"]'),
('B1','ml',$c${{name}}, ഒരു ചെറിയ പരിശോധന — {{package}} സ്വകാര്യ വിശദീകരണം തുറക്കാൻ കഴിഞ്ഞോ?$c$, '["ഇപ്പോൾ തുറക്കാം","ലിങ്ക് പ്രശ്നം","പിന്നീട്"]'),
('B2','en',$c$I recommended {{package}} because you mentioned {{main_problem}} — the explanation shows how exactly that gets handled. Your private access: {{link}}$c$, '["View now","Later"]'),
('B2','ml',$c${{main_problem}} എന്ന് പറഞ്ഞതിനാലാണ് {{package}} നിർദ്ദേശിച്ചത് — അത് എങ്ങനെ പരിഹരിക്കുന്നു എന്ന് വിശദീകരണം കാണിക്കും. നിങ്ങളുടെ സ്വകാര്യ ആക്സസ്: {{link}}$c$, '["ഇപ്പോൾ കാണാം","പിന്നീട്"]'),
-- Sequence C
('C1','en',$c$You reached the section on {{last_section}}; the remaining part covers {{next_section}}. Continue from where you stopped: {{resume_link}}$c$, '["Resume","Later"]'),
('C1','ml',$c${{last_section}} വരെ എത്തി; ബാക്കി ഭാഗം {{next_section}} ആണ്. നിർത്തിയിടത്ത് നിന്ന് തുടരാം: {{resume_link}}$c$, '["തുടരുക","പിന്നീട്"]'),
('C2','en',$c$Is there a particular reason you stopped watching?$c$, '["Need time","Investment","Not sure it fits"]'),
('C2','ml',$c$കാണുന്നത് നിർത്താൻ പ്രത്യേക കാരണം ഉണ്ടോ?$c$, '["സമയം വേണം","നിക്ഷേപം","ഫിറ്റ് ഉറപ്പില്ല"]'),
-- Sequence D
('D1','en',$c$Based on what you watched, which is closest to your response?$c$, '["Looks suitable","Clarification","Not now"]'),
('D1','ml',$c$കണ്ടതിന്റെ അടിസ്ഥാനത്തിൽ, നിങ്ങളുടെ പ്രതികരണത്തോട് ഏറ്റവും അടുത്തത് ഏതാണ്?$c$, '["അനുയോജ്യമാണ്","വ്യക്തത വേണം","ഇപ്പോൾ വേണ്ട"]'),
-- Sequence E
('E1','en',$c$Your application is saved — we still need {{missing}} before deciding whether a final meeting is useful. Continue: {{flow_link}}$c$, '["Continue","Later"]'),
('E1','ml',$c$അപേക്ഷ സേവ് ചെയ്തിട്ടുണ്ട് — അന്തിമ മീറ്റിംഗ് ഉപയോഗപ്രദമാണോ എന്ന് തീരുമാനിക്കാൻ {{missing}} കൂടി വേണം. തുടരാം: {{flow_link}}$c$, '["തുടരുക","പിന്നീട്"]'),
('E2','en',$c$The details requested are used only to verify whether the package can realistically work for your business. Continue here: {{flow_link}}$c$, '["Continue","Later"]'),
('E2','ml',$c$ചോദിച്ച വിവരങ്ങൾ പാക്കേജ് നിങ്ങളുടെ ബിസിനസിന് യഥാർത്ഥത്തിൽ ചേരുമോ എന്ന് പരിശോധിക്കാൻ മാത്രമാണ് ഉപയോഗിക്കുന്നത്. ഇവിടെ തുടരാം: {{flow_link}}$c$, '["തുടരുക","പിന്നീട്"]'),
-- Core fixed messages (used by WF-01 branches / Part 4 brain)
('ENTRY','en',$c$Hi {{name}}, welcome to BrandSure. I am BrandSure's furniture-business assessment assistant — in about two minutes I can check which of our growth options fits your business, and tell you honestly if none of them do. Which best describes your business?$c$, '["LIST:Manufacturer + showroom","LIST:Furniture manufacturer","LIST:Retailer / showroom","LIST:Planning to start","LIST:Other"]'),
('ENTRY','ml',$c$ഹായ് {{name}}, ബ്രാൻഡ്ഷുറിലേക്ക് സ്വാഗതം. ഞാൻ ബ്രാൻഡ്ഷുറിന്റെ ഫർണിച്ചർ-ബിസിനസ് അസസ്മെന്റ് അസിസ്റ്റന്റാണ് — ഏകദേശം രണ്ട് മിനിറ്റിൽ നിങ്ങളുടെ ബിസിനസിന് ഏത് ഗ്രോത്ത് ഓപ്ഷൻ അനുയോജ്യമെന്ന് പരിശോധിക്കാം; ഒന്നും ചേരുന്നില്ലെങ്കിൽ അതും തുറന്നു പറയാം. നിങ്ങളുടെ ബിസിനസ് ഏതു തരമാണ്?$c$, '["LIST:നിർമ്മാതാവ് + ഷോറൂം","LIST:ഫർണിച്ചർ നിർമ്മാതാവ്","LIST:റീട്ടെയ്ലർ / ഷോറൂം","LIST:തുടങ്ങാൻ പദ്ധതിയുണ്ട്","LIST:മറ്റുള്ളവ"]'),
('STOP_CONFIRM','en',$c$Understood — you will not receive further messages from us. If you ever want to restart the assessment, just message Hi.$c$, '[]'),
('STOP_CONFIRM','ml',$c$മനസ്സിലായി — ഇനി ഞങ്ങളിൽ നിന്ന് സന്ദേശങ്ങൾ ലഭിക്കില്ല. അസസ്മെന്റ് വീണ്ടും തുടങ്ങണമെങ്കിൽ Hi എന്ന് മെസേജ് ചെയ്താൽ മതി.$c$, '[]'),
('ELIG_NURTURE','en',$c$Our current packages are designed for operating furniture businesses with an existing showroom, customers or manufacturing. You may be too early right now, and I do not want to push you into something unsuitable. When you have a running showroom or production, message us again — we will pick up from here.$c$, '[]'),
('ELIG_NURTURE','ml',$c$ഞങ്ങളുടെ നിലവിലെ പാക്കേജുകൾ ഷോറൂമോ ഉപഭോക്താക്കളോ നിർമ്മാണമോ ഉള്ള, പ്രവർത്തിക്കുന്ന ഫർണിച്ചർ ബിസിനസുകൾക്കായാണ്. നിങ്ങൾക്ക് ഇപ്പോൾ അല്പം നേരത്തെയായിരിക്കാം; അനുയോജ്യമല്ലാത്ത ഒന്നിലേക്ക് തള്ളിവിടാൻ ഞാൻ ആഗ്രഹിക്കുന്നില്ല. ഷോറൂമോ പ്രൊഡക്ഷനോ തുടങ്ങുമ്പോൾ വീണ്ടും മെസേജ് ചെയ്യൂ — ഇവിടെ നിന്ന് തുടരാം.$c$, '[]'),
('ELIG_DISQUALIFIED','en',$c$This WhatsApp channel is only for furniture-business growth enquiries. We do not handle job applications or labour enquiries through this number. Wishing you the best.$c$, '[]'),
('ELIG_DISQUALIFIED','ml',$c$ഈ WhatsApp ചാനൽ ഫർണിച്ചർ-ബിസിനസ് ഗ്രോത്ത് അന്വേഷണങ്ങൾക്ക് മാത്രമാണ്. ജോലി അപേക്ഷകളോ തൊഴിൽ അന്വേഷണങ്ങളോ ഈ നമ്പറിലൂടെ കൈകാര്യം ചെയ്യുന്നില്ല. എല്ലാ ആശംസകളും.$c$, '[]'),
('HANDOFF','en',$c$Your assessment is complete, {{name}}. Based on your answers and what you reviewed, {{package}} appears suitable for final evaluation — I am handing this to the BrandSure team. The meeting will focus on your numbers, operational fit and the final decision, not repeat the explanation.$c$, '[]'),
('HANDOFF','ml',$c${{name}}, നിങ്ങളുടെ അസസ്മെന്റ് പൂർത്തിയായി. ഉത്തരങ്ങളും നിങ്ങൾ കണ്ട വിവരങ്ങളും അനുസരിച്ച് {{package}} അന്തിമ വിലയിരുത്തലിന് അനുയോജ്യമാണ് — ഇത് ബ്രാൻഡ്ഷുർ ടീമിന് കൈമാറുന്നു. മീറ്റിംഗ് നിങ്ങളുടെ കണക്കുകളും പ്രവർത്തന ഫിറ്റും അന്തിമ തീരുമാനവുമാണ് ചർച്ച ചെയ്യുക; വിശദീകരണം ആവർത്തിക്കില്ല.$c$, '[]')
on conflict (key, language) do update set body = excluded.body, buttons = excluded.buttons, updated_at = now();

-- ------------------------------------------------------------
-- Template registry: all draft until Meta approves each
-- name+language. WF-05 physically cannot send a non-approved row.
-- ------------------------------------------------------------
insert into wa_templates (template_name, language, category, body_preview, meta_status, used_by) values
('bs_assessment_saved','en','utility','Your BrandSure business assessment is saved, {{1}}…','draft','A2,A3'),
('bs_assessment_saved','ml','utility','{{1}}, നിങ്ങളുടെ ബ്രാൻഡ്ഷുർ ബിസിനസ് അസസ്മെന്റ്…','draft','A2,A3'),
('bs_access_reminder','en','utility','The private {{1}} explanation you requested is still active…','draft','B2,C2'),
('bs_access_reminder','ml','utility','നിങ്ങൾ അഭ്യർത്ഥിച്ച സ്വകാര്യ {{1}} വിശദീകരണം…','draft','B2,C2'),
('bs_access_expiry','en','utility','The private access you requested closes in about 4 hours…','draft','B3,C3,E3'),
('bs_access_expiry','ml','utility','സ്വകാര്യ ആക്സസ് ഏകദേശം 4 മണിക്കൂറിനുള്ളിൽ…','draft','B3,C3,E3'),
('bs_video_check','en','utility','You completed the {{1}} explanation you requested…','draft','D1'),
('bs_video_check','ml','utility','നിങ്ങൾ അഭ്യർത്ഥിച്ച {{1}} വിശദീകരണം പൂർത്തിയാക്കി…','draft','D1'),
('bs_application_pending','en','utility','Your BrandSure application is saved, {{1}}…','draft','E1,E2'),
('bs_application_pending','ml','utility','{{1}}, നിങ്ങളുടെ ബ്രാൻഡ്ഷുർ അപേക്ഷ സേവ്…','draft','E1,E2'),
('bs_meeting_confirmed','en','utility','{{1}}, your BrandSure meeting is confirmed for {{2}}…','draft','F1'),
('bs_meeting_confirmed','ml','utility','{{1}}, നിങ്ങളുടെ ബ്രാൻഡ്ഷുർ മീറ്റിംഗ് {{2}}-ന്…','draft','F1'),
('bs_meeting_reminder','en','utility','Reminder, {{1}}: your BrandSure meeting is at {{2}}…','draft','F2,F3'),
('bs_meeting_reminder','ml','utility','ഓർമ്മപ്പെടുത്തൽ, {{1}}: മീറ്റിംഗ് {{2}}-ന്…','draft','F2,F3'),
('bs_meeting_reminder_partner','en','utility','Reminder… every person involved in approving the Partnership attends…','draft','F2P,F3P'),
('bs_meeting_reminder_partner','ml','utility','ഓർമ്മപ്പെടുത്തൽ… പാർട്ണർഷിപ്പ് അംഗീകരിക്കുന്നതിൽ പങ്കുള്ള എല്ലാവരും…','draft','F2P,F3P'),
('bs_meeting_missed','en','utility','We missed you at your BrandSure meeting today, {{1}}…','draft','F4'),
('bs_meeting_missed','ml','utility','{{1}}, ഇന്നത്തെ ബ്രാൻഡ്ഷുർ മീറ്റിംഗിൽ…','draft','F4')
on conflict (template_name, language) do update
  set body_preview = excluded.body_preview, used_by = excluded.used_by, updated_at = now();


-- ============================================================
-- NOTE ON THE WF-05 TEMPLATE JOIN
--
-- The submissions doc asks for:
--   ON position(l.followup_sequence || (l.followup_step + 1)::text
--               in t.used_by) > 0
--
-- That is a substring test, and key 'F2' is a substring of used_by
-- 'F2P,F3P'. Verified against this data: key 'F2' matches FOUR rows
-- (bs_meeting_reminder and bs_meeting_reminder_partner, in both
-- languages) instead of two, so a LEFT JOIN hands the scheduler the same
-- lead twice and it sends two templates.
--
-- WF-05 uses exact list-element matching instead:
--   ON (l.followup_sequence || (l.followup_step + 1)::text)
--      = ANY (string_to_array(t.used_by, ','))
-- ============================================================
