# Layer 4 v4 — change and conflict register

*What the 5 September 2026 Layer 4 pack changed in this build, what it contradicted, and what
is left for BrandSure to settle. Written in Chat 15 against
`ACTIVE/BrandSure_Layer_4_Website_and_Knowledgebase_v4.md`, `ACTIVE/numbers.v2.js` and
`ACTIVE/Knowledgebase_Update_Prompt.txt`.*

The pack is a specification, not a copy of this site. Nothing in it was applied without first
reading what the build actually did.

---

## 1. Commercial facts — applied

| # | Was | Now | Authority |
|---|---|---|---|
| 1 | `digital_partner_adspend_start` = **₹1,00,000 / month** | **removed** | numbers.v2.js v2 changelog removes `DP_AD_START` by name as a *"forbidden universal ₹1,00,000 advertising budget"* |
| 2 | `agency_adspend_range` = **₹50,000 – ₹2,00,000 / month**, labelled "suggested" | **removed** | v4 §6 *"No fixed advertising budget"*. A hedge in front of a range does not stop it being a range |
| 3 | — | `advertising_separate` + `advertising_factors` added, **carrying no figure** | numbers.v2.js `DP_AD_NOTE`, `DP_AD_FACTORS` |
| 4 | "1 city · up to 300 new **enquiries** / month" | "1 city · **handling capacity** for up to 300 new **enquiry records** a month", plus a mandatory `capacity_qualifier` | v4 §6, `DP_CAPACITY_NOT` |
| 5 | Break-even shown with a loose note | Labelled an **illustration of the monthly fee only** — excludes setup, advertising, GST and other costs | v4 §6 |
| 6 | Agency setup scope, no exclusions | `agency_setup_scope` + `agency_setup_excludes` (**no follow-up system, no catalogue**) | `AG_SETUP_SCOPE`, `AG_SETUP_EXCLUDES` |
| 7 | DP setup scope loose | Adds the follow-up-system foundation and a best-seller catalogue from existing information, **max 50 products** | v4 §6 |
| 8 | Public **five-stage** GP roadmap (Foundation · Stability · Profit per Order · Scale · Stable at Scale) | **withdrawn**; replaced by the approved progression paragraph, which names no stage | v4 §4 withdraws the public five-stage diagram; numbers.v2.js separately replaced those five with four *private, dated* stages |
| 9 | Compare-table row "Ad budget you pay", carrying both figures | **row deleted** — an existing "Who pays for the ads" row already answered it | as #1, #2 |

**Retired plan names:** swept. Nothing in the build uses Standard, Expert, Virtual Partner,
Virtual Growth Partner or Virtual Growth Partnership as a plan name. Two service pages contain
the ordinary English *"Standard-product thinking"* and *"Standard low-ticket tactics"*; guard
R14 matches plan-label context rather than the bare word, so it does not fire on them. A rule
that flags approved copy is a rule people learn to skip.

---

## 2. Contact details — applied, and needing BrandSure's eye

numbers.v2.js is named the contact authority by v4 §2, and the update prompt says *"Do not use
older chat memory to override these files."* Applied on that basis:

| Field | Was | Now |
|---|---|---|
| Email | `info@brandsure.in` | **`hello@brandsure.in`** |
| WhatsApp | `917624964199` | **`918891199199`** |
| Phone | `+91 88911 99199` | unchanged — already matched |

**Two consequences that are BrandSure's to confirm, not the file's** (`OPEN-ITEMS.md` item 13):

1. **The WhatsApp line and the phone line are now the same number.** They were deliberately
   different: WhatsApp reached the AI assistant, the phone reached a person. numbers.v2.js
   gives one number for both. If the split is still wanted, `contact.whatsapp_number` in
   `data/site.json` is the only field to put back.
2. **`hello@brandsure.in` has to receive mail before launch.** It is now the grievance contact
   on all four legal pages. A grievance address that bounces is a compliance problem, not a
   typo.

Both previous values are recorded in `data/site.json`, so reversing either is one edit.

---

## 3. Conflicts recorded, not resolved

The pack's own internal discrepancies. Logged because v4 §10 asks for a register; none of them
is the website's to fix.

| # | Discrepancy | Status |
|---|---|---|
| 1 | `BrandSure_Master_Video_Plan.md` is referenced by all five VCBs and is **absent** from the pack | Not read, not summarised, not guessed at |
| 2 | D3 does not contain the ₹19.4 lakh / ₹51.3 lakh investment values its VCB mirror claims | A **missing mirror sentence**, not a numerical contradiction — recorded as such |
| 3 | D4 uses a ₹10,00,000 → ₹2,00,000 GST illustration; `GST_SENTENCE` freezes a ₹1,00,000 → ₹20,000 one. Mathematically consistent, **not verbatim** | A wording update to log against D4. No public surface carries either — both are GP-private |
| 4 | D1 PRE places D4 before D3/D5; VCB5 and D7 need the guides first | v4 adopts guides-then-D4. The public site is unaffected: it names no document |
| 5 | Client PDFs/DOCX still carry `+91 8884 446 668` and `anasuu@brandsure.in` | The document owner's task. **Zero** occurrences in this build |
| 6 | Bundled `components.js` imports absent `brand`, `icons`, `numbers` modules — not standalone | Not used. This theme has its own components |
| 7 | Website module price **₹90,000 before GST** exists in D2 but not in numbers.v2.js | **Not added.** The DP page says the website is optional without pricing it. Adding it needs a source-tagged extension citing D2 and its version — a decision, not a default |
| 8 | GST wording, the influencer percentage basis, other D2 §5 module prices | Absent from numbers.v2.js; none invented |

---

## 4. Not in scope of this chat

The pack's sales-flow and automation sections (§7, §9 private access, bot migration) need the
n8n workflows and the Supabase schema, which are outside this repository. They are unchanged
and unclaimed. `OPEN-ITEMS.md` item 2 already lists the six blockers on the event path, and the
STANDARD → AGENCY / EXPERT → DIGITAL_PARTNER / PARTNERSHIP → GROWTH_PARTNERSHIP compatibility
migration v4 §9 asks for is an automation task against real schemas — v4 is explicit that it
must not be a blind string replacement.

The website side of the event contract *is* done: five new event names, with an outbound
WhatsApp click kept strictly separate from a received conversation.
