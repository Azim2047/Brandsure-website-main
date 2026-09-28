// WF-05 Follow-Up Scheduler — n8n Workflow SDK source, as authored.
// Deployed as n8n workflow PNzy0h6VROmJHoZF.
//
// One cron. One pending action per lead. Window-aware. Template-approval-
// enforced. In-window copy comes from the message_copy table (Part 3) so
// marketing can edit it without touching the workflow.
//
// Replace REPLACE_PHONE_NUMBER_ID in both send nodes before activating.

import { workflow, node, trigger, expr, ifElse, switchCase } from '@n8n/workflow-sdk';

const every5 = trigger({
  type: 'n8n-nodes-base.scheduleTrigger',
  version: 1.4,
  config: {
    name: 'Every 5 Minutes',
    parameters: { rule: { interval: [{ field: 'minutes', minutesInterval: 5 }] } }
  }
});

const dueLeads = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Due Leads',
    parameters: {
      operation: 'executeQuery',
      query: `-- Exact list-element matching, NOT position()/substring: key 'F2' is a
-- substring of used_by 'F2P,F3P', so a substring test joins BOTH
-- bs_meeting_reminder and bs_meeting_reminder_partner to one lead and the
-- lead gets processed twice.
--
-- The approved-only join is the structural enforcement: a step needing a
-- template with no approved row yields NULL and Decide Message halts.
SELECT * FROM (
  SELECT DISTINCT ON (l.lead_id)
         l.lead_id, l.phone, l.first_name, l.language, l.route, l.track,
         l.conv_state, l.followup_sequence, l.followup_step, l.main_problem,
         l.enquiry_band, l.city, l.next_action_at,
         w.window_open,
         t.template_name, t.meta_status,
         c.body    AS copy_body,
         c.buttons AS copy_buttons,
         tok.token AS active_token
  FROM leads l
  JOIN v_lead_window w ON w.lead_id = l.lead_id
  LEFT JOIN wa_templates t
         ON (l.followup_sequence || (l.followup_step + 1)::text)
            = ANY (string_to_array(t.used_by, ','))
        AND t.language    = l.language
        AND t.meta_status = 'approved'
  LEFT JOIN message_copy c
         ON c.key      = l.followup_sequence || (l.followup_step + 1)::text
        AND c.language = l.language
  LEFT JOIN LATERAL (
    SELECT lt.token FROM landing_tokens lt
    WHERE lt.lead_id = l.lead_id AND lt.revoked = false AND lt.expires_at > now()
    ORDER BY lt.created_at DESC LIMIT 1
  ) tok ON true
  WHERE l.next_action_at IS NOT NULL
    AND l.next_action_at <= now()
    AND l.opted_out = false
    AND l.ai_paused = false
    AND l.handoff   = false
  ORDER BY l.lead_id, t.template_name
) s
ORDER BY s.next_action_at
LIMIT 25;`,
      options: {}
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const decide = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Decide Message',
    parameters: {
      jsCode: `// sequence -> step -> { gap hours until the NEXT step, or null to end }
// Cumulative timings match the Part 3 copy pack: A2 +6h, A3 +22h,
// B2 +20h, B3 +44h, C2 +20h, C3 +44h, E2 +20h, E3 +44h.
const SEQ = {
  A: [ {gap: 6,    key:'A1'}, {gap: 16, key:'A2'}, {gap: null, key:'A3'} ],
  B: [ {gap: 18.5, key:'B1'}, {gap: 24, key:'B2'}, {gap: null, key:'B3'} ],
  C: [ {gap: 17,   key:'C1'}, {gap: 24, key:'C2'}, {gap: null, key:'C3'} ],
  D: [ {gap: null, key:'D1'} ],
  E: [ {gap: 18,   key:'E1'}, {gap: 24, key:'E2'}, {gap: null, key:'E3'} ]
};

// Which merge fills each {{n}} of each Meta template, from the Part 3
// submissions doc. A template whose parameters cannot all be resolved is
// halted, not sent - Meta rejects a template with a missing variable anyway.
const TEMPLATE_PARAMS = {
  bs_assessment_saved:         ['name', 'pending_topic'],
  bs_access_reminder:          ['package', 'name'],
  bs_access_expiry:            ['package', 'name'],
  bs_video_check:              ['package', 'name'],
  bs_application_pending:      ['name', 'missing'],
  bs_meeting_confirmed:        ['name', 'meeting_time'],
  bs_meeting_reminder:         ['name', 'meeting_time'],
  bs_meeting_reminder_partner: ['name', 'meeting_time'],
  bs_meeting_missed:           ['name']
};
// Templates whose URL button carries the token as its dynamic suffix.
const TEMPLATE_URL_TOKEN = new Set(['bs_access_reminder', 'bs_application_pending']);

const j = $json;
const b64 = (o) => Buffer.from(JSON.stringify(o)).toString('base64');
const stop = (event, detail) => [{ json: { ...j, action: 'stop',
  stop_p: b64({ lead_id: j.lead_id, event, msg_key: (detail && detail.msg_key) || 'none', ...detail }) } }];

const seq  = SEQ[j.followup_sequence];
const step = seq ? seq[j.followup_step] : null;

// Sequence finished, or a sequence we do not recognise. next_action_at MUST
// be cleared or the scheduler re-selects this lead every 5 minutes forever.
if (!step) {
  return stop('scheduler_sequence_complete', { msg_key: 'none' });
}

// Out of window with no approved template: halt and flag. Never guess.
if (!j.window_open && !j.template_name) {
  return stop('scheduler_halted_no_template', { msg_key: step.key });
}

// ---- merges available to WF-05 from the lead record -------------------
const link = j.active_token
  ? 'https://go.brandsure.in/a?t=' + j.active_token
  : null;
const pkg = (j.route && j.route !== 'UNSET')
  ? j.route.charAt(0) + j.route.slice(1).toLowerCase()
  : null;

const MERGE = {
  name:         j.first_name || null,
  package:      pkg,
  main_problem: j.main_problem || null,
  city:         j.city || null,
  lead_band:    j.enquiry_band || null,
  link:         link,
  resume_link:  link
  // pending_topic, missing, last_section, next_section, flow_link and
  // meeting_time are conversation state the scheduler does not hold.
  // They resolve when the Part 4 brain owns them; until then any copy
  // needing one halts below rather than sending a broken message.
};

const nextAt = step.gap == null
  ? null
  : new Date(Date.now() + step.gap * 3600 * 1000).toISOString();

// ---- out of window: an approved template -----------------------------
if (!j.window_open) {
  const names = TEMPLATE_PARAMS[j.template_name];
  if (!names) {
    return stop('scheduler_unknown_template', { msg_key: step.key, template: j.template_name });
  }
  const values = names.map((n) => MERGE[n]);
  const missing = names.filter((n, i) => values[i] == null || values[i] === '');
  if (missing.length) {
    return stop('scheduler_missing_merge',
                { msg_key: step.key, template: j.template_name, missing: missing.join(' ') });
  }
  if (TEMPLATE_URL_TOKEN.has(j.template_name) && !j.active_token) {
    return stop('scheduler_missing_merge',
                { msg_key: step.key, template: j.template_name, missing: 'url_token' });
  }

  const components = [{ type: 'body',
    parameters: values.map((v) => ({ type: 'text', text: String(v) })) }];
  if (TEMPLATE_URL_TOKEN.has(j.template_name)) {
    components.push({ type: 'button', sub_type: 'url', index: '0',
      parameters: [{ type: 'text', text: j.active_token }] });
  }

  return [{ json: { ...j,
    action: 'send',
    msg_key: step.key,
    use_template: true,
    next_action_at: nextAt,
    body: '[template] ' + j.template_name,
    wa_payload: {
      messaging_product: 'whatsapp',
      to: j.phone,
      type: 'template',
      template: {
        name: j.template_name,
        language: { code: j.language === 'ml' ? 'ml' : 'en' },
        components
      }
    }
  }}];
}

// ---- in window: free-form copy from message_copy ---------------------
if (!j.copy_body) {
  return stop('scheduler_missing_copy', { msg_key: step.key, language: j.language });
}

let body = String(j.copy_body);
for (const k of Object.keys(MERGE)) {
  const v = MERGE[k];
  if (v != null && v !== '') body = body.split('{{' + k + '}}').join(String(v));
}

// Never send a body still carrying an unresolved merge.
const left = body.match(/\\{\\{[a-z_]+\\}\\}/g);
if (left) {
  return stop('scheduler_missing_merge',
              { msg_key: step.key, missing: left.join(' ') });
}

// Buttons: max 3, titles <= 20 chars (platform limit). LIST: rows are for
// list messages, which WF-05 does not send - those keys fall back to text.
let btnSrc = j.copy_buttons;
if (typeof btnSrc === 'string') { try { btnSrc = JSON.parse(btnSrc); } catch (e) { btnSrc = []; } }
const buttons = (Array.isArray(btnSrc) ? btnSrc : [])
  .filter((x) => typeof x === 'string' && x.length && !x.startsWith('LIST:'))
  .slice(0, 3)
  .map((x, i) => ({ type: 'reply', reply: { id: 'opt_' + (i + 1), title: x.slice(0, 20) } }));

const wa_payload = buttons.length
  ? { messaging_product: 'whatsapp', to: j.phone, type: 'interactive',
      interactive: { type: 'button', body: { text: body },
                     action: { buttons } } }
  : { messaging_product: 'whatsapp', to: j.phone, type: 'text', text: { body } };

return [{ json: { ...j,
  action: 'send',
  msg_key: step.key,
  use_template: false,
  next_action_at: nextAt,
  body,
  wa_payload
}}];`
    }
  }
});

