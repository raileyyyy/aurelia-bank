<?php
/**
 * Aurelia Bank — Shared Helper Functions
 * -----------------------------------------------------------------------------
 * Small, reusable, side-effect-free helpers used across the application.
 * Security-relevant helpers (output escaping) live here so every page uses the
 * same, correct implementation.
 */

declare(strict_types=1);

/**
 * Escape a string for safe output in HTML context.
 * Use this for EVERY dynamic value printed into a page.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build a root-relative URL for the application.
 *
 * Auto-detects the app's web base path from DOCUMENT_ROOT so links work whether
 * the app lives at the web root (Hostinger) or in a subfolder (local XAMPP).
 * Override by defining APP_BASE_URL in config.local.php.
 */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        if (defined('APP_BASE_URL')) {
            $base = rtrim((string) APP_BASE_URL, '/');
        } else {
            $root    = str_replace('\\', '/', ROOT_PATH);
            $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

            if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
                $base = substr($root, strlen($docRoot));
            } else {
                $base = '';
            }
            $base = rtrim($base, '/');
        }
    }

    return $base . '/' . ltrim($path, '/');
}

/**
 * Convenience wrapper for asset URLs (CSS, JS, images).
 */
function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect to an application path and stop execution.
 */
function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Store a one-time flash message to display after a redirect.
 * $type is a semantic class: success | error | info | warning
 */
function set_flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

/**
 * Retrieve and clear all pending flash messages.
 *
 * @return array<int, array{type:string, message:string}>
 */
function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

/**
 * Format a decimal amount as currency for display.
 */
function format_money(float|string $amount, string $currency = 'USD'): string
{
    $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol  = $symbols[$currency] ?? ($currency . ' ');
    return $symbol . number_format((float) $amount, 2);
}

/**
 * Format a datetime string for display.
 */
function format_date(string $datetime, string $format = 'M j, Y'): string
{
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : $datetime;
}

/**
 * Parse a user-entered money amount into a positive float with 2 decimals.
 * Accepts values like "100", "100.50" or "1,000.50". Returns null if the input
 * is not a valid, positive amount.
 */
function parse_amount(string $input): ?float
{
    $clean = str_replace([',', ' '], '', trim($input));
    if ($clean === '' || !is_numeric($clean)) {
        return null;
    }
    $value = round((float) $clean, 2);
    return $value > 0 ? $value : null;
}

/**
 * Mask an account number for compact display, showing only the last 4 chars.
 */
function mask_account(string $accountNumber): string
{
    $last4 = substr($accountNumber, -4);
    return '•••• ' . $last4;
}

/**
 * Validate a 'YYYY-MM-DD' date string. Returns true only for a real calendar
 * date in that exact format.
 */
function is_valid_date(string $value): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

/**
 * Render a simple pagination control.
 *
 * @param int                  $current  1-based current page
 * @param int                  $total    total number of pages
 * @param array<string,scalar> $params   query params to preserve (minus 'page')
 * @param string               $script   target script (defaults to current)
 */
function render_pagination(int $current, int $total, array $params = [], string $script = ''): string
{
    if ($total <= 1) {
        return '';
    }

    $script = $script !== '' ? $script : basename($_SERVER['SCRIPT_NAME'] ?? '');
    unset($params['page']);

    $link = static function (int $page) use ($params, $script): string {
        $params['page'] = $page;
        return e($script . '?' . http_build_query($params));
    };

    $html  = '<nav class="pagination" aria-label="Pagination">';
    $html .= $current > 1
        ? '<a class="pagination__link" href="' . $link($current - 1) . '" rel="prev">&larr; Prev</a>'
        : '<span class="pagination__link is-disabled">&larr; Prev</span>';
    $html .= '<span class="pagination__status">Page ' . $current . ' of ' . $total . '</span>';
    $html .= $current < $total
        ? '<a class="pagination__link" href="' . $link($current + 1) . '" rel="next">Next &rarr;</a>'
        : '<span class="pagination__link is-disabled">Next &rarr;</span>';
    $html .= '</nav>';

    return $html;
}

/**
 * Map a record status to a badge CSS modifier class.
 */
function status_badge_class(string $status): string
{
    return match ($status) {
        'active', 'completed'         => 'badge--success',
        'pending'                     => 'badge--warning',
        'locked', 'frozen', 'failed', 'disabled', 'closed' => 'badge--danger',
        default                       => '',
    };
}
