/**
 * Sohojatra — Research Collaboration Portal
 * Lightweight Vanilla JavaScript Interactions (Phase 2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Navigation Toggle
    var toggleBtn = document.querySelector('.mobile-toggle');
    var navLinks = document.querySelector('.navbar-links');

    if (toggleBtn && navLinks) {
        toggleBtn.addEventListener('click', function () {
            navLinks.classList.toggle('show');
            var isExpanded = navLinks.classList.contains('show');
            toggleBtn.setAttribute('aria-expanded', isExpanded);
        });
    }

    // 2. Auto-focus first input in forms if present
    var firstInput = document.querySelector('form input:not([type="hidden"]):not([readonly]), form select, form textarea');
    if (firstInput && !firstInput.value) {
        firstInput.focus();
    }

    // 3. Confirm dialog helper on dangerous actions
    var confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            var message = button.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
