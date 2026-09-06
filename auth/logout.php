<?php
/**
 * Aurelia Bank — Logout
 * -----------------------------------------------------------------------------
 * Ends the authenticated session and returns the user to the login page.
 *
 * Logout is a STATE-CHANGING action, so it is only performed on POST. This
 * avoids a logout being triggered by a stray link, prefetch, or an <img> tag
 * pointing at logout.php. A GET request is treated as a no-op redirect.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remember the role before we tear the session down, so we can return the
    // user to the correct portal (staff go back to the admin login).
    $wasAdmin = current_user_role() === 'admin';

    logout_user();

    // Start a fresh session purely to carry the confirmation flash message.
    session_start();
    set_flash('success', 'You have been logged out.');
    redirect($wasAdmin ? 'admin/login.php' : 'auth/login.php');
}

// Not a POST — nothing to do; send the user somewhere sensible.
redirect(is_logged_in() ? role_home(current_user_role()) : 'index.php');
