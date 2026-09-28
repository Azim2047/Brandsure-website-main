// theme.js — BSU Master Theme v3 (LIGHT). Verified against BrandSure_Digital_Partner.pptx + BSU_Theme_File.docx.
// Supersedes v1 and v2. Source of truth: BSU_Master_Theme_Spec_v3.md.
// No hex literal belongs in a deck file — import from here.

const C = {
  // core
  INK: "081E2E",          // headlines, dark callout cards, the customer's step
  BLUE: "116E98",         // BrandSure's lane
  BLUE_D: "0D5D81",       // pressed / darker blue
  GREEN: "22BE6C",        // your lane, a sale, ticks, included
  GREEN_D: "1BA85C",      // green text on light
  TEAL: "30A4A0",         // shared lane, your team
  AQUA: "7DD1C9",         // connectors, chevrons, glow, progress
  // dark panels
  NAVY: "0E3247", BLUE_DEEP: "12384E", NAVY_2: "27546B",
  // text
  WHITE: "FFFFFF", BODY: "4B6673", SUB: "7F96A0",
  MUTED: "A9C4D2",        // body text ON dark cards
  MUTED_D: "8FA9B6",      // secondary on dark, "before" states
  // surfaces
  BG: "FFFFFF", BORDER: "E8F3F2", BORDER_2: "CFE4EA", BORDER_G: "BEE9CF",
  MIST: "F0F8F7", MIST_2: "DCEBF0", TINT_G: "E7F6F0", TINT_G2: "F4FAF9",
  // caution — the system's ONLY warm colour. There is no red or coral.
  WARN: "FCC851", WARN_BG: "FBF3DF", WARN_LINE: "FFEDC2", WARN_TXT: "9A7B2E",
};

const F = { HEAD: "Inter", BODY: "Inter", FALLBACK: "Calibri" };

// type scale (pt) — 13.33 x 7.5 in. These are the sizes actually used in the deck.
const T = {
  HEADLINE: 27, HEADLINE_B: 32, EYEBROW: 12, SUB: 13.5,
  NUMBER: 26, NUMBER_XL: 34, CARD: 15, LABEL: 12.5, TAG: 12,
  TAKEAWAY: 14, DARK_BODY: 12.5, FOOT: 9,
};

// icon states — the circle fill IS the lane code. No tiles, no borders.
const CIRCLE = {
  bs:      { fill: C.BLUE,  glyph: C.WHITE },
  you:     { fill: C.GREEN, glyph: C.WHITE },
  shared:  { fill: C.TEAL,  glyph: C.WHITE },
  problem: { fill: C.BODY,  glyph: C.WHITE },
  quiet:   { fill: C.MIST,  glyph: C.BLUE  },
  onDark:  { fill: C.GREEN, fillAlpha: 16, glyph: C.GREEN },   // ticks inside a dark card
};
const GLYPH_RATIO = 0.50;   // glyph box / circle diameter
const STROKE = 2.0;         // Lucide stroke on the 24 grid
const CIRCLE_D = { row: 0.6, list: 0.42, hero: 1.1, tick: 0.26 };

// icon vocabulary — meaning -> Lucide name
const ICON = {
  owner: "user-round", showroom: "store", factory: "factory", truck: "truck", keep: "coins", check: "check",
  worker: "hard-hat", customers: "users", customer: "user", sofa: "sofa", quotation: "receipt",
  ad: "megaphone", phone: "phone", whatsapp: "message-circle", calendar: "calendar-days", clipboard: "clipboard-list",
  gear: "settings", shield: "shield-check", territory: "map-pin", website: "globe", creative: "pen-tool",
  report: "file-text", review: "search", growth: "trending-up", handshake: "handshake", rupee: "indian-rupee",
  clock: "clock", leak: "droplets", unknown: "circle-help",
};
const ICON_LANE = {
  owner: "you", showroom: "you", factory: "you", truck: "you", keep: "you", check: "you",
  worker: "shared", customers: "shared", customer: "shared", sofa: "shared", quotation: "shared",
  ad: "bs", phone: "bs", whatsapp: "bs", calendar: "bs", clipboard: "bs", gear: "bs", shield: "bs",
  territory: "bs", website: "bs", creative: "bs", report: "bs", review: "bs", growth: "bs",
  handshake: "bs", rupee: "bs",
  clock: "problem", leak: "problem", unknown: "problem",
};

