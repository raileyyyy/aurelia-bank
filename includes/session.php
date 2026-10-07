<?php
/**
 * Aurelia Bank — Session Bootstrap
 * -----------------------------------------------------------------------------
 * Starts a PHP session with cookie parameters.
 *
 *   - HttpOnly  : JavaScript cannot read the session cookie
 *   - Secure    : INTENTIONALLY DISABLED — Phase 7 MITM demonstration point
 *   - SameSite  : 'Lax' — sensible default that still allows normal navigation
 *
 * MITM DEMONSTRATION (Phase 7): the cookie's `Secure` attribute is hard-coded
 * false instead of being auto-detected from the connection's scheme, so the
 * session cookie is sent over a plain-HTTP connection too. Anyone who can
 * observe that traffic (e.g. a classic sslstrip-style MITM on the same
 * network, or sniffing the local XAMPP/LAN copy) can read the session cookie
 * and hijack the login. The hardened counterpart restores the auto-detected
 * `secure` flag below (kept here, commented out, so the two can be compared).
 * See README "Security notes" / plan.md Phase 7.
 *
 * $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
 *     || (($_SERVER['SERVER_PORT'] ?? null) == 443)
 *     || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => false, // Phase 7 MITM demo — see comment above.
        'samesite' => 'Lax',
    ]);

    session_name(SESSION_NAME);
    session_start();
}
