/* BrandSure — global header behaviour.
   Dropdown and mobile panel with correct aria-expanded, keyboard support,
   Escape to close, outside-click to close, and nothing left open across a
   navigation. No library. */
(function () {
  "use strict";

  var nav = document.getElementById("bsnav");
  if (!nav) return;

  var burger = document.getElementById("bsnav-burger");
  var dropdowns = [].slice.call(nav.querySelectorAll("[data-bsnav-dd]"));
  var mobileSecs = [].slice.call(nav.querySelectorAll(".bsnav__msec"));

  /* ---- light/dark on scroll ----
     Pages rendered light from the server stay light; only a page with the dark
     full-bleed hero toggles, so nav text is never white on white. */
  var staticLight = nav.getAttribute("data-static-light") === "1";
  if (!staticLight) {
    var onScroll = function () {
      nav.classList.toggle("is-light", window.scrollY > 40);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  /* ---- desktop dropdown ---- */
  function closeDropdowns(except) {
    dropdowns.forEach(function (dd) {
      if (dd === except) return;
      dd.classList.remove("is-open");
      var btn = dd.querySelector(".bsnav__ddbtn");
      if (btn) btn.setAttribute("aria-expanded", "false");
    });
  }

  dropdowns.forEach(function (dd) {
    var btn = dd.querySelector(".bsnav__ddbtn");
    if (!btn) return;

    btn.addEventListener("click", function () {
      var open = !dd.classList.contains("is-open");
      closeDropdowns(dd);
      dd.classList.toggle("is-open", open);
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });

    // Down-arrow opens and moves into the menu, as a menu button should.
    btn.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        dd.classList.add("is-open");
        btn.setAttribute("aria-expanded", "true");
        var first = dd.querySelector(".bsnav__ddmenu a");
        if (first) first.focus();
      }
    });

    // Leaving the dropdown by keyboard closes it.
    dd.addEventListener("focusout", function (e) {
      if (!dd.contains(e.relatedTarget)) {
        dd.classList.remove("is-open");
        btn.setAttribute("aria-expanded", "false");
      }
    });
  });

  /* ---- mobile panel ---- */
  function closeMobile() {
    nav.classList.remove("is-open");
    if (burger) {
      burger.setAttribute("aria-expanded", "false");
      burger.setAttribute("aria-label", "Open menu");
    }
  }

  if (burger) {
    burger.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", open ? "true" : "false");
      burger.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    });
  }

  mobileSecs.forEach(function (sec) {
    var btn = sec.querySelector(".bsnav__mbtn");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var open = sec.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });

  /* ---- global close behaviour ---- */
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    var openDd = nav.querySelector("[data-bsnav-dd].is-open");
    if (openDd) {
      var btn = openDd.querySelector(".bsnav__ddbtn");
      closeDropdowns(null);
      if (btn) btn.focus();
      return;
    }
    if (nav.classList.contains("is-open")) {
      closeMobile();
      if (burger) burger.focus();
    }
  });

  document.addEventListener("click", function (e) {
    if (nav.contains(e.target)) return;
    closeDropdowns(null);
    closeMobile();
  });

  // A same-page (hash) link does not reload, so close the panel by hand.
  nav.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest("a");
    if (!a) return;
    closeDropdowns(null);
    closeMobile();
  });

  // Returning via the back/forward cache must not restore an open menu.
  window.addEventListener("pageshow", function () {
    closeDropdowns(null);
    closeMobile();
  });
})();
