# `assets/`

`css/tokens.css` is the design system: the only file that defines a colour, a type size, a
radius or a shadow. A hard-coded hex value anywhere else in the theme is a review failure.

`css/brandsure.css` (Chat 1) holds component styles, written entirely against those tokens.

`js/brandsure.js` (Chat 1) is vanilla JavaScript, no framework and no jQuery: the mobile nav,
the sticky bar, tap-to-load video, the gate form and the event beacon.

`img/icons.svg` is one inline SVG sprite. No icon font, no per-icon HTTP request.

Inter is self-hosted here rather than fetched from a third party, to keep the LCP budget.
