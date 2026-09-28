/* BrandSure — scroll reveal, loaded on every public page.

   The .rv class starts at opacity:0 and needs .in to become visible, so if the
   script that adds it never runs the section is invisible rather than merely
   un-animated. Pages whose body was pasted in as markup carry that script
   inline, where a single escaped character breaks the whole file — so the
   observer lives here as well, in a real .js file that cannot be rewritten by
   the editor.

   Adding .in twice is harmless, so this runs alongside an inline copy without
   conflicting, and it keeps the animation instead of flashing everything in. */
(function () {
  "use strict";

  function reveal() {
    var items = [].slice.call(document.querySelectorAll(".rv:not(.in)"));
    if (!items.length) return;

    if (!("IntersectionObserver" in window)) {
      items.forEach(function (el) {
        el.classList.add("in");
      });
      return;
    }

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("in");
          io.unobserve(entry.target);
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.15 }
    );

    items.forEach(function (el, i) {
      if (!el.style.transitionDelay) {
        el.style.transitionDelay = Math.min(i % 5, 4) * 50 + "ms";
      }
      io.observe(el);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", reveal);
  } else {
    reveal();
  }

  // Anything still hidden well after load never got an observer — show it.
  window.addEventListener("load", function () {
    window.setTimeout(function () {
      [].slice.call(document.querySelectorAll(".rv:not(.in)")).forEach(function (el) {
        var box = el.getBoundingClientRect();
        if (box.top < window.innerHeight && box.bottom > 0) {
          el.classList.add("in");
        }
      });
    }, 1200);
  });
})();
