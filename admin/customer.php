<?php
/**
 * Aurelia Bank — Admin: Customer Detail & Management (Phase 6)
 * -----------------------------------------------------------------------------
 * Shows a single customer's profile, their accounts and recent transactions,
 * and provides the one piece of basic management for this mini app: changing a
 * customer's status (active / locked / disabled).
 *
 * The status change is a state-changing action, so it is POST-only. (App-wide
 * CSRF token protection is introduced in the security-hardening phase.)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';
require_once __DIR__ . '/../includes/audit.php';

require_role('admin');

$admin      = current_user();
$customerId = (int) ($_GET['id'] ?? 0);

// Handle a status-management submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'status') {
    $postedId  = (int) ($_POST['customer_id'] ?? 0);
    $newStatus = (string) ($_POST['status'] ?? '');

    if ($postedId > 0 && get_customer($postedId) !== null && set_customer_status($postedId, $newStatus)) {
        log_audit($admin['id'], 'admin.customer_status', "Set customer #$postedId status to $newStatus");
        set_flash('success', 'Customer status updated.');
    } else {
        set_flash('error', 'Could not update customer status.');
    }
    redirect('admin/customer.php?id=' . $postedId);
}

$customer = $customerId > 0 ? get_customer($customerId) : null;

$pageTitle = 'Customer';
$activeNav = 'customers';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <p><a href="<?= e(base_url('admin/customers.php')) ?>">&larr; Back to customers</a></p>

        <?php if ($customer === null): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">👤</div>
                    <p>That customer could not be found.</p>
                </div>
            </div>
        <?php else:
            $accounts = get_accounts_for_user((int) $customer['id']);
            $recent   = search_transactions(['user_id' => (int) $customer['id'], 'limit' => 8])['rows'];
        ?>
            <div class="page-header">
                <div>
                    <h1 class="mt-0"><?= e($customer['full_name']) ?></h1>
                    <p class="text-muted mb-0">
                        <?= e($customer['username']) ?> ·
                        <span class="badge <?= e(status_badge_class($customer['status'])) ?>"><?= e(ucfirst($customer['status'])) ?></span>
                    </p>
                </div>
            </div>

            <div class="grid grid--2" style="margin-top:1.25rem; align-items:start;">
                <!-- Profile -->
                <div class="card">
                    <h2 class="card__title mt-0">Profile</h2>
                    <dl class="detail-list">
                        <div><dt>Email</dt><dd><?= e($customer['email']) ?></dd></div>
                        <div><dt>Phone</dt><dd><?= e($customer['phone'] ?? '—') ?></dd></div>
                        <div><dt>Address</dt><dd><?= e($customer['address'] ?? '—') ?></dd></div>
                        <div><dt>Customer since</dt><dd><?= e(format_date($customer['created_at'], 'M j, Y')) ?></dd></div>
                    </dl>
                </div>

                <!-- Management -->
                <div class="card">
                    <h2 class="card__title mt-0">Manage status</h2>
                    <p class="text-muted">Lock or disable an account to prevent the customer signing in.</p>
                    <form method="post" action="<?= e(base_url('admin/customer.php')) ?>">
                        <input type="hidden" name="form" value="status">
                        <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                        <div class="form-group">
                            <label class="label" for="status">Account status</label>
                            <select class="select" id="status" name="status">
                                <?php foreach (['active', 'locked', 'disabled'] as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $customer['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn--primary">Update status</button>
                    </form>
                </div>
            </div>

            <!-- Accounts -->
            <h2 style="margin-top:2rem;">Accounts</h2>
            <?php if (!$accounts): ?>
                <div class="card"><div class="empty-state"><p>No accounts for this customer.</p></div></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Account number</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th class="num">Balance</th>
                                <th>Opened</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $a): ?>
                                <tr>
                                    <td><?= e($a['account_number']) ?></td>
                                    <td><?= e(ucfirst($a['account_type'])) ?></td>
                                    <td><span class="badge <?= e(status_badge_class($a['status'])) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
                                    <td class="num"><?= e(format_money($a['balance'], $a['currency'])) ?></td>
                                    <td><?= $a['opened_at'] ? e(format_date($a['opened_at'], 'M j, Y')) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Recent transactions -->
            <h2 style="margin-top:2rem;">Recent transactions</h2>
            <?php if (!$recent): ?>
                <div class="card"><div class="empty-state"><p>No transactions for this customer.</p></div></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Date</th><th>Description</th><th>Category</th><th class="num">Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $t): ?>
                                <tr>
                                    <td><?= e(format_date($t['transacted_at'], 'M j, Y')) ?></td>
                                    <td><?= e($t['description']) ?></td>
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
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
