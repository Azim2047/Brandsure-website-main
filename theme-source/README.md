# The supplied theme, unmodified

`theme.js` and `BSU_Master_Theme_Spec_v3.md` exactly as BrandSure supplied them
(BSU Master Theme v3, 4 Sep 2026). Byte-identical to `ACTIVE/Theme/` in the Layer 4 pack —
verified by sha256 on both files.

**Do not edit either file.** They are inputs, not source. `theme.js` says so itself:

> No hex literal belongs in a deck file — import from here.

`tools/build-tokens.mjs` reads `theme.js` and generates
`wp-content/themes/brandsure-v2/assets/css/tokens.css`.
`tools/build-icons.mjs` reads its `ICON` / `ICON_LANE` vocabulary and generates the icon sprite.

That is the whole reason Node.js is in this repository: it makes the supplied theme executable
instead of something a person transcribes by hand. Transcription is what left `tokens.css`
carrying 13 of the theme's 23 colours with no lane abstraction, and the icon sprite missing 16
of its 29 meanings.

If BrandSure sends a new theme file, replace these two and re-run `npm run build`. The diff on
the generated files is then the complete answer to "what changed".