const routeAction = switchCase({
  version: 3.2,
  config: {
    name: 'Send or Stop',
    parameters: {
      rules: {
        values: [
          { outputKey: 'send', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.action }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'send' }], combinator: 'and' } },
          { outputKey: 'stop', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.action }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'stop' }], combinator: 'and' } }
        ]
      },
      options: {}
    }
  }
});

const needsTemplate = ifElse({
  version: 2.2,
  config: {
    name: 'Window Closed?',
    parameters: {
      conditions: {
        options: { caseSensitive: true, leftValue: '', typeValidation: 'loose' },
        conditions: [{ leftValue: expr('{{ $json.use_template }}'), operator: { type: 'boolean', operation: 'true', singleValue: true } }],
        combinator: 'and'
      }
    }
  }
});

// Both send nodes just post the payload Decide Message already built, so
// button construction and template parameter mapping live in one place.
const sendTemplate = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Send Template',
    parameters: {
      method: 'POST',
      url: 'https://graph.facebook.com/v21.0/REPLACE_PHONE_NUMBER_ID/messages',
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      sendBody: true,
      contentType: 'json',
      specifyBody: 'json',
      jsonBody: expr('{{ JSON.stringify($json.wa_payload) }}'),
      options: {}
    },
    credentials: { httpHeaderAuth: { id: 'aCKOlR6MC1T5AeXN', name: 'WhatsApp Meta Header' } }
  }
});

