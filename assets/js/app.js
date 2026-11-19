/* CarePoint Patient Portal - client-side interactions.
 * No inline scripts are used anywhere (the Content-Security-Policy blocks them).
 * User data is only ever written with textContent, never innerHTML. */
(function () {
    'use strict';

    var base = document.body.getAttribute('data-base') || '';

    // ---- Sidebar toggle (mobile) ----
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (toggle) {
        toggle.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { document.body.classList.remove('sidebar-open'); });
    }

    // ---- Public site menu toggle (mobile) ----
    var navToggle = document.getElementById('navToggle');
    if (navToggle) {
        navToggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.querySelector('i').className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        });
        document.querySelectorAll('#publicMenu a').forEach(function (a) {
            a.addEventListener('click', function () { document.body.classList.remove('nav-open'); });
        });
    }

    // ---- Scroll effects (public pages) ----
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (document.body.classList.contains('public')) {
        // Progress bar, nav shadow and back-to-top button
        var bar = document.createElement('div');
        bar.className = 'scroll-progress';
        document.body.appendChild(bar);
        var toTop = document.createElement('button');
        toTop.type = 'button';
        toTop.className = 'to-top';
        toTop.setAttribute('aria-label', 'Back to top');
        var arrow = document.createElement('i');
        arrow.className = 'fa-solid fa-arrow-up';
        toTop.appendChild(arrow);
        document.body.appendChild(toTop);
        toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); });

        var ticking = false;
        var onScroll = function () {
            var y = window.scrollY || document.documentElement.scrollTop;
            var max = document.documentElement.scrollHeight - window.innerHeight;
            bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(y / max, 1) : 0) + ')';
            document.body.classList.toggle('scrolled', y > 10);
            toTop.classList.toggle('show', y > 600);
            ticking = false;
        };
        window.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
        }, { passive: true });
        onScroll();

        // Reveal blocks as they scroll into view. Only elements that start BELOW the
        // visible screen are hidden, so nothing flickers on page load, and if JavaScript
        // is off everything simply shows normally.
        if (!reduceMotion && 'IntersectionObserver' in window) {
            var groups = [
                ['.section > .eyebrow, .section > h2, .section > .lead, .cta-band > *, .prose > *', ''],
                ['.service-card, .step, .feature, .doctor-card, .faq-item, .adv-item, .adv-list li, .service-detail, .chip-nav', ''],
                ['.split > :first-child, .contact-info .contact-item', 'reveal-left'],
                ['.split > :last-child, .security-list li, .contact-grid > .card', 'reveal-right'],
                ['.mission, .emergency-note', 'reveal-zoom']
            ];
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var t = entry.target;
                        t.classList.add('in');
                        observer.unobserve(t);
                        // Once shown, drop the reveal classes so hover effects work normally again
                        setTimeout(function () {
                            t.classList.remove('reveal', 'in', 'reveal-left', 'reveal-right', 'reveal-zoom');
                            t.style.removeProperty('--reveal-delay');
                        }, 1100);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            var seen = [];
            groups.forEach(function (g) {
                document.querySelectorAll(g[0]).forEach(function (el) {
                    if (seen.indexOf(el) !== -1) { return; }
                    seen.push(el);
                    if (el.getBoundingClientRect().top < window.innerHeight) { return; } // already on screen
                    // Stagger siblings in the same grid: 0, 80, 160 ms ...
                    var idx = Array.prototype.indexOf.call(el.parentElement.children, el) % 4;
                    el.style.setProperty('--reveal-delay', (idx * 0.08) + 's');
                    el.classList.add('reveal');
                    if (g[1]) { el.classList.add(g[1]); }
                    observer.observe(el);
                });
            });
        }

        // Count-up for hero numbers like "24/7", "8", "100%"
        if (!reduceMotion) {
            document.querySelectorAll('.hero-stats strong').forEach(function (el) {
                var m = el.textContent.match(/^(\d+)(.*)$/);
                if (!m || m[2].charAt(0) === '/') { return; }
                var target = parseInt(m[1], 10), suffix = m[2], start = null, dur = 1200;
                var step = function (ts) {
                    if (start === null) { start = ts; }
                    var p = Math.min((ts - start) / dur, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
                    if (p < 1) { window.requestAnimationFrame(step); }
                };
                el.textContent = '0' + suffix;
                window.requestAnimationFrame(step);
            });
        }
    }

    // ---- Confirm before dangerous actions: <form data-confirm="..."> ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    // ---- Show / hide password ----
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var icon = btn.querySelector('i');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    });

    // ---- Password strength hint ----
    document.querySelectorAll('input[data-strength]').forEach(function (input) {
        var out = document.getElementById(input.getAttribute('data-strength'));
        if (!out) { return; }
        input.addEventListener('input', function () {
            var v = input.value, missing = [];
            if (v.length < 8) { missing.push('8+ characters'); }
            if (!/[A-Z]/.test(v)) { missing.push('an uppercase letter'); }
            if (!/[a-z]/.test(v)) { missing.push('a lowercase letter'); }
            if (!/\d/.test(v)) { missing.push('a number'); }
            if (!/[^A-Za-z0-9]/.test(v)) { missing.push('a symbol'); }
            out.textContent = missing.length ? 'Needs ' + missing.join(', ') + '.' : 'Strong password.';
            out.className = missing.length ? 'field-error' : 'hint';
        });
    });

    // ---- Confirm password match ----
    document.querySelectorAll('form[data-match]').forEach(function (form) {
        var names = form.getAttribute('data-match').split(',');
        form.addEventListener('submit', function (ev) {
            var a = form.elements[names[0]], b = form.elements[names[1]];
            if (a && b && a.value !== b.value) {
                ev.preventDefault();
                b.setCustomValidity('Passwords do not match.');
                b.reportValidity();
            }
        });
        var second = form.elements[names[1]];
        if (second) { second.addEventListener('input', function () { second.setCustomValidity(''); }); }
    });

    // ---- Do not allow past dates on date pickers marked data-future ----
    document.querySelectorAll('input[type=date][data-future]').forEach(function (input) {
        var d = new Date();
        var iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        input.min = iso;
    });

    // ---- Prevent double-submits ----
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (ev.defaultPrevented) { return; }
            var btn = form.querySelector('button[type=submit]');
            if (btn) { setTimeout(function () { btn.disabled = true; }, 0); }
        });
    });

    // ---- Notification bell polling ----
    var bell = document.getElementById('bellCount');
    if (bell) {
        var poll = function () {
            fetch(base + '/api/notifications.php', { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || typeof data.unread !== 'number') { return; }
                    bell.textContent = String(data.unread);
                    bell.classList.toggle('hidden', data.unread === 0);
                })
                .catch(function () { /* offline: ignore */ });
        };
        setInterval(poll, 60000);
    }
})();
