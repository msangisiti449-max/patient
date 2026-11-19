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
