# `inc/` — behaviour, no markup

PHP that decides things. Nothing in here echoes markup; that is `components/`.

Files are added in the chat that builds them. `functions.php` loads each one only if it exists,
so the skeleton is installable today.

| File | Chat | Responsibility |
|---|---|---|
| `data.php` | 1 | Read `data/*.json`, validate against the section-type registry, fail loudly under `WP_DEBUG`, cache the parsed result in a transient keyed by theme version. One public helper: `bs_data( 'pages/plans' )`. |
| `content-ownership.php` | 1 | The controlled/editable slug registry, plus the three runtime locks: block editor disabled on controlled pages, `post_content` forced empty on save, template bound in code rather than by the admin dropdown. This is what makes Override 4 structural. |
| `content-guard.php` | 8 | The same disclosure rules applied to WordPress editor content, which `guard.php` cannot see. Holds a post as a draft at save time if it carries a private fact, naming the phrase for the editor; elides it at render time as a last resort. Shares `BrandSure_Guard::blocklist()` so the two can never disagree. |
| `guard.php` | 1 | The seven build checks in `docs/SOURCE-OF-TRUTH.md` §5.4: global disclosure blocklist, the stricter Growth-Partnership blocklist, the `plan=Growth Partnership` route check, the banned-language list, price parity with Supabase `commercial_facts`, `FAQPage` honesty, and the 350-word budget. Runs in CI and blocks deploy. It reads files only and cannot see WordPress editor content — that is `content-guard.php`. |
| `events.php` | 1, wired 11 | The ten website events in `docs/EVENTS-AND-INTEGRATION.md` §2, the `bs_vid` cookie, and the beacon to the n8n `/landing-event` path. No-ops cleanly while `n8n_base` is empty — a missing endpoint must never break a page or block the WhatsApp CTA. |
| `schema.php` | 11 | `Organization`, `WebSite`, `Service`, `FAQPage`, `Article`, `BreadcrumbList`. Emits `FAQPage` only where a visible FAQ block renders. Subject to the disclosure blocklist like any other output. |
| `seo.php` | 11 | Title, meta description, canonical, OG and Twitter. Reads from `data/` for controlled pages and from post meta for editable ones — the same field never has two sources. |
| `redirects.php` | 11 | The `/pricing/` 301 and its variants. See `docs/REDIRECTS.md`. |
