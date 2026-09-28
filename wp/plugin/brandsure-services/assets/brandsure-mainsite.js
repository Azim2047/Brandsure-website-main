/* BrandSure — behaviour for the services hub, how-it-works and pricing pages.

   This is the inline script those static pages carried, minus the navigation
   handling (the global header owns that now) and minus the scroll reveal
   (brandsure-reveal.js runs on every page). Every block exits early when its
   markup is absent, so one file serves all three pages.

   Contains, unchanged: the sticky WhatsApp pill, the WhatsApp dataLayer
   tracking, the plan finder and the worth-it calculator. */
(function () {
  "use strict";

  window.dataLayer = window.dataLayer || [];
  var cfg = window.BrandSureMain || {};

  /* ---- sticky WhatsApp pill ---- */
  (function () {
    var pill = document.getElementById("wapill");
    if (!pill) return;
    var hero = document.querySelector(".pagehero") || document.querySelector(".hero");
    var ft = document.getElementById("ft") || document.querySelector("footer");
    var inputFocused = false;

    function update() {
      var heroBottom = hero ? hero.getBoundingClientRect().bottom : -1;
      var footerTop = ft ? ft.getBoundingClientRect().top : Number.MAX_VALUE;
      pill.classList.toggle(
        "is-on",
        heroBottom < 0 && footerTop >= window.innerHeight * 0.5 && !inputFocused
      );
    }

    window.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update, { passive: true });
    document.addEventListener("focusin", function (e) {
      if (e.target.matches && e.target.matches("input,textarea,select")) {
        inputFocused = true;
        update();
      }
    });
    document.addEventListener("focusout", function () {
      inputFocused = false;
      window.setTimeout(update, 150);
    });
    update();
  })();

  /* ---- WhatsApp click tracking (dataLayer) ---- */
  (function () {
    // A page that still carries its own inline copy of this tracking must not
    // report every click twice.
    if (window.__brandsureWaTracking) return;
    window.__brandsureWaTracking = true;

    var q = new URLSearchParams(location.search);

    function source() {
      var u = q.get("utm_source");
      if (u) return u + (q.get("utm_medium") ? " / " + q.get("utm_medium") : "");
      if (q.get("gclid")) return "google / cpc";
      if (!document.referrer) return "direct";
      try {
        return new URL(document.referrer).hostname.replace(/^www\./, "");
      } catch (e) {
        return "referral";
      }
    }

    document.addEventListener(
      "click",
      function (e) {
        var a = e.target.closest && e.target.closest('a[href*="wa.me"]');
        if (!a) return;
        window.dataLayer.push({
          event: "whatsapp_click",
          page_service: cfg.service || document.body.dataset.pageService,
          page_slug: cfg.slug || document.body.dataset.pageSlug,
          cta_position: a.dataset.cta || "unknown",
          device: window.matchMedia("(min-width:760px)").matches ? "desktop" : "mobile",
          traffic_source: source(),
          campaign: q.get("utm_campaign") || null
        });
      },
      true
    );
  })();

  /* ---- scroll depth (homepage) ---- */
  (function () {
    if (cfg.slug !== "start") return;
    var marks = [25, 50, 75, 100];
    var fired = {};
    window.addEventListener(
      "scroll",
      function () {
        var depth = Math.round(
          ((window.scrollY + window.innerHeight) /
            document.documentElement.scrollHeight) *
            100
        );
        marks.forEach(function (m) {
          if (depth >= m && !fired[m]) {
            fired[m] = true;
            window.dataLayer.push({
              event: "scroll_depth",
              percent: m,
              page_slug: "home"
            });
          }
        });
      },
      { passive: true }
    );
  })();

  /* ---- callback request form ----
     The static page validated this form, logged the values to the console and
     showed the success panel: a real enquiry went nowhere. It now posts to the
     plugin's endpoint, which stores it under Enquiries and emails it. The
     success panel only appears once the request has actually been accepted. */
  (function () {
    var form = document.getElementById("cbform");
    if (!form) return;

    var fields = document.getElementById("cbfields");
    var done = document.getElementById("cbdone");
    var button = form.querySelector('button[type="submit"]');
    var endpoint = cfg.endpoint;
    var error = null;

    function showError(message) {
      if (!error) {
        error = document.createElement("p");
        error.setAttribute("role", "alert");
        error.style.cssText =
          "margin-top:12px;font-size:14px;color:#B4453F;line-height:1.5";
        form.appendChild(error);
      }
      error.textContent = message;
    }

    function clearError() {
      if (error) error.textContent = "";
    }

    function validate() {
      var ok = true;
      [].slice.call(form.querySelectorAll(".inp")).forEach(function (input) {
        var value = input.value.trim();
        var bad = value === "";
        if (!bad && input.type === "tel") {
          var digits = value.replace(/\D/g, "");
          if (digits.length < 10 || digits.length > 12) bad = true;
        }
        input.setAttribute("aria-invalid", bad ? "true" : "false");
        if (bad) ok = false;
      });
      return ok;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      clearError();

      if (!validate()) {
        var first = form.querySelector('[aria-invalid="true"]');
        if (first) first.focus();
        return;
      }

      var payload = {};
      new FormData(form).forEach(function (v, k) {
        payload[k] = v;
      });
      payload.source = location.href;
      payload.campaign = new URLSearchParams(location.search).get("utm_campaign") || "";

      if (!endpoint) {
        // Nothing to post to: say so rather than claiming the call is booked.
        showError(
          "Sorry — the form is not available right now. Please message us on WhatsApp instead."
        );
        return;
      }

      var label = button ? button.textContent : "";
      if (button) {
        button.disabled = true;
        button.textContent = "Sending…";
      }

      window
        .fetch(endpoint, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        })
        .then(function (response) {
          if (!response.ok) throw new Error("HTTP " + response.status);
          window.dataLayer.push({ event: "callback_request", page_slug: "home" });
          if (fields) fields.style.display = "none";
          if (done) done.classList.add("is-on");
        })
        .catch(function () {
          if (button) {
            button.disabled = false;
            button.textContent = label;
          }
          showError(
            "That did not go through. Please try again, or message us on WhatsApp."
          );
        });
    });
  })();

  /* ---- plan finder ---- */
  (function () {
    var fopts = document.getElementById("fopts");
    if (!fopts) return;
    var btns = [].slice.call(fopts.querySelectorAll(".fopt"));
    var panels = [].slice.call(document.querySelectorAll(".fres"));

    btns.forEach(function (b) {
      b.addEventListener("click", function () {
        var id = b.dataset.res;
        btns.forEach(function (o) {
          o.setAttribute("aria-pressed", o === b ? "true" : "false");
        });
        panels.forEach(function (p) {
          p.classList.toggle("is-on", p.id === id);
        });
        window.dataLayer.push({
          event: "plan_finder_answer",
          page_slug: "pricing",
          answer: id
        });
      });
    });
  })();

  /* ---- worth-it calculator ---- */
  (function () {
    var inputIds = ["c-aov", "c-margin", "c-visits", "c-close", "c-ads"];
    var outputIds = [
      "c-be", "c-be-l", "c-be-wk", "c-per", "c-cost", "c-need",
      "c-wif-v", "c-wif-c", "c-orders", "c-sales", "c-net", "c-net-row"
    ];

    var el = {};
    var ids = inputIds.concat(outputIds);
    for (var i = 0; i < ids.length; i++) {
      el[ids[i]] = document.getElementById(ids[i]);
      if (!el[ids[i]]) return; // not the pricing page, or the block was removed
    }

    var seg = document.getElementById("c-seg");
    var fee = 75000;

    function inr(n) {
      n = Math.round(n);
      var negative = n < 0;
      n = Math.abs(n);
      var s = String(n);
      var out = s.length > 3
        ? s.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ",") + "," + s.slice(-3)
        : s;
      return (negative ? "−₹" : "₹") + out;
    }

    function num(node, fallback) {
      var v = parseFloat(node.value);
      return isNaN(v) || v < 0 ? fallback : v;
    }

    function render() {
      var aov = num(el["c-aov"], 0);
      var margin = num(el["c-margin"], 0) / 100;
      var visits = num(el["c-visits"], 0);
      var closePct = num(el["c-close"], 0);
      var ads = num(el["c-ads"], 0);

      var closeRate = closePct / 100;
      var cost = fee + ads;
      var perOrder = aov * margin;
      var breakEven = perOrder > 0 ? Math.ceil(cost / perOrder) : 0;
      var need = margin > 0 ? cost / margin : 0;
      var orders = visits * closeRate;
      var sales = orders * aov;
      var net = sales * margin - cost;

      /* break-even block — no forecast, pure arithmetic */
      el["c-be"].textContent = breakEven > 0 ? breakEven : "—";
      el["c-be-l"].textContent = breakEven > 0
        ? (breakEven === 1
            ? "extra order a month covers the fee and the whole ad budget."
            : "extra orders a month cover the fee and the whole ad budget.")
        : "Enter your order value and margin to see the break-even.";

      if (breakEven > 0) {
        var weekly = breakEven / 4.33;
        el["c-be-wk"].textContent = weekly < 1
          ? "That is fewer than one a week."
          : "That is about " + Math.max(1, Math.round(weekly)) + " a week.";
      } else {
        el["c-be-wk"].textContent = "";
      }

      el["c-per"].textContent = inr(perOrder);
      el["c-cost"].textContent = inr(cost);
      el["c-need"].textContent = inr(need);

      /* what-if block — the owner's own estimate */
      el["c-wif-v"].textContent = Math.round(visits);
      el["c-wif-c"].textContent = Math.round(closePct);
      el["c-orders"].textContent = (Math.round(orders * 10) / 10).toString();
      el["c-sales"].textContent = inr(sales);
      el["c-net"].textContent = (net >= 0 ? "+" : "") + inr(Math.abs(net));
      if (el["c-net-row"].firstChild) {
        el["c-net-row"].firstChild.nodeValue = net >= 0
          ? "Ahead by, every month "
          : "Short by, every month ";
      }
    }

    inputIds.forEach(function (id) {
      el[id].addEventListener("input", render);
    });

    if (seg) {
      var segBtns = [].slice.call(seg.querySelectorAll("button"));
      segBtns.forEach(function (b) {
        b.addEventListener("click", function () {
          segBtns.forEach(function (o) {
            o.setAttribute("aria-pressed", o === b ? "true" : "false");
          });
          fee = parseFloat(b.dataset.fee) || 75000;
          render();
        });
      });
    }

    render();
  })();
})();
