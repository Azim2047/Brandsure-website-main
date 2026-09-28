// WF-01 Inbound Message Router — n8n Workflow SDK source, as authored.
// Deployed as n8n workflow syhzsnZ65q1EArCm.
//
// Receives everything Meta sends on wa-inbound: messages (text, buttons, lists,
// voice) AND delivery statuses. Ends at an AI Brain stub that Part 4 replaces.
//
// Before activating: replace REPLACE_PHONE_NUMBER_ID and REPLACE_VERIFY_TOKEN,
// and check no other workflow is bound to the same Meta phone number.

import { workflow, node, trigger, expr, ifElse, switchCase } from '@n8n/workflow-sdk';

const verifyGet = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2,
  config: {
    name: 'WA Verify (GET)',
    parameters: { httpMethod: 'GET', path: 'wa-inbound', responseMode: 'responseNode', options: {} }
  }
});

const verifyReply = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.1,
  config: {
    name: 'Verify Reply',
    parameters: {
      respondWith: 'text',
      responseBody: expr("{{ $json.query['hub.verify_token'] === 'REPLACE_VERIFY_TOKEN' ? $json.query['hub.challenge'] : 'forbidden' }}"),
      options: {}
    }
  }
});

const inbound = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2,
  config: {
    name: 'WA Inbound (POST)',
    parameters: { httpMethod: 'POST', path: 'wa-inbound', responseMode: 'onReceived', options: {} }
  }
});

const unpack = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Unpack',
    parameters: {
      jsCode: `// Meta sends messages AND status updates on the same webhook. Split them
// into one item each so the Switch can route them separately.
const out = [];
const body = $json.body || {};
for (const entry of (body.entry || [])) {
  for (const ch of (entry.changes || [])) {
    const v = ch.value || {};

    for (const s of (v.statuses || [])) {
      out.push({ json: { kind: 'status', wa_message_id: s.id,
        status: s.status, errors: s.errors || null, ts: s.timestamp } });
    }

    for (const m of (v.messages || [])) {
      const base = { phone: '+' + m.from, wa_message_id: m.id,
        wa_name: (v.contacts && v.contacts[0] && v.contacts[0].profile
                  && v.contacts[0].profile.name) || null };
      if (m.type === 'audio') {
        out.push({ json: { ...base, kind: 'voice', media_id: m.audio.id } });
      } else {
        let text = null; const meta = {};
        if (m.type === 'text') text = m.text.body;
        else if (m.type === 'interactive' && m.interactive.type === 'button_reply') {
          text = m.interactive.button_reply.title; meta.button_id = m.interactive.button_reply.id;
        } else if (m.type === 'interactive' && m.interactive.type === 'list_reply') {
          text = m.interactive.list_reply.title; meta.list_id = m.interactive.list_reply.id;
        } else if (m.type === 'button') text = m.button.text;   // template quick-reply
        else meta.unsupported_type = m.type;
        out.push({ json: { ...base, kind: 'message', body_text: text,
                           media_type: m.type, meta } });
      }
    }
  }
}
return out;`
    }
  }
});

const routeKind = switchCase({
  version: 3.2,
  config: {
    name: 'Switch: kind',
    parameters: {
      rules: {
        values: [
          { outputKey: 'status', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.kind }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'status' }], combinator: 'and' } },
          { outputKey: 'voice', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.kind }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'voice' }], combinator: 'and' } },
          { outputKey: 'message', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.kind }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'message' }], combinator: 'and' } }
        ]
      },
      options: {}
    }
  }
});

const updateStatus = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Update Delivery Status',
    parameters: {
      operation: 'executeQuery',
      query: `UPDATE message_log
SET delivery_status = $2
WHERE wa_message_id = $1
RETURNING lead_id, is_template, template_name;`,
      // Two parameters is safe here: both are guaranteed non-empty and
      // comma-free (a wamid and one of sent|delivered|read|failed).
      options: { queryReplacement: expr('{{ $json.wa_message_id }},{{ $json.status }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const isTemplateFailure = ifElse({
  version: 2.2,
  config: {
    name: 'Template Failed?',
    parameters: {
      conditions: {
        options: { caseSensitive: true, leftValue: '', typeValidation: 'loose' },
        conditions: [
          { leftValue: expr('{{ $json.is_template }}'), operator: { type: 'boolean', operation: 'true', singleValue: true } },
          { leftValue: expr("{{ $('Switch: kind').item.json.status }}"), operator: { type: 'string', operation: 'equals' }, rightValue: 'failed' }
        ],
        combinator: 'and'
      }
    }
  }
});

const buildSuppressRow = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Build Suppress Row',
    parameters: {
      jsCode: `// Meta's errors array contains commas, so this goes as one base64 param.
const row = $json;                       // from Update Delivery Status
const s = $('Switch: kind').item.json;   // the status event itself
return [{ json: { p: Buffer.from(JSON.stringify({
  lead_id: row.lead_id,
  template: row.template_name || null,
  status: s.status,
  errors: s.errors || null
})).toString('base64') } }];`
    }
  }
});

