<?php
/**
 * Aurelia Bank — Security / Lab-Toggle Helpers
 * -----------------------------------------------------------------------------
 * Central place for the CONTROLLED vulnerability toggles (Phase 7) and for the
 * brute-force protection that the hardened login uses (Phase 9 counterpart).
 *
 * DESIGN
 *   The application ships SECURE BY DEFAULT. Each of the three demonstration
 *   vulnerabilities is gated behind a single, clearly-named flag:
 *
 *       VULN_BRUTE_FORCE   — disables login rate-limiting (login pages)
 *       VULN_SQLI          — enables the unsafe transaction-search query
 *       VULN_CSRF          — disables the CSRF token on the profile update
 *
 *   All three default to FALSE (see config/config.php). They are switched on
 *   ONLY inside the isolated VirtualBox test VM, by editing config.local.php.
 *   This is exactly the "clearly isolate the vulnerable code so it can be
 *   reverted" requirement from the project roadmap: to revert, set the flag
 *   back to false — no code changes needed.
 *
 *   Because the flags live in configuration (not in the UI), a normal visitor
 *   never sees a "security lab" — the site still looks and behaves like an
 *   ordinary bank, as required.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Whether a given controlled vulnerability is currently armed.
 *
 * @param string $key One of: 'brute_force', 'sqli', 'csrf'.
 */
function vuln_enabled(string $key): bool
{
    switch ($key) {
        case 'brute_force':
            return defined('VULN_BRUTE_FORCE') && VULN_BRUTE_FORCE === true;
        case 'sqli':
            return defined('VULN_SQLI') && VULN_SQLI === true;
        case 'csrf':
            return defined('VULN_CSRF') && VULN_CSRF === true;
        default:
            return false;
    }
}

// -----------------------------------------------------------------------------
// Brute-force protection (used by the HARDENED login path)
// -----------------------------------------------------------------------------
//
// The parameters below define a simple sliding-window lockout:
//   after LOGIN_MAX_FAILURES failed attempts (matched by username OR client IP)
//   inside the last LOGIN_WINDOW_SECONDS, further attempts are refused until the
//   old failures age out of the window.
//
// These are deliberately gentle so the demonstration is quick to reproduce.

if (!defined('LOGIN_MAX_FAILURES'))   define('LOGIN_MAX_FAILURES', 5);
if (!defined('LOGIN_WINDOW_SECONDS')) define('LOGIN_WINDOW_SECONDS', 900); // 15 minutes

/**
 * Count recent FAILED login attempts matching this username or client IP,
 * within the sliding window. Uses the database clock (NOW()) to avoid any
 * PHP/MySQL timezone mismatch. The window length is an integer constant we
 * control, so inlining it in the SQL is safe.
 */
function login_recent_failures(?string $username, ?string $ip): int
{
    $window = (int) LOGIN_WINDOW_SECONDS;

    $sql = "SELECT COUNT(*)
              FROM login_attempts
             WHERE successful = 0
               AND attempted_at >= (NOW() - INTERVAL $window SECOND)
               AND (username = :u OR ip_address = :ip)";

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':u'  => $username !== null ? mb_substr($username, 0, 50) : '',
            ':ip' => $ip ?? '',
        ]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        // Fail OPEN for availability, but log it — a lockout check must never
        // take the whole login page down.
        error_log('[Aurelia Bank] login_recent_failures failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Whether this username/IP is currently locked out under the hardened policy.
 *
 * When the brute-force vulnerability is ARMED (VULN_BRUTE_FORCE = true) this
 * always returns false — i.e. there is no protection, which is the point of the
 * demonstration. When it is disarmed (the default) the sliding-window lockout
 * is enforced.
 */
function login_is_locked_out(?string $username, ?string $ip): bool
{
    if (vuln_enabled('brute_force')) {
        return false; // protection intentionally disabled for the demonstration
    }
    return login_recent_failures($username, $ip) >= LOGIN_MAX_FAILURES;
}
