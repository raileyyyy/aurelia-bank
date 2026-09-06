<?php
/**
 * Aurelia Bank — Customer Dashboard (Phase 3)
 * -----------------------------------------------------------------------------
 * The customer's home: total available balance, their accounts, and recent
 * activity. All data is loaded from the database and SCOPED to the signed-in
 * user, so a customer only ever sees their own information.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('customer');

$user     = current_user();
$userId   = $user['id'];
$accounts = get_accounts_for_user($userId);
$total    = get_total_balance_for_user($userId);
$recent   = get_recent_transactions_for_user($userId, 6);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
                <p class="text-muted mb-0">Here is a summary of your accounts.</p>
            </div>
            <div class="action-bar">
                <a class="btn btn--primary btn--sm" href="<?= e(base_url('customer/transfer.php')) ?>">Transfer</a>
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/deposit.php')) ?>">Deposit</a>
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/withdraw.php')) ?>">Withdraw</a>
            </div>
        </div>

        <!-- Balance + account summary ------------------------------------->
        <div class="grid grid--3" style="margin-top:1.5rem;">
            <div class="stat-card stat-card--accent">
                <p class="stat-card__label">Total balance</p>
                <p class="stat-card__value"><?= e(format_money($total)) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Accounts</p>
                <p class="stat-card__value"><?= count($accounts) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Recent activity</p>
                <p class="stat-card__value"><?= count($recent) ?> <span style="font-size:1rem;font-weight:500;">shown</span></p>
            </div>
        </div>

        <!-- Accounts ------------------------------------------------------>
        <h2 style="margin-top:2rem;">Your accounts</h2>
        <?php if (!$accounts): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🏦</div>
                    <p>You don't have any accounts yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach ($accounts as $acc): ?>
                    <div class="card account-card">
                        <div class="account-card__top">
                            <span class="badge"><?= e(ucfirst($acc['account_type'])) ?></span>
                            <span class="badge <?= e(status_badge_class($acc['status'])) ?>"><?= e(ucfirst($acc['status'])) ?></span>
                        </div>
                        <p class="account-card__number"><?= e($acc['account_number']) ?></p>
                        <p class="stat-card__label">Available balance</p>
                        <p class="account-card__balance"><?= e(format_money($acc['balance'], $acc['currency'])) ?></p>
                        <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/transactions.php?account_id=' . (int) $acc['id'])) ?>">
                            View transactions
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Recent transactions ------------------------------------------->
        <div class="section-head-row" style="margin-top:2rem;">
            <h2 class="mb-0">Recent transactions</h2>
            <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/transactions.php')) ?>">View all</a>
        </div>

        <?php if (!$recent): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🧾</div>
                    <p>No transactions to show yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap" style="margin-top:1rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $t): ?>
                            <tr>
                                <td><?= e(format_date($t['transacted_at'], 'M j, Y')) ?></td>
                                <td>
                                    <a href="<?= e(base_url('customer/transaction.php?id=' . (int) $t['id'])) ?>"><?= e($t['description']) ?></a>
                                    <?php if (!empty($t['counterparty'])): ?>
                                        <span class="text-muted"> · <?= e($t['counterparty']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(ucfirst($t['category'])) ?></td>
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
