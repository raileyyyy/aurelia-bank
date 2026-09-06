/* =============================================================================
   Aurelia Bank — Foundation JavaScript
   -----------------------------------------------------------------------------
   Progressive enhancement only: the site must remain fully usable if JS fails.
   Phase 1 provides:
     - Mobile navigation toggle
     - Auto-dismiss for flash alerts
     - A tiny, reusable client-side form validation helper (used from Phase 2+)
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initNavToggle();
        initAlertDismiss();
        initFormValidation();
    });

    /** Toggle the primary navigation on small screens. */
    function initNavToggle() {
        var toggle = document.querySelector('.nav-toggle');
        var nav = document.getElementById('primary-nav');
        if (!toggle || !nav) return;

        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /** Auto-dismiss flash alerts after a short delay. */
    function initAlertDismiss() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            window.setTimeout(function () {
                alert.style.transition = 'opacity .4s ease';
                alert.style.opacity = '0';
                window.setTimeout(function () { alert.remove(); }, 450);
            }, 6000);
        });
    }

    /**
     * Client-side validation for any <form data-validate>. This is a convenience
     * layer only — the server always re-validates authoritatively.
     *
     * Supported per field: [required], type="email", [minlength],
     * and [data-match="#otherId"] (values must be equal, e.g. confirm password).
     */
    function initFormValidation() {
        var forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var ok = true;
                var fields = form.querySelectorAll('input, textarea, select');

                fields.forEach(function (field) {
                    if (field.disabled || field.type === 'hidden') return;
                    AureliaForms.clearError(field);

                    var value = (field.value || '').trim();

                    if (field.hasAttribute('required') && value === '') {
                        AureliaForms.markError(field, 'This field is required.');
                        ok = false;
                        return;
                    }
                    if (value === '') return; // optional & empty → nothing more to check

                    if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        AureliaForms.markError(field, 'Please enter a valid email address.');
                        ok = false;
                        return;
                    }
                    var min = parseInt(field.getAttribute('minlength'), 10);
                    if (!isNaN(min) && value.length < min) {
                        AureliaForms.markError(field, 'Must be at least ' + min + ' characters.');
                        ok = false;
                        return;
                    }
                    var matchSel = field.getAttribute('data-match');
                    if (matchSel) {
                        var other = form.querySelector(matchSel);
                        if (other && value !== other.value) {
                            AureliaForms.markError(field, 'Values do not match.');
                            ok = false;
                        }
                    }
                });

                if (!ok) {
                    event.preventDefault();
                    var firstError = form.querySelector('.input--error');
                    if (firstError) firstError.focus();
                }
            });
        });
    }

    /**
     * Reusable helper: mark / clear inline field errors. Exposed globally so
     * page-specific scripts can reuse the same presentation.
     */
    window.AureliaForms = {
        markError: function (input, message) {
            input.classList.add('input--error');
            var next = input.nextElementSibling;
            if (!next || !next.classList.contains('field-error')) {
                next = document.createElement('p');
                next.className = 'field-error';
                input.parentNode.insertBefore(next, input.nextSibling);
            }
            next.textContent = message;
        },
        clearError: function (input) {
            input.classList.remove('input--error');
            var next = input.nextElementSibling;
            if (next && next.classList.contains('field-error')) next.remove();
        }
    };
})();
