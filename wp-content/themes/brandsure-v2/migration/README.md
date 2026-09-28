# One-time migration seeds

Starting content for **editable** pages, to be imported into WordPress **once**
and then never read again.

## These files are not the source of anything

After import, the page's words live in the WordPress database and nowhere
else. That is the whole of Override 4: one editable copy, in one place.

So a file in this directory is in one of two states:

* **not yet imported** — it is the starting content for a page that does not
  exist in WordPress yet, or exists empty;
* **already imported** — it is dead weight, and should be deleted in the same
  commit that confirms the import.

Editing a seed after import changes nothing on the site. If a seed is left
here after import it will eventually be edited by someone expecting it to,
and that is the drift this project exists to prevent. `tools/guard.php`
therefore lists any seed still present as a warning, so they cannot linger
unnoticed.

## What is here now

| Seed | Page | Words |
|---|---|---|
| `about.html` | `/about/` | ~240 |
| `furniture-growth-marketing-india.html` | that service page | ~1,670 |
| `furniture-business-consulting-india.html` | that service page | ~1,680 |
| `furniture-product-design-services-india.html` | that service page | ~1,250 |
| `furniture-labour-hiring-india.html` | that service page | ~1,290 |
| `privacy-policy.html` | `/privacy-policy/` | ~1,040 |
| `cookie-policy.html` | `/cookie-policy/` | ~640 |
| `terms-and-conditions.html` | `/terms-and-conditions/` | ~710 |
| `disclaimer.html` | `/disclaimer/` | ~550 |

The four legal seeds are reviewed legal text carried over as it was, with one exception:
the tracking claims in the Cookie and Privacy policies were corrected to match what the
theme actually does. See `docs/OPEN-ITEMS.md` item 11 before editing those two.

**Repaired in Chat 11.** The four service seeds were missing something. Their common-questions
sections came from `<details><summary>` blocks on the old pages, and the conversion to block
markup dropped every `<summary>` — leaving a heading followed by ten to twelve answers with no
questions attached. The same conversion flattened each page's `<h1>` into a paragraph, so every
service page opened by repeating its own title. The 49 questions were recovered from the old
pages, each checked against its answer before being reattached, and are now core Details blocks;
the four duplicate title paragraphs are gone. This is why `FAQPage` schema is derived from the
published content rather than assumed from the template — deriving it is what made the loss
visible.

## Importing one

```bash
wp post create --post_type=page --post_title='About' --post_name=about \
  --post_status=publish --post_content="$(cat migration/about.html)"
```

Then check the page in wp-admin, confirm the editor opens normally (About is
editable — the block editor must work), and delete the seed.

## What was carried over, and what was not

Only what the brief allows from the old site: founder copy worth preserving,
useful SEO copy, service-page information. Never old pricing, package names,
commercial terms, navigation, the Growth Partnership explanation, or homepage
positioning.

Every seed passes the same disclosure and language checks as developer
content — the guard scans this directory — and the runtime content guard
checks it again on save, so an import cannot publish a private fact either.
