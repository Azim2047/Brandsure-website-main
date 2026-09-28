# Inter, self-hosted

The BSU Theme File specifies Inter. It is served from this directory rather
than from a third-party CDN: a font from another host costs a DNS lookup and a
fresh connection on a 4G phone — the single worst place to spend the LCP
budget — and it hands a list of the site's visitors to someone else.

## What is missing

The font files themselves. They are not in the repository because they are not
in the supplied project bundle, and I will not pull a binary from an unverified
source into a production theme.

## Dropping them in

1. Get Inter from the official release (`rsms/inter`, SIL Open Font License).
2. Put these four `.woff2` files here — the subset the design actually uses,
   which is the four weights in the token scale and nothing else:

   ```
   inter-400.woff2   regular   body, descriptions
   inter-600.woff2   semibold  card titles, buttons, nav
   inter-700.woff2   bold      headlines
   ```

3. Create `assets/css/fonts.css` with one `@font-face` per file:

   ```css
   @font-face {
     font-family: "Inter";
     font-style: normal;
     font-weight: 400;
     font-display: swap;                    /* text paints immediately */
     src: url("../fonts/inter-400.woff2") format("woff2");
     unicode-range: U+0000-00FF, U+20B9;    /* Latin plus the rupee sign */
   }
   ```

   `font-display: swap` matters here: the fallback stack in `tokens.css` is a
   system UI font, so the page is readable from the first paint and simply
   sharpens when Inter arrives. Nothing shifts layout enough to notice, and
   nothing is invisible while waiting.

   `U+20B9` is the rupee sign. Leaving it out of the subset would send every
   price on the site to a fallback font mid-sentence.

`functions.php` enqueues `fonts.css` only if it exists, so the theme runs
correctly today on the system stack and picks Inter up the moment the files
land — no code change.
