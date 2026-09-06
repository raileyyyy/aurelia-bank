<?php
/**
 * Aurelia Bank — CSRF Protection Helpers
 * -----------------------------------------------------------------------------
 * A small, reusable synchroniser-token implementation used to protect the
 * MONEY-MOVEMENT forms (deposit / withdraw / transfer) from the outset, because
 * those are the most sensitive state-changing actions in the app.
 *
 * NOTE for the later CSRF demonstration:
 *   The profile/settings update is intentionally left WITHOUT this protection —
 *   it is the designated, controlled CSRF demonstration point. Money movement is
 *   deliberately protected here so the demo never targets a financial action
 *   (see the project roadmap). The same helper is what the hardening phase will
 *   apply to the profile form.
 *
 * Usage:
 *   In a form:   <?= csrf_field() ?>
 *   On POST:     if (!csrf_verify()) { ...reject... }
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Return the session CSRF token, creating it on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Hidden input carrying the CSRF token, ready to drop into a <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the token submitted with a POST request against the session token.
 * Uses hash_equals() for a timing-safe comparison.
 */
function csrf_verify(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted)
        && $submitted !== ''
        && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $submitted);
}
