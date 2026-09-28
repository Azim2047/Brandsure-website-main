/*
 * BrandSure v2 — front-end behaviour.
 *
 * Vanilla JavaScript, no framework, no jQuery, no build step. Everything here
 * is progressive: with JavaScript off, the nav is open, every link works and
 * every CTA still reaches WhatsApp. Only the measurement is lost.
 *
 * Contents: the mobile nav, the visitor session code, the event beacon, the
 * gate's live WhatsApp link, and the tap-to-load video player.
 */

(function () {
	'use strict';

	var config = window.BrandSureConfig || {};

	/* ---------------------------------------------------------------------
	 * Mobile navigation
	 *
	 * The panel is hidden with the hidden attribute rather than a class, so
	 * it leaves the tab order and the accessibility tree as well as the
	 * screen. Above the breakpoint the attribute is removed entirely: the nav
	 * is simply part of the header, not a collapsed panel that CSS happens to
	 * be showing.
	 * ------------------------------------------------------------------ */

	var DESKTOP = window.matchMedia('(min-width: 56rem)');
	var header = document.querySelector('[data-bs-header]');
	var toggle = document.querySelector('[data-bs-nav-toggle]');
	var nav = document.getElementById('bs-nav');

	// Until this class is set, the nav is an ordinary list in the flow and the
	// toggle is hidden — so a visitor with no JavaScript gets a working menu
	// rather than a button that does nothing or a panel over the hero.
	if (header && toggle && nav) header.classList.add('is-enhanced');

	function closeNav() {
		if (!toggle || !nav) return;
		toggle.setAttribute('aria-expanded', 'false');
		nav.hidden = true;
	}

	function openNav() {
		if (!toggle || !nav) return;
		toggle.setAttribute('aria-expanded', 'true');
		nav.hidden = false;
	}

	function syncNav() {
		if (!toggle || !nav) return;

		if (DESKTOP.matches) {
			nav.hidden = false;
			toggle.setAttribute('aria-expanded', 'false');
		} else if (toggle.getAttribute('aria-expanded') !== 'true') {
			nav.hidden = true;
		}
	}

	if (toggle && nav) {
		syncNav();

		toggle.addEventListener('click', function () {
			if (toggle.getAttribute('aria-expanded') === 'true') {
				closeNav();
			} else {
				openNav();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && !DESKTOP.matches) {
				closeNav();
				toggle.focus();
			}
		});

		// Tapping a link in the open panel should close it, otherwise an
		// in-page anchor leaves the panel covering what it jumped to.
		nav.addEventListener('click', function (event) {
			if (event.target.closest('a') && !DESKTOP.matches) closeNav();
		});

		if (DESKTOP.addEventListener) {
			DESKTOP.addEventListener('change', syncNav);
		} else if (DESKTOP.addListener) {
			DESKTOP.addListener(syncNav); // Safari < 14
		}
	}

	/* ---------------------------------------------------------------------
	 * Visitor session code (bs_vid)
	 *
	 * A random first-party value, nothing derived from the visitor. It exists
	 * to stitch this browsing session to the lead record n8n creates when the
	 * owner sends the WhatsApp message, so the same person is not counted
	 * twice and their route is known. See docs/EVENTS-AND-INTEGRATION.md §4.
	 * ------------------------------------------------------------------ */

	function readCookie(name) {
		var match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
		return match ? decodeURIComponent(match[2]) : '';
	}

	function writeCookie(name, value, days) {
		var expires = new Date(Date.now() + days * 864e5).toUTCString();
		document.cookie = name + '=' + encodeURIComponent(value) +
			';expires=' + expires +
			';path=/;SameSite=Lax' +
			(location.protocol === 'https:' ? ';Secure' : '');
	}

	function randomId() {
		var alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
		var bytes = new Uint8Array(10);
		var out = '';

		if (window.crypto && window.crypto.getRandomValues) {
			window.crypto.getRandomValues(bytes);
			for (var i = 0; i < bytes.length; i++) out += alphabet[bytes[i] % alphabet.length];
			return out;
		}

		// No crypto (very old browser). A weaker id still stitches correctly;
		// it is a correlation key, not a secret.
		for (var j = 0; j < 10; j++) out += alphabet[Math.floor(Math.random() * alphabet.length)];
		return out;
	}

	function sessionId() {
		var name = config.cookie || 'bs_vid';
		var existing = readCookie(name);

		if (existing) return existing;

		var fresh = randomId();
		writeCookie(name, fresh, config.days || 180);
		return fresh;
	}

	/* ---------------------------------------------------------------------
	 * Events
	 *
	 * Sent with sendBeacon, matching the transport the existing funnel
	 * landing page already uses. Never a name, a phone number, an email, or
	 * any of the four gate numbers — those travel only inside the WhatsApp
	 * message the owner sends from their own device.
	 *
	 * With no endpoint configured this collects nothing and posts nothing.
	 * ------------------------------------------------------------------ */

	var allowed = config.events || [];
	var sent = {};

	function track(name, meta) {
		if (!config.endpoint) return;
		if (allowed.indexOf(name) === -1) return;

		var payload = {
			source: config.source || 'website',
			event: name,
			session_id: sessionId(),
			path: config.path || location.pathname,
			ts: new Date().toISOString()
		};

		if (meta && typeof meta === 'object') payload.meta = meta;

		try {
			var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });

			if (navigator.sendBeacon) {
				navigator.sendBeacon(config.endpoint, blob);
			} else {
				fetch(config.endpoint, { method: 'POST', body: blob, keepalive: true, mode: 'no-cors' });
			}
		} catch (error) {
			// Measurement must never break a page or block a CTA.
		}
	}

	/**
	 * Fire an event at most once per page view. Scroll and view events would
	 * otherwise repeat on every scroll tick and drown the real signal.
	 */
	function trackOnce(name, meta) {
		if (sent[name]) return;
		sent[name] = true;
		track(name, meta);
	}

	// Exposed so the page scripts built in later chats use this one
	// implementation rather than each rolling their own beacon.
	window.BrandSure = { track: track, trackOnce: trackOnce, sessionId: sessionId };

	/* ---------------------------------------------------------------------
	 * The WhatsApp panel
	 *
	 * A bottom sheet on phones, a corner panel on desktop, one piece of
	 * markup. Everything below is enhancement: the launchers are real wa.me
	 * links and every option is a real wa.me link, so with this file blocked
	 * the panel is a plain list at the end of the page and the launcher goes
	 * straight to WhatsApp. Nothing here is load-bearing.
	 *
	 * The panel is NOT a chat window and this code never pretends otherwise:
	 * no typing indicator, no fake reply, no unread badge, no timer that
	 * promises a response.
	 * ------------------------------------------------------------------ */

	var waRoot = document.querySelector('[data-bs-wa]');

	if (waRoot) (function () {
		var panel = waRoot.querySelector('[data-bs-wa-panel]');
		var scrim = waRoot.querySelector('[data-bs-wa-scrim]');
		var invite = waRoot.querySelector('[data-bs-wa-invite]');
		var lastFocus = null;
		var scrollY = 0;
		var open = false;

		if (!panel || !scrim) return;

		// Only now is the panel a dialog rather than a list. The class gates
		// every style that would hide content from a scriptless visitor, and
		// these attributes are the same idea in the accessibility tree: the
		// markup does not claim to be a modal dialog until it is one.
		waRoot.classList.add('is-enhanced');
		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.hidden = true;
		scrim.hidden = true;

		var FOCUSABLE = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

		function focusables() {
			return Array.prototype.filter.call(
				panel.querySelectorAll(FOCUSABLE),
				function (el) { return el.offsetParent !== null || el === document.activeElement; }
			);
		}

		/**
		 * Lock the page behind the sheet.
		 *
		 * position:fixed rather than overflow:hidden, because iOS Safari
		 * scrolls the body anyway under overflow:hidden and the reader loses
		 * their place. The scroll offset is restored exactly on close —
		 * getting that wrong dumps somebody at the top of a long page, which
		 * is worse than not locking at all.
		 */
		function lockScroll() {
			scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
			document.body.style.top = '-' + scrollY + 'px';
			document.body.classList.add('bs-scroll-locked');
		}

		function unlockScroll() {
			document.body.classList.remove('bs-scroll-locked');
			document.body.style.top = '';

			// Instant, explicitly. The page sets scroll-behavior: smooth, so a
			// plain scrollTo animates the restore — the page visibly glides
			// back to where the reader already was, and a touch mid-glide
			// cancels it and strands them somewhere else. Restoring position
			// is not navigation and should not look like it.
			try {
				window.scrollTo({ top: scrollY, left: 0, behavior: 'instant' });
			} catch (error) {
				// Older browsers reject 'instant'; they also have no smooth
				// scrolling to fight, so the plain form is correct there.
				window.scrollTo(0, scrollY);
			}
		}

		function showStep(name) {
			var steps = panel.querySelectorAll('[data-bs-wa-step]');

			Array.prototype.forEach.call(steps, function (step) {
				step.hidden = step.getAttribute('data-bs-wa-step') !== name;
			});

			var first = panel.querySelector('[data-bs-wa-step="' + name + '"] ' + FOCUSABLE);
			if (first) first.focus();
		}

		function openPanel(source) {
			if (open) return;
			open = true;
			lastFocus = document.activeElement;

			dismissInvite(false);
			showStep('root');

			scrim.hidden = false;
			panel.hidden = false;

			// Two frames: the element has to be laid out before the class that
			// transitions it is added, or it arrives already in place.
			requestAnimationFrame(function () {
				requestAnimationFrame(function () { waRoot.classList.add('is-open'); });
			});

			lockScroll();

			var heading = panel.querySelector('.bs-wa__heading');
			if (heading) {
				heading.setAttribute('tabindex', '-1');
				heading.focus();
			}

			track('whatsapp_panel_open', { source: source || 'unknown' });
		}

		function closePanel() {
			if (!open) return;
			open = false;
			waRoot.classList.remove('is-open');

			var finish = function () {
				panel.hidden = true;
				scrim.hidden = true;
			};

			// Wait for the close transition, but never trust it to fire: a
			// panel that stays open because transitionend was missed traps the
			// reader behind a scrim.
			var done = false;
			var settle = function () {
				if (done) return;
				done = true;
				finish();
			};

			panel.addEventListener('transitionend', settle, { once: true });
			setTimeout(settle, 400);

			unlockScroll();

			if (lastFocus && lastFocus.focus) lastFocus.focus();
		}

		/* -- the launchers ------------------------------------------------ */

		document.addEventListener('click', function (event) {
			var launcher = event.target.closest('[data-bs-wa-open]');

			if (launcher) {
				// The href is the no-script path. With script, the tap opens
				// the panel instead of leaving the site immediately.
				event.preventDefault();
				track('whatsapp_launcher_click', { source: launcher.getAttribute('data-bs-wa-open') });
				openPanel(launcher.getAttribute('data-bs-wa-open'));
				return;
			}

			if (event.target.closest('[data-bs-wa-close]') || event.target.closest('[data-bs-wa-scrim]')) {
				event.preventDefault();
				closePanel();
				return;
			}

			var step = event.target.closest('[data-bs-wa-step-to]');

			if (step && panel.contains(step)) {
				event.preventDefault();
				showStep(step.getAttribute('data-bs-wa-step-to'));
				return;
			}

			var choice = event.target.closest('[data-bs-wa-choice]');

			if (choice && panel.contains(choice)) {
				// Not prevented: this one really does leave for WhatsApp.
				var id = choice.getAttribute('data-bs-wa-choice');
				track('whatsapp_option_select', { option: id });
				track('whatsapp_outbound_click', { option: id, placement: 'panel' });
			}
		});

		/* -- keyboard ----------------------------------------------------- */

		document.addEventListener('keydown', function (event) {
			if (!open) return;

			if (event.key === 'Escape') {
				event.preventDefault();
				closePanel();
				return;
			}

			if (event.key !== 'Tab') return;

			// Keep Tab inside the dialog. Without this the next Tab lands on
			// the page behind the scrim, where nothing is visible and nothing
			// can be clicked.
			var items = focusables();
			if (!items.length) return;

			var first = items[0];
			var last = items[items.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});

		/* -- the one-time invitation --------------------------------------
		 *
		 * Shown once, after the reader has actually engaged — well down the
		 * page, or a while into it. Never on load, never over the thing being
		 * read, and once refused it stays refused for the browsing session.
		 * sessionStorage rather than a cookie: this is a UI preference, not
		 * something anybody should be tracked by.
		 * -------------------------------------------------------------- */

		var INVITE_KEY = 'bs-wa-invite-dismissed';

		function inviteDismissed() {
			try { return sessionStorage.getItem(INVITE_KEY) === '1'; } catch (error) { return false; }
		}

		function dismissInvite(remember) {
			if (!invite) return;
			invite.hidden = true;
			waRoot.classList.remove('has-invite');

			if (remember) {
				try { sessionStorage.setItem(INVITE_KEY, '1'); } catch (error) { /* private mode */ }
			}
		}

		if (invite && !inviteDismissed()) {
			var inviteCfg = (config.waInvite || {});
			var needScroll = typeof inviteCfg.scroll === 'number' ? inviteCfg.scroll : 55;
			var needSeconds = typeof inviteCfg.seconds === 'number' ? inviteCfg.seconds : 45;
			var shown = false;

			var showInvite = function () {
				if (shown || open || inviteDismissed()) return;
				shown = true;
				invite.hidden = false;
				waRoot.classList.add('has-invite');
			};

			var onScroll = function () {
				var doc = document.documentElement;
				var height = doc.scrollHeight - doc.clientHeight;
				if (height <= 0) return;

				var percent = ((window.pageYOffset || doc.scrollTop) / height) * 100;
				if (percent >= needScroll) showInvite();
			};

			window.addEventListener('scroll', onScroll, { passive: true });
			setTimeout(showInvite, needSeconds * 1000);

			document.addEventListener('click', function (event) {
				if (event.target.closest('[data-bs-wa-invite-dismiss]')) {
					event.preventDefault();
					dismissInvite(true);
				}
			});
		}
	})();

	/* ---------------------------------------------------------------------
	 * Motion
	 *
	 * The .bs-motion class on <html> is what switches every animated "before"
	 * state on. Nothing in the stylesheet animates without it, so a visitor
	 * whose script is blocked, slow or broken gets the finished page rather
	 * than a page of invisible sections waiting for something that will never
	 * run. That is why it is a class set from here and not a state written
	 * into the markup.
	 *
	 * WHY A SWEEP AND NOT AN IntersectionObserver. The first version used one,
	 * and it left content permanently invisible. An observer reports
	 * intersection *changes*: jump from the top of the page to the bottom in
	 * one frame — an anchor link, a restored scroll position, a flick on a
	 * long page — and every section in between goes from below the viewport
	 * to above it without ever intersecting. No callback, no reveal, opacity
	 * nailed at 0. Eleven of twelve sections on the home page were reproduced
	 * blank this way.
	 *
	 * A sweep asks a different and safer question: has this element's top
	 * passed the bottom of the viewport yet? That is true whether it was
	 * scrolled to, jumped past, or was on screen from the start. Each target
	 * is dropped from the list as it reveals, and when the list empties the
	 * scroll listener removes itself, so the steady-state cost is nothing.
	 * ------------------------------------------------------------------ */

	(function () {
		var REVEAL = [
			'.bs-section > .bs-container > *',
			'.bs-chain__list',
			'.bs-plans__grid',
			'.bs-figures__grid',
			'.bs-postcard'
		];

		var root = document.documentElement;
		root.classList.add('bs-motion');

		// The hero is already on screen, so it plays on load rather than
		// waiting to be scrolled to. Its children are one wrapper deep — the
		// stagger has to reach the headline, the sentence and the buttons, not
		// the single box around them.
		var heroInner = document.querySelector('.bs-hero__inner') ||
			document.querySelector('.bs-hero .bs-container');

		if (heroInner) {
			Array.prototype.forEach.call(heroInner.children, function (child) {
				child.setAttribute('data-bs-hero', '');
			});
		}

		var pending = [];

		REVEAL.forEach(function (selector) {
			Array.prototype.forEach.call(document.querySelectorAll(selector), function (el) {
				if (el.closest('.bs-hero')) return;      // the hero has its own entrance
				if (el.closest('[data-bs-wa]')) return;  // the panel is not a section
				if (el.hasAttribute('data-bs-reveal')) return;

				el.setAttribute('data-bs-reveal', '');
				pending.push(el);
			});
		});

		var ticking = false;

		function sweep() {
			ticking = false;

			var edge = window.innerHeight * 0.92;
			var still = [];

			for (var i = 0; i < pending.length; i++) {
				if (pending[i].getBoundingClientRect().top < edge) {
					pending[i].classList.add('is-revealed');
				} else {
					still.push(pending[i]);
				}
			}

			pending = still;

			if (!pending.length) {
				window.removeEventListener('scroll', onScroll);
				window.removeEventListener('resize', onScroll);
			}
		}

		function onScroll() {
			if (ticking) return;
			ticking = true;
			requestAnimationFrame(sweep);
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll, { passive: true });
		sweep();

		/*
		 * A last resort, and it costs nothing to keep. If anything at all stops
		 * the sweep running, everything is shown anyway. No effect on this site
		 * is worth leaving a sentence invisible for.
		 */
		setTimeout(function () {
			Array.prototype.forEach.call(
				document.querySelectorAll('[data-bs-reveal]'),
				function (el) { el.classList.add('is-revealed'); }
			);
		}, 1500);
	})();

	/* ---------------------------------------------------------------------
	 * The sticky header's scrolled state
	 * ------------------------------------------------------------------ */

	(function () {
		var el = document.querySelector('[data-bs-header]');

		if (!el) return;

		var stuck = false;

		var sync = function () {
			var now = (window.pageYOffset || document.documentElement.scrollTop) > 8;

			// Only touch the DOM when the state actually changes. This runs on
			// every scroll frame; writing a class that is already there is how
			// a scroll handler starts costing something.
			if (now === stuck) return;

			stuck = now;
			el.classList.toggle('is-stuck', stuck);
		};

		sync();
		window.addEventListener('scroll', sync, { passive: true });
	})();

	/* ---------------------------------------------------------------------
	 * Plan selection feedback
	 *
	 * The tap leaves for WhatsApp or another page, and on mobile data that
	 * gap can be over a second. Marking the card chosen says the tap landed.
	 * ------------------------------------------------------------------ */

	document.addEventListener('click', function (event) {
		var action = event.target.closest('.bs-plan .bs-btn');

		if (!action) return;

		var card = action.closest('.bs-plan');

		if (!card) return;

		Array.prototype.forEach.call(
			document.querySelectorAll('.bs-plan.is-choosing'),
			function (other) { other.classList.remove('is-choosing'); }
		);

		card.classList.add('is-choosing');
	});

	/* ---------------------------------------------------------------------
	 * Wiring
	 * ------------------------------------------------------------------ */

	trackOnce('page_view');

	// Any element can declare an event without a line of bespoke JavaScript:
	//   data-bs-event="gate_whatsapp_click" data-bs-meta='{"plan":"Agency"}'
	document.addEventListener('click', function (event) {
		var el = event.target.closest('[data-bs-event]');
		if (!el) return;

		var meta;

		try {
			meta = el.dataset.bsMeta ? JSON.parse(el.dataset.bsMeta) : undefined;
		} catch (error) {
			meta = undefined;
		}

		track(el.dataset.bsEvent, meta);
	});

	// Any element can declare an event that fires when it is actually looked
	// at: data-bs-view="plan_viewed". It fires once, at half visible, which is
	// the difference between "the cards were on the page" and "the cards were
	// read". Without IntersectionObserver nothing fires — an old browser
	// losing a scroll measurement is not worth a scroll handler.
	if (window.IntersectionObserver) {
		var seen = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				trackOnce(entry.target.dataset.bsView);
				seen.unobserve(entry.target);
			});
		}, { threshold: 0.5 });

		[].forEach.call(document.querySelectorAll('[data-bs-view]'), function (el) {
			seen.observe(el);
		});
	}

	// The referral entry point. Reading it here rather than server-side keeps
	// the page cacheable.
	if (location.pathname.indexOf('/built-by-brandsure') === 0) {
		var ref = new URLSearchParams(location.search).get('ref');
		if (ref) trackOnce('builtby_view', { ref: ref.slice(0, 40) });
	}

	/* ---------------------------------------------------------------------
	 * The gate
	 *
	 * The send control is a link whose href is already valid when the page
	 * renders. This only rewrites it as the owner types, so with JavaScript
	 * off the button still opens WhatsApp — without the four numbers, which
	 * the AI then asks for. Nothing is POSTed: the numbers reach BrandSure
	 * only inside the message the owner sends from their own device.
	 * ------------------------------------------------------------------ */

	var gate = document.querySelector('[data-bs-gate]');

	if (gate) {
		var send = gate.querySelector('[data-bs-gate-send]');
		var inputs = [].slice.call(gate.querySelectorAll('[data-bs-gate-field]'));
		var number = gate.dataset.bsGateNumber || '';
		var template = gate.dataset.bsGateTemplate || '';

		/**
		 * The self-selected route, from the query string.
		 *
		 * Override 3, enforced here as well as in PHP: Growth Partnership can
		 * only ever travel as an interest. If someone arrives on a hand-typed
		 * or stale link that pairs it with the plan parameter, it is rewritten
		 * rather than passed through — the plan parameter reads to the AI as a
		 * self-selected route, and Growth Partnership is never self-selected.
		 */
		function routeLine() {
			var params = new URLSearchParams(location.search);
			var plan = params.get('plan');
			var interest = params.get('interest');

			if (plan && /growth\s*partnership/i.test(plan)) {
				interest = plan;
				plan = null;
			}

			if (interest) return 'Interested in: ' + interest;
			if (plan) return 'Plan: ' + plan;
			return '';
		}

		function buildMessage() {
			var values = {};
			inputs.forEach(function (input) {
				values[input.dataset.bsGateField] = input.value.trim();
			});

			values.route = routeLine();
			values.ref = sessionId();

			return template
				.split('\n')
				.map(function (line) {
					return line.replace(/\{([a-z_]+)\}/g, function (whole, key) {
						return values[key] != null ? values[key] : '';
					});
				})
				// Drop any line whose placeholder came back empty, so nobody
				// receives "Showrooms:" with nothing after it.
				.filter(function (line) {
					var trimmed = line.trim();
					if (!trimmed) return false;
					return !/:\s*$/.test(trimmed);
				})
				.join('\n');
		}

		function refresh() {
			if (!send || !number) return;
			send.href = 'https://wa.me/' + encodeURIComponent(number) +
				'?text=' + encodeURIComponent(buildMessage());
		}

		var filledFired = false;

		function onInput() {
			refresh();

			// The friction point worth measuring is how many people fill all
			// four and still do not send.
			if (!filledFired && inputs.length && inputs.every(function (i) { return i.value.trim() !== ''; })) {
				filledFired = true;
				track('gate_field_filled');
			}
		}

		inputs.forEach(function (input) {
			input.addEventListener('input', onInput);
			input.addEventListener('change', onInput);
		});

		refresh();
		trackOnce('start_gate_open');

		// The click event carries the route so the mix of self-selected plans
		// is visible without reading message bodies.
		if (send) {
			send.addEventListener('click', function () {
				var params = new URLSearchParams(location.search);
				var meta = {};
				var plan = params.get('plan');
				var interest = params.get('interest');

				if (plan && /growth\s*partnership/i.test(plan)) { interest = plan; plan = null; }
				if (plan) meta.plan = plan;
				if (interest) meta.interest = interest;

				send.dataset.bsMeta = JSON.stringify(meta);
			});
		}
	}

	/* ---------------------------------------------------------------------
	 * Video — tap to load, then retention
	 *
	 * Nothing from YouTube is requested until the visitor presses play. On a
	 * 4G phone the iframe and its scripts cost more than the rest of the
	 * page put together, and most visitors never press it.
	 *
	 * Retention is the one measurement worth the effort here: where Video 1
	 * loses people is the single most useful thing the site can learn, and
	 * it is why the quarter marks exist as separate events.
	 * ------------------------------------------------------------------ */

	var YT_API = 'https://www.youtube.com/iframe_api';
	var apiRequested = false;
	var apiReady = false;
	var pending = [];

	function loadApi() {
		if (apiRequested) return;
		apiRequested = true;

		var tag = document.createElement('script');
		tag.src = YT_API;
		document.head.appendChild(tag);
	}

	// The YouTube API insists on calling a global.
	window.onYouTubeIframeAPIReady = function () {
		apiReady = true;
		pending.forEach(function (fn) { fn(); });
		pending = [];
	};

	function whenApiReady(fn) {
		if (apiReady) { fn(); return; }
		pending.push(fn);
		loadApi();
	}

	/**
	 * Replace a poster with a real player and report progress.
	 *
	 * @param {HTMLElement} poster The tapped poster button.
	 */
	function mountPlayer(poster) {
		var videoId = poster.dataset.bsVideo;
		var key = poster.dataset.bsVideoKey || 'video1';

		if (!videoId) return;

		var mount = document.createElement('div');
		mount.className = 'bs-video__frame';
		poster.replaceWith(mount);

		whenApiReady(function () {
			var marks = { 25: false, 50: false, 75: false };
			var done = false;
			var timer;

			var player = new window.YT.Player(mount, {
				videoId: videoId,
				playerVars: { rel: 0, modestbranding: 1, playsinline: 1 },
				events: {
					onReady: function (event) {
						event.target.playVideo();
						track(key + '_start');
					},
					onStateChange: function (event) {
						var YTS = window.YT.PlayerState;

						if (event.data === YTS.PLAYING) {
							clearInterval(timer);
							timer = setInterval(function () {
								var total = player.getDuration();
								if (!total) return;

								var percent = (player.getCurrentTime() / total) * 100;

								[25, 50, 75].forEach(function (mark) {
									if (!marks[mark] && percent >= mark) {
										marks[mark] = true;
										track(key + '_' + mark);
									}
								});
							}, 1000);
						} else {
							clearInterval(timer);
						}

						if (event.data === YTS.ENDED && !done) {
							done = true;
							track(key + '_complete');
						}
					}
				}
			});
		});
	}

	document.addEventListener('click', function (event) {
		var poster = event.target.closest('[data-bs-video]');
		if (poster) mountPlayer(poster);
	});
})();