const suppress = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Template-Fail Suppress',
    parameters: {
      operation: 'executeQuery',
      query: `-- A failed or capped template HALTS the sequence and flags the lead,
-- instead of the system assuming delivery and marching on.
WITH e AS (
  SELECT convert_from(decode($1,'base64'),'UTF8')::jsonb AS j
), halted AS (
  UPDATE leads SET next_action_at = NULL
  WHERE lead_id = (SELECT j->>'lead_id' FROM e)
  RETURNING lead_id
)
INSERT INTO lead_events (lead_id, source, event, payload)
SELECT h.lead_id, 'system', 'template_failed', e.j
FROM halted h, e
RETURNING id;`,
      options: { queryReplacement: expr('{{ $json.p }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const getMediaUrl = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Get Media URL',
    parameters: {
      method: 'GET',
      url: expr('https://graph.facebook.com/v21.0/{{ $json.media_id }}'),
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      options: {}
    },
    credentials: { httpHeaderAuth: { id: 'aCKOlR6MC1T5AeXN', name: 'WhatsApp Meta Header' } }
  }
});

const downloadAudio = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Download Audio',
    parameters: {
      method: 'GET',
      url: expr('{{ $json.url }}'),
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      options: { response: { response: { responseFormat: 'file', outputPropertyName: 'data' } } }
    },
    credentials: { httpHeaderAuth: { id: 'aCKOlR6MC1T5AeXN', name: 'WhatsApp Meta Header' } }
  }
});

// UNTESTED: needs a real voice note to exercise. The multipart upload in
// particular has not been run against Whisper.
const transcribe = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Transcribe',
    parameters: {
      method: 'POST',
      url: 'https://api.openai.com/v1/audio/transcriptions',
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      sendBody: true,
      contentType: 'multipart-form-data',
      bodyParameters: {
        parameters: [
          { parameterType: 'formBinaryData', name: 'file', inputDataFieldName: 'data' },
          { parameterType: 'formData', name: 'model', value: 'whisper-1' }
        ]
      },
      options: {}
    },
    credentials: { httpHeaderAuth: { id: 'tmNB20c9MbDeO3ov', name: 'OpenAI API Key' } }
  }
});

const voiceToText = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Voice To Text',
    parameters: {
      jsCode: `// Fold the transcription back into the same shape a text message has.
const v = $('Switch: kind').item.json;
const t = $json || {};
return [{ json: {
  ...v,
  kind: 'message',
  body_text: t.text || null,
  media_type: 'voice',
  meta: { transcribed: true }
}}];`
    }
  }
});

const buildInboundRow = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Build Inbound Row',
    parameters: {
      jsCode: `// A WhatsApp message body is free text and will contain commas, so it
// travels as one base64 parameter rather than a comma-separated list.
const j = $json;
return [{ json: { p: Buffer.from(JSON.stringify({
  phone:         j.phone,
  wa_name:       j.wa_name || null,
  body_text:     j.body_text || null,
  media_type:    j.media_type || 'text',
  wa_message_id: j.wa_message_id,
  meta:          j.meta || {}
})).toString('base64') } }];`
    }
  }
});

