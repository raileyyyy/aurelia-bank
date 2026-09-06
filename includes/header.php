<?php
/**
 * Aurelia Bank — Common Page Header / Top Navigation
 * -----------------------------------------------------------------------------
 * Pages set these optional variables BEFORE including this file:
 *   $pageTitle  (string)  — browser tab title (appended with the app name)
 *   $activeNav  (string)  — key of the active nav item for highlighting
 *   $bodyClass  (string)  — extra class on <body> for page-specific styling
 *
 * This header is intentionally simple in Phase 1. Authenticated navigation
 * (dashboard links, account menu, logout) is added in later phases once the
 * login system exists.
 */

declare(strict_types=1);

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/bootstrap.php';
}

$pageTitle = isset($pageTitle) ? $pageTitle . ' · ' . APP_NAME : APP_NAME;
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';

/** Helper: mark a nav item active. */
$navActive = static fn (string $key): string => $activeNav === $key ? ' is-active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(APP_NAME . ' — ' . APP_TAGLINE) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(base_url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> home">
            <span class="brand__mark" aria-hidden="true">A</span>
            <span class="brand__name"><?= e(APP_NAME) ?></span>
        </a>

        <?php if (empty($hideNav)): ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav"
                aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="primary-nav" aria-label="Primary">
        <?php if (is_logged_in()): $currentUser = current_user();
              $firstName = trim(explode(' ', $currentUser['full_name'])[0]);
              $navItems = $currentUser['role'] === 'admin'
                  ? [
                      'dashboard'    => ['Dashboard',    'admin/dashboard.php'],
                      'customers'    => ['Customers',    'admin/customers.php'],
                      'transactions' => ['Transactions', 'admin/transactions.php'],
                  ]
                  : [
                      'dashboard'    => ['Dashboard',    'customer/dashboard.php'],
                      'transactions' => ['Transactions', 'customer/transactions.php'],
                      'transfer'     => ['Transfer',     'customer/transfer.php'],
                      'profile'      => ['Profile',      'customer/profile.php'],
                  ]; ?>
            <ul class="site-nav__list">
                <?php foreach ($navItems as $key => [$label, $href]): ?>
                    <li><a class="site-nav__link<?= $navActive($key) ?>" href="<?= e(base_url($href)) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="site-nav__actions">
                <span class="site-nav__user">Hi, <?= e($firstName) ?></span>
                <form class="logout-form" method="post" action="<?= e(base_url('auth/logout.php')) ?>">
                    <button type="submit" class="btn btn--ghost btn--sm">Log out</button>
                </form>
            </div>
        <?php else: ?>
            <ul class="site-nav__list">
                <li><a class="site-nav__link<?= $navActive('home') ?>" href="<?= e(base_url('index.php')) ?>">Home</a></li>
                <li><a class="site-nav__link<?= $navActive('personal') ?>" href="<?= e(base_url('index.php#personal')) ?>">Personal</a></li>
                <li><a class="site-nav__link<?= $navActive('business') ?>" href="<?= e(base_url('index.php#business')) ?>">Business</a></li>
                <li><a class="site-nav__link<?= $navActive('support') ?>" href="<?= e(base_url('index.php#support')) ?>">Support</a></li>
            </ul>
            <div class="site-nav__actions">
                <a class="btn btn--ghost" href="<?= e(base_url('auth/login.php')) ?>">Log in</a>
                <a class="btn btn--gold" href="<?= e(base_url('auth/register.php')) ?>">Open account</a>
            </div>
        <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</header>

<main id="main" class="site-main">
<?php foreach (get_flashes() as $flash): ?>
    <div class="container">
        <div class="alert alert--<?= e($flash['type']) ?>" role="alert">
            <?= e($flash['message']) ?>
        </div>
    </div>
<?php endforeach; ?>
