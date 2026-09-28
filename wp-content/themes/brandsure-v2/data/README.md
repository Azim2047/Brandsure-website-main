# `data/` — the only place controlled copy lives

Every word on a **controlled** page comes from a file in this directory. Nothing in here is
editable from wp-admin; changes are commits.

Rules, in force from Chat 0:

1. **No private commercial fact may ever appear in this directory** — not in a value, not in a
   key, not in a comment. The private list is `docs/SOURCE-OF-TRUTH.md` §5.2. The theme has no
   use for those values, so it does not hold them.
2. **No data file may exist for an editable slug.** The four service pages, About, Blog and the
   legal pages are owned by WordPress. `inc/guard.php` fails the build if a file appears here
   for one of them.
3. **Every rupee figure must trace to the Master Numbers Sheet.** `commercial.json` records the
   section reference (`A1`, `A2`, `A4`) next to each value, so an audit can be mechanical.
4. **Empty is not a placeholder.** A value left `""` or `null` in these skeletons is a slot
   waiting for approved copy or an unresolved input (see `docs/OPEN-ITEMS.md`). Do not fill one
   by inventing content.

## Files

| File | Status | Filled in |
|---|---|---|
| `site.json` | skeleton | Chat 1 |
| `commercial.json` | skeleton, public facts seeded from Numbers Sheet A1/A2/A4 | Chat 1 |
| `videos.json` | skeleton | Chat 1 |
| `plans.json` | **filled (Chat 4)** | Chat 4 |
| `proof.json` | Chat 7 | Chat 7 |
| `faq.json` | Chat 7 | Chat 7 |
| `pages/*.json` | one per controlled page | with that page |
| `pages/services-index.json` | **filled (Chat 9)** | Chat 9 |

## Page file shape

```json
{
  "slug": "plans",
  "title": "",
  "meta_description": "",
  "sections": [
    { "type": "hero",        "...": "props for components/hero.php" },
    { "type": "plan-cards",  "...": "..." },
    { "type": "cta",         "...": "..." }
  ]
}
```

`sections` is an ordered list of component invocations. `type` maps to a file in `components/`.
A template loops the array and does nothing else — no template contains layout decisions that
belong to a component, and no template contains copy.