const sendText = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Send Session Message',
    parameters: {
      method: 'POST',
      url: 'https://graph.facebook.com/v21.0/REPLACE_PHONE_NUMBER_ID/messages',
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      sendBody: true,
      contentType: 'json',
      specifyBody: 'json',
      jsonBody: expr('{{ JSON.stringify($json.wa_payload) }}'),
      options: {}
    },
    credentials: { httpHeaderAuth: { id: 'aCKOlR6MC1T5AeXN', name: 'WhatsApp Meta Header' } }
  }
});

const buildLogRow = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Build Log Row',
    parameters: {
      jsCode: `// .item (not .first()) - Due Leads returns up to 25 leads, so the row must
// be resolved per item or every log line would describe the first lead.
const d = $('Decide Message').item.json;
const resp = $json || {};
const waId = (resp.messages && resp.messages[0] && resp.messages[0].id) || null;

// Base64 for the same reason as WF-03: the Postgres node splits Query
// Parameters on commas and drops empties, and a message body is virtually
// guaranteed to contain a comma.
const payload = {
  lead_id:        d.lead_id,
  next_action_at: d.next_action_at,      // null ends the sequence
  body:           d.body,
  use_template:   !!d.use_template,
  template_name:  d.template_name || null,
  wa_message_id:  waId,
  msg_key:        d.msg_key
};
return [{ json: { p: Buffer.from(JSON.stringify(payload)).toString('base64') } }];`
    }
  }
});

const logAdvance = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Log + Advance',
    parameters: {
      operation: 'executeQuery',
      query: `WITH e AS (
  SELECT convert_from(decode($1,'base64'),'UTF8')::jsonb AS j
), adv AS (
  UPDATE leads
  SET followup_step  = followup_step + 1,
      next_action_at = (SELECT (j->>'next_action_at')::timestamptz FROM e)
  WHERE lead_id = (SELECT j->>'lead_id' FROM e)
  RETURNING lead_id
)
INSERT INTO message_log (lead_id, direction, body, is_template, template_name, wa_message_id, meta)
SELECT j->>'lead_id', 'out', j->>'body', (j->>'use_template')::boolean,
       j->>'template_name', j->>'wa_message_id',
       jsonb_build_object('msg_key', j->>'msg_key')
FROM e, adv
RETURNING id;`,
      options: { queryReplacement: expr('{{ $json.p }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const stopSequence = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Stop Sequence',
    parameters: {
      operation: 'executeQuery',
      query: `-- One base64 parameter: a halt reason can list several unresolved merges,
-- and that list contains commas.
WITH e AS (
  SELECT convert_from(decode($1,'base64'),'UTF8')::jsonb AS j
), h AS (
  UPDATE leads SET next_action_at = NULL
  WHERE lead_id = (SELECT j->>'lead_id' FROM e)
  RETURNING lead_id
)
INSERT INTO lead_events (lead_id, source, event, payload)
SELECT h.lead_id, 'system', e.j->>'event', e.j
FROM h, e
RETURNING id;`,
      options: { queryReplacement: expr('{{ $json.stop_p }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

export default workflow('wf05-followup-scheduler', 'WF-05 Follow-Up Scheduler')
  .add(every5)
  .to(dueLeads)
  .to(decide)
  .to(routeAction
    .onCase(0, needsTemplate
      .onTrue(sendTemplate.to(buildLogRow.to(logAdvance)))
      .onFalse(sendText.to(buildLogRow.to(logAdvance))))
    .onCase(1, stopSequence));