// surfaces
const CARD = { fill: C.WHITE, radius: 0.1, border: C.BORDER, borderW: 1,
  shadow: { type: "outer", color: C.BLUE, opacity: 0.07, blur: 9, offset: 5, angle: 90 } };
const DARK_CARD = { fill: C.INK, radius: 0.1, border: null, bodyColor: C.MUTED };
const HERO_PANEL = { from: C.NAVY, to: "1E5C74", angle: 135 };   // once per video, full bleed
const STRIP = { fill: C.WARN_BG, border: C.WARN_LINE, text: C.WARN_TXT, radius: 0.07, italic: true, align: "center" };
const GLOW = { corner: "bottom-right", layers: [[C.AQUA, 38], [C.WARN, 22]] };
const TICKBAR = { w: 0.07, h: 0.36, color: C.BLUE, radius: 0.02, gap: 0.16 };
const PROGRESS = { track: C.MIST_2, fill: [C.AQUA, C.TEAL] };

// chain block colours — whose step it is
const CHAIN = { bs: C.BLUE, team: C.TEAL, customer: C.INK, sale: C.GREEN, chevron: C.AQUA, radius: 0.09 };
// split-bar segment kinds
const SEG = { nobody: { fill: C.MIST_2, text: C.BODY }, bs: { fill: C.BLUE, text: C.WHITE }, keep: { fill: C.GREEN, text: C.WHITE } };
// pills
const PILL = {
  warn:   { fill: C.WARN_BG, text: C.WARN_TXT, border: C.WARN_LINE },
  blue:   { fill: C.BLUE,    text: C.WHITE,    border: null },
  green:  { fill: C.GREEN,   text: C.WHITE,    border: null },
  mist:   { fill: C.MIST,    text: C.BLUE,     border: C.BORDER_2 },
  white:  { fill: C.WHITE,   text: C.BLUE,     border: C.BORDER_2 },
  whiteG: { fill: C.WHITE,   text: C.GREEN_D,  border: C.BORDER_G },
  grey:   { fill: C.MIST_2,  text: C.SUB,      border: C.BORDER_2 },
};

// motion grammar — v1 restored. Names double as pptxgenjs objectName prefixes.
const MOTION = {
  M1_SETTLE: { effect: "floatIn", dir: "fromBottom", dur: 0.4 },
  M2_DRAW:   { effect: "wipe", dir: "fromLeft", dur: 0.5 },
  M3_POP:    { effect: "zoom", dir: "center", dur: 0.3 },
  M4_VERIFY: { effect: "zoom", dir: "center", dur: 0.25, then: { effect: "pulse", dur: 0.25 } },
  M5_FLOW:   { base: "M3_POP", stagger: 0.15 },
  M6_GROW:   { effect: "wipe", dir: "fromBottom", dur: 0.6, then: "M1_SETTLE" },
  TRANSITION: { effect: "fade", dur: 0.5 },
  TRANSITION_GATE: { effect: "push", dir: "fromBottom", dur: 0.6 },
};
const COMPONENT_MOTION = {
  tickbar: "M2_DRAW", headline: "M1_SETTLE", sub: "M1_SETTLE", label: "M1_SETTLE", row: "M1_SETTLE", footer: "M1_SETTLE",
  splitBar: "M2_DRAW", ruler: "M2_DRAW", chevron: "M2_DRAW", meter: "M2_DRAW",
  circle: "M3_POP", chain: "M5_FLOW", laneRows: "M5_FLOW", funnel: "M5_FLOW",
  stack: "M6_GROW", routeBar: "M6_GROW",
  darkCard: "M1_SETTLE", tick: "M4_VERIFY", pill: "M4_VERIFY", badge: "M4_VERIFY",
};

module.exports = {
  C, F, T, CIRCLE, GLYPH_RATIO, STROKE, CIRCLE_D, ICON, ICON_LANE,
  CARD, DARK_CARD, HERO_PANEL, STRIP, GLOW, TICKBAR, PROGRESS,
  CHAIN, SEG, PILL, MOTION, COMPONENT_MOTION,
};
