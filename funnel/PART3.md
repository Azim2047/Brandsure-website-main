# PART 3 — Copy pack, message registry, Meta template submissions

Applied to Supabase project `mzuzlfnkvsvrfmxdupvh` as migration
`part3_copy_and_template_registry`
(`funnel/db/migrations/0002_part3_copy_and_templates.sql`).

| | |
|---|---|
| `message_copy` | **30 rows** — 13 keys × EN/ML, in-window bodies + button arrays |
| `wa_templates` | **18 rows** — 9 Meta templates × EN/ML, all `draft` |
| approved templates | **0** — nothing can send out-of-window until Meta approves |

`wa_templates` is now keyed on `(template_name, language)`, since one Meta template
name spans both languages.

## What changed in WF-05

In-window copy is no longer hard-coded. `Due Leads` joins `message_copy` on
`followup_sequence || (followup_step + 1)` + `language`, and joins the lead's latest
live `landing_tokens` row to resolve `{{link}}` / `{{resume_link}}` without minting a
new token on every follow-up.

`Decide Message` resolves merges, builds the full Meta payload (including reply
buttons, capped at 3 × 20 chars), and maps each template's `{{n}}` parameters per the
submissions doc.

### Verified against the live database

| Case | Result |
|---|---|
| `C2` in-window, no merges | body from `message_copy`, 3 interactive buttons built correctly |
| `B2` in-window, merges + token | *"I recommended **Expert** because you mentioned **Slow or missed follow-ups** … Your private access: https://go.brandsure.in/a?t=…"* |
| `C1` with unresolvable merges | halted — `scheduler_missing_merge`, `missing: {{last_section}} {{next_section}} {{resume_link}}`, **0 messages sent** |

All three stopped only at `REPLACE_PHONE_NUMBER_ID`.

## Correction to the submissions doc

The doc's WF-05 join change is a substring test:

```sql
ON position(l.followup_sequence || (l.followup_step + 1)::text in t.used_by) > 0
```

Key `F2` is a substring of `used_by = 'F2P,F3P'`. Verified on the seeded data: key
`F2` matches **four** rows — `bs_meeting_reminder` *and*
`bs_meeting_reminder_partner`, in both languages — instead of two. A LEFT JOIN then
hands the scheduler the same lead twice and it sends two templates, one of them the
wrong one.

WF-05 uses exact list-element matching:

```sql
ON (l.followup_sequence || (l.followup_step + 1)::text)
   = ANY (string_to_array(t.used_by, ','))
```

`Due Leads` additionally applies `DISTINCT ON (l.lead_id)` as a second guard, so a
future registry mistake cannot double-send either, and orders oldest-due first so a
backlog drains fairly rather than always replaying the same 25 leads.

## Merges WF-05 can and cannot resolve

Resolved from the lead record: `{{name}}`, `{{package}}`, `{{main_problem}}`,
`{{city}}`, `{{lead_band}}`, `{{link}}`, `{{resume_link}}`.

**Not resolvable by the scheduler:** `{{pending_topic}}`, `{{missing}}`,
`{{last_section}}`, `{{next_section}}`, `{{flow_link}}`, `{{meeting_time}}`. These are
conversation state that only the Part 4 brain holds. Any copy or template needing one
halts with `scheduler_missing_merge` naming exactly which merge was missing, rather
than sending a body containing a literal `{{missing}}`.

That currently affects **A1, A2, C1, E1, E2** in-window, and the
`bs_assessment_saved` / `bs_application_pending` templates. `B1`, `B2`, `C2`, `D1`
resolve fully today.

## Still outstanding

1. **Submit T1–T9 to Meta**, EN then ML under the same name, category UTILITY, using
   the sample values from the submissions doc verbatim.
2. **Flip `meta_status` per approval:**
   `update wa_templates set meta_status='approved' where template_name='…' and language='…';`
   Never set it by hand to bypass a rejection — that flag is the only thing standing
   between the scheduler and an unapproved send.
3. **Sequence F is not wired.** `F1`–`F4` templates are registered, but WF-05's `SEQ`
   map has no F sequence: booking reminders are event-driven off a meeting time, not
   gap-driven, so they belong to a booking workflow that does not exist yet.
4. **Kannada, Tamil and Hindi** are phased later; `leads.language` already accepts
   them and the copy tables are keyed by language, so it is a data job, not a
   schema change.
