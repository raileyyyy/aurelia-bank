<?php
/**
 * Aurelia Bank — Admin Dashboard (Phase 6)
 * -----------------------------------------------------------------------------
 * Bank-wide overview for administrators: headline figures and the latest
 * transactions across all accounts, with quick links into the admin tools.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('admin');

$user   = current_user();
$stats  = admin_overview_stats();
$recent = search_transactions(['limit' => 8])['rows'];

$pageTitle = 'Admin dashboard';
$activeNav = 'dashboard';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Administrator overview</h1>
                <p class="text-muted mb-0">Signed in as <?= e($user['full_name']) ?>.</p>
            </div>
        </div>

        <div class="grid grid--4" style="margin-top:1.5rem;">
            <div class="stat-card stat-card--accent">
                <p class="stat-card__label">Total holdings</p>
                <p class="stat-card__value"><?= e(format_money($stats['total_balance'])) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Customers</p>
                <p class="stat-card__value"><?= (int) $stats['customers'] ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Accounts</p>
                <p class="stat-card__value"><?= (int) $stats['accounts'] ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Transactions</p>
                <p class="stat-card__value"><?= (int) $stats['transactions'] ?></p>
            </div>
        </div>

        <div class="hero__actions" style="margin-top:1.5rem;">
            <a class="btn btn--primary" href="<?= e(base_url('admin/customers.php')) ?>">Manage customers</a>
            <a class="btn btn--outline" href="<?= e(base_url('admin/transactions.php')) ?>">All transactions</a>
        </div>

        <h2 style="margin-top:2rem;">Latest transactions</h2>
        <?php if (!$recent): ?>
            <div class="card"><div class="empty-state"><p>No transactions yet.</p></div></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Account</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $t): ?>
                            <tr>
                                <td><?= e(format_date($t['transacted_at'], 'M j, Y')) ?></td>
                                <td><?= e($t['owner_name']) ?></td>
                                <td><?= e($t['description']) ?></td>
                                <td class="text-muted"><?= e(mask_account($t['account_number'])) ?></td>
                                <td class="num <?= $t['type'] === 'credit' ? 'amount-credit' : 'amount-debit' ?>">
                                    <?= $t['type'] === 'credit' ? '+' : '−' ?><?= e(format_money($t['amount'], $t['currency'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
