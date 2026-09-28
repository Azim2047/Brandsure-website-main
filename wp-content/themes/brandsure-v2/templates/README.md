# `templates/` — page templates, section loops only

A template's whole job is: load its data, loop its sections, call components. It contains no
copy, no header, no footer and no layout decision that belongs in a component.

Controlled slugs are bound to their template in code (`inc/content-ownership.php`), not by the
page-attributes dropdown, so the binding cannot be changed from wp-admin.

| Template | Route | Ownership | Chat |
|---|---|---|---|
| `page-home.php` | `/` | controlled | 2 |
| `page-start.php` | `/start/` | controlled | 3 |
| `page-plans.php` | `/plans/` | controlled | 4 |
| `page-digital-partner.php` | `/digital-partner/` | controlled | 5 |
| `page-growth-partnership.php` | `/growth-partnership/` | controlled | 6 |
| `page-how-it-works.php` | `/how-it-works/` | controlled | 7 |
| `page-proof.php` | `/proof/` | controlled | 7 |
| `page-built-by.php` | `/built-by-brandsure/` | controlled, `noindex` | 7 |
| `page-services-index.php` | `/services/` | controlled | 9 |
| `page-service.php` | the four service pages | **editable body**, shared shell | 9 |
| `archive-blog.php` | `/blog/` | editable | 10 |
| `single-post.php` | `/blog/<post>/` | editable body + theme-appended gate card | 10 |

`page.php` at theme root serves the remaining editable pages — About and the four legal pages —
as a shared shell around `the_content()`.