const upsert = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Upsert Lead + Log + Window',
    parameters: {
      operation: 'executeQuery',
      query: `WITH e AS (
  SELECT convert_from(decode($1,'base64'),'UTF8')::jsonb AS j
), lead AS (
  INSERT INTO leads (phone, wa_name, first_name, last_inbound_at)
  SELECT j->>'phone', j->>'wa_name',
         NULLIF(split_part(COALESCE(j->>'wa_name',''), ' ', 1), ''), now()
  FROM e
  ON CONFLICT (phone) DO UPDATE
    SET wa_name         = COALESCE(EXCLUDED.wa_name, leads.wa_name),
        last_inbound_at = now(),
        next_action_at  = NULL          -- every inbound cancels the pending action
  RETURNING lead_id, conv_state, route, track, language, ai_paused, opted_out
), logged AS (
  INSERT INTO message_log (lead_id, direction, body, media_type, wa_message_id, meta)
  SELECT l.lead_id, 'in', e.j->>'body_text', e.j->>'media_type',
         e.j->>'wa_message_id', COALESCE(e.j->'meta', '{}'::jsonb)
  FROM lead l, e
  RETURNING lead_id
)
SELECT l.lead_id, l.conv_state, l.route, l.track, l.language,
       l.ai_paused, l.opted_out,
       (SELECT j->>'phone'     FROM e) AS phone,
       (SELECT j->>'body_text' FROM e) AS body_text,
       (SELECT count(*) FROM logged)   AS logged
FROM lead l;`,
      options: { queryReplacement: expr('{{ $json.p }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const deepCodes = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Handle Deep-Link Codes',
    parameters: {
      jsCode: `// The wa.me prefills from the landing page, and the STOP rule, resolved
// BEFORE the AI ever sees the message.
const j = $json;
const t = String(j.body_text || '').trim().toUpperCase();
const m = t.match(/^(CONFIRM|TERMS|QUESTION|CLARIFY|RENEW|HELLO)\\s+(BS-\\d+)/);
const wants_stop = /^(STOP|UNSUBSCRIBE|NOT INTERESTED)/.test(t);

// Human takeover and opt-out both mean: do nothing, silently.
const silent = j.ai_paused === true || j.opted_out === true;

return [{ json: { ...j,
  deep_code: m ? m[1] : null,
  deep_lead: m ? m[2] : null,
  wants_stop,
  action: wants_stop ? 'stop' : (silent ? 'silent' : 'reply')
}}];`
    }
  }
});

// 'silent' has no case here on purpose: an ai_paused or opted_out lead falls
// through every rule and the run ends with nothing sent.
const routeAction = switchCase({
  version: 3.2,
  config: {
    name: 'Stop / Silent / Reply',
    parameters: {
      rules: {
        values: [
          { outputKey: 'stop', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.action }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'stop' }], combinator: 'and' } },
          { outputKey: 'reply', conditions: { options: { caseSensitive: true, leftValue: '', typeValidation: 'strict' }, conditions: [{ leftValue: expr('{{ $json.action }}'), operator: { type: 'string', operation: 'equals' }, rightValue: 'reply' }], combinator: 'and' } }
        ]
      },
      options: {}
    }
  }
});

const optOut = node({
  type: 'n8n-nodes-base.postgres',
  version: 2.5,
  config: {
    name: 'Opt Out',
    parameters: {
      operation: 'executeQuery',
      query: `WITH o AS (
  UPDATE leads SET opted_out = true, next_action_at = NULL
  WHERE lead_id = $1
  RETURNING lead_id
)
INSERT INTO lead_events (lead_id, source, event, payload)
SELECT lead_id, 'whatsapp', 'opted_out', '{}'::jsonb FROM o
RETURNING id;`,
      options: { queryReplacement: expr('{{ $json.lead_id }}') }
    },
    credentials: { postgres: { id: '6qn1m9qWuhRwzfF7', name: 'Postgres account' } }
  }
});

const aiBrain = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'AI Brain (stub)',
    parameters: {
      jsCode: `// PART 4 REPLACES THIS NODE.
// It receives the full lead state: lead_id, conv_state, route, track,
// language, body_text, and the resolved deep_code / deep_lead.
return [{ json: {
  ...$json,
  reply: 'Received - the assistant will be connected shortly.'
}}];`
    }
  }
});

const sendReply = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.2,
  config: {
    name: 'Send WhatsApp Reply',
    parameters: {
      method: 'POST',
      url: 'https://graph.facebook.com/v21.0/REPLACE_PHONE_NUMBER_ID/messages',
      authentication: 'genericCredentialType',
      genericAuthType: 'httpHeaderAuth',
      sendBody: true,
      contentType: 'json',
      specifyBody: 'json',
      jsonBody: expr("{{ JSON.stringify({ messaging_product: 'whatsapp', to: $json.phone, type: 'text', text: { body: $json.reply } }) }}"),
      options: {}
    },
    credentials: { httpHeaderAuth: { id: 'aCKOlR6MC1T5AeXN', name: 'WhatsApp Meta Header' } }
  }
});

export default workflow('wf01-inbound-router', 'WF-01 Inbound Message Router')
  .add(verifyGet)
  .to(verifyReply)
  .add(inbound)
  .to(unpack)
  .to(routeKind
    .onCase(0, updateStatus.to(isTemplateFailure.onTrue(buildSuppressRow.to(suppress))))
    .onCase(1, getMediaUrl.to(downloadAudio).to(transcribe).to(voiceToText).to(buildInboundRow))
    .onCase(2, buildInboundRow))
  .add(buildInboundRow)
  .to(upsert)
  .to(deepCodes)
  .to(routeAction
    .onCase(0, optOut)
    .onCase(1, aiBrain.to(sendReply)));
