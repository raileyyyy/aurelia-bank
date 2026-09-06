<?php
/**
 * Aurelia Bank — Withdraw (Phase 6+)
 * -----------------------------------------------------------------------------
 * Withdraw funds from one of the signed-in customer's own accounts (a fictional
 * ATM / branch withdrawal). CSRF-protected, POST-only, atomic, and checks for
 * sufficient funds against the live locked balance (see withdraw_money()).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

require_role('customer');

$user     = current_user();
$userId   = $user['id'];
$accounts = array_filter(get_accounts_for_user($userId), static fn ($a) => $a['status'] === 'active');

$error     = '';
$accountId = (int) ($_POST['account_id'] ?? 0);
$amountIn  = trim((string) ($_POST['amount'] ?? ''));
$note      = trim((string) ($_POST['note'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Your session has expired. Please try again.';
    } else {
        $amount = parse_amount($amountIn);

        $validAccount = false;
        foreach ($accounts as $a) {
            if ((int) $a['id'] === $accountId) { $validAccount = true; break; }
        }

        if (!$validAccount) {
            $error = 'Please choose a valid account.';
        } elseif ($amount === null) {
            $error = 'Please enter a valid amount greater than zero.';
        } else {
            $result = withdraw_money($userId, $accountId, $amount, $note !== '' ? $note : null);
            if ($result['ok']) {
                log_audit($userId, 'money.withdraw',
                    sprintf('Withdrew %s from account #%d (ref %s)', format_money($amount), $accountId, $result['reference']));
                set_flash('success', sprintf('%s withdrawn. New balance %s.',
                    format_money($amount), format_money($result['balance'])));
                redirect('customer/dashboard.php');
            }
            $error = $result['error'] ?? 'The withdrawal could not be completed.';
        }
    }
}

$pageTitle = 'Withdraw';
$activeNav = 'transfer';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Withdraw money</h1>
                <p class="text-muted mb-0">Take funds out of one of your accounts.</p>
            </div>
            <div class="action-bar">
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/transfer.php')) ?>">Transfer</a>
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/deposit.php')) ?>">Deposit</a>
            </div>
        </div>

        <?php if (!$accounts): ?>
            <div class="card" style="margin-top:1.25rem;">
                <div class="empty-state"><div class="empty-state__icon" aria-hidden="true">🏦</div>
                    <p>You have no active accounts.</p></div>
            </div>
        <?php else: ?>
            <div class="card form-narrow" style="margin-top:1.25rem;">
                <?php if ($error !== ''): ?>
                    <div class="alert alert--error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('customer/withdraw.php')) ?>" novalidate data-validate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="label" for="account_id">From account</label>
                        <select class="select" id="account_id" name="account_id" required>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $accountId === (int) $a['id'] ? 'selected' : '' ?>>
                                    <?= e(ucfirst($a['account_type']) . ' · ' . mask_account($a['account_number'])
                                        . ' · ' . format_money($a['balance'], $a['currency'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label" for="amount">Amount</label>
                        <input class="input" type="text" inputmode="decimal" id="amount" name="amount"
                               value="<?= e($amountIn) ?>" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label class="label" for="note">Note <span class="text-muted">(optional)</span></label>
                        <input class="input" type="text" id="note" name="note" value="<?= e($note) ?>" maxlength="120">
                    </div>

                    <button type="submit" class="btn btn--primary">Withdraw</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
