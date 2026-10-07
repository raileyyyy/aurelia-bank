<?php
/**
 * Aurelia Bank — Customer Transactions (Phase 4)
 * -----------------------------------------------------------------------------
 * Transaction history with search, filtering (type / category / date range),
 * optional per-account scoping, and pagination.
 *
 * The free-text "search" box here is the Phase 7 SQL-injection demonstration
 * point: see includes/banking.php :: build_transaction_filters(), where the
 * `q` term is intentionally concatenated into the query instead of bound.
 * Every other filter on this page (and the user_id scope itself) remains a
 * bound parameter — the injection is isolated to that one clause.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('customer');

$user   = current_user();
$userId = $user['id'];

$accounts   = get_accounts_for_user($userId);
$categories = get_transaction_categories($userId);

// --- Read & normalise filters from the query string --------------------------
$q        = trim((string) ($_GET['q'] ?? ''));
$type     = (string) ($_GET['type'] ?? '');
$category = trim((string) ($_GET['category'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo   = trim((string) ($_GET['date_to'] ?? ''));
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 15;

// Account scope: only honour an account_id that actually belongs to this user.
$accountId = (int) ($_GET['account_id'] ?? 0);
if ($accountId > 0 && get_account_for_user($accountId, $userId) === null) {
    $accountId = 0; // not the user's account — ignore it
}

// Ignore invalid date inputs (keep the raw value in the field for correction).
$filterDateFrom = ($dateFrom !== '' && is_valid_date($dateFrom)) ? $dateFrom : '';
$filterDateTo   = ($dateTo   !== '' && is_valid_date($dateTo))   ? $dateTo   : '';

$result = search_transactions([
    'user_id'    => $userId,           // hard authorization scope
    'account_id' => $accountId ?: null,
    'q'          => $q,
    'type'       => $type,
    'category'   => $category,
    'date_from'  => $filterDateFrom,
    'date_to'    => $filterDateTo,
    'limit'      => $perPage,
    'offset'     => ($page - 1) * $perPage,
]);

$rows       = $result['rows'];
$total      = $result['total'];
$totalPages = (int) ceil($total / $perPage);

// Query params preserved across pagination links.
$preserve = array_filter([
    'q'          => $q,
    'type'       => $type,
    'category'   => $category,
    'date_from'  => $dateFrom,
    'date_to'    => $dateTo,
    'account_id' => $accountId ?: null,
], static fn ($v) => $v !== '' && $v !== null);

$pageTitle = 'Transactions';
$activeNav = 'transactions';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Transactions</h1>
                <p class="text-muted mb-0">Search and filter your transaction history.</p>
            </div>
        </div>

        <!-- Filter bar ---------------------------------------------------->
        <form class="card filters" method="get" action="<?= e(base_url('customer/transactions.php')) ?>">
            <div class="filters__grid">
                <div class="form-group filters__search">
                    <label class="label" for="q">Search</label>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($q) ?>" placeholder="Description, counterparty or reference">
                </div>

                <?php if (count($accounts) > 1): ?>
                    <div class="form-group">
                        <label class="label" for="account_id">Account</label>
                        <select class="select" id="account_id" name="account_id">
                            <option value="">All accounts</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= (int) $acc['id'] ?>" <?= $accountId === (int) $acc['id'] ? 'selected' : '' ?>>
                                    <?= e(ucfirst($acc['account_type']) . ' · ' . mask_account($acc['account_number'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="label" for="type">Type</label>
                    <select class="select" id="type" name="type">
                        <option value="">All</option>
                        <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Money in</option>
                        <option value="debit"  <?= $type === 'debit'  ? 'selected' : '' ?>>Money out</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="category">Category</label>
                    <select class="select" id="category" name="category">
                        <option value="">All</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e(ucfirst($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="date_from">From</label>
                    <input class="input" type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
                </div>

                <div class="form-group">
                    <label class="label" for="date_to">To</label>
                    <input class="input" type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
                </div>
            </div>

            <div class="filters__actions">
                <button type="submit" class="btn btn--primary">Apply</button>
                <a class="btn btn--outline" href="<?= e(base_url('customer/transactions.php')) ?>">Reset</a>
            </div>
        </form>

        <!-- Results ------------------------------------------------------->
        <p class="text-muted" style="margin:1.25rem 0 .5rem;">
            <?= $total ?> transaction<?= $total === 1 ? '' : 's' ?> found<?php if ($q !== ''): ?> for &ldquo;<?= e($q) ?>&rdquo;<?php endif; ?>.
        </p>

        <?php if (!$rows): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🔎</div>
                    <p>No transactions match your filters.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Reference</th>
                            <th class="num">Amount</th>
                            <th class="num">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $t): ?>
                            <tr>
                                <td><?= e(format_date($t['transacted_at'], 'M j, Y')) ?></td>
                                <td>
                                    <a href="<?= e(base_url('customer/transaction.php?id=' . (int) $t['id'])) ?>"><?= e($t['description']) ?></a>
                                    <?php if (!empty($t['counterparty'])): ?>
                                        <span class="text-muted"> · <?= e($t['counterparty']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(ucfirst($t['category'])) ?></td>
                                <td class="text-muted"><?= e($t['reference']) ?></td>
                                <td class="num <?= $t['type'] === 'credit' ? 'amount-credit' : 'amount-debit' ?>">
                                    <?= $t['type'] === 'credit' ? '+' : '−' ?><?= e(format_money($t['amount'], $t['currency'])) ?>
                                </td>
                                <td class="num"><?= $t['balance_after'] !== null ? e(format_money($t['balance_after'], $t['currency'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= render_pagination($page, $totalPages, $preserve, 'transactions.php') ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
