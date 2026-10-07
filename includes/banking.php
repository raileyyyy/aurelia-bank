<?php
/**
 * Aurelia Bank — Banking Data Access
 * -----------------------------------------------------------------------------
 * All read queries for accounts and transactions, plus the admin-side summaries
 * and customer management. Centralised here so every page uses the same, safe
 * queries.
 *
 * SECURITY:
 *   - Every query uses prepared statements / bound parameters.
 *   - Customer-facing helpers are always SCOPED BY user_id, so a customer can
 *     only ever read their own accounts and transactions (authorization is
 *     enforced in the query, not just the UI).
 *   - search_transactions() is the reusable search used by the customer
 *     transaction page. It is the designated SQL-injection demonstration point
 *     for a later phase; it is written safely here (bound parameters) so the
 *     vulnerable variant can be introduced in an isolated, documented way.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// -----------------------------------------------------------------------------
// Accounts
// -----------------------------------------------------------------------------

/** All accounts belonging to a user. */
function get_accounts_for_user(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT id, account_number, account_type, balance, currency, status, opened_at
           FROM accounts
          WHERE user_id = :uid
          ORDER BY id'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

/** A single account, but only if it belongs to the given user (ownership check). */
function get_account_for_user(int $accountId, int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, account_number, account_type, balance, currency, status, opened_at
           FROM accounts
          WHERE id = :id AND user_id = :uid
          LIMIT 1'
    );
    $stmt->execute([':id' => $accountId, ':uid' => $userId]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/** Generate a unique account number in the AUREL########## format. */
function generate_account_number(): string
{
    $check = db()->prepare('SELECT 1 FROM accounts WHERE account_number = :n LIMIT 1');
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $number = 'AUREL' . random_int(1000000000, 9999999999);
        $check->execute([':n' => $number]);
        if ($check->fetchColumn() === false) {
            return $number;
        }
    }
    // Astronomically unlikely fallback.
    return 'AUREL' . random_int(1000000000, 9999999999) . random_int(0, 9);
}

/**
 * Open a new active account for a user with a zero balance.
 *
 * @return array{id:int, account_number:string}
 */
function open_account_for_user(int $userId, string $type = 'checking'): array
{
    $number = generate_account_number();
    $stmt = db()->prepare(
        'INSERT INTO accounts (user_id, account_number, account_type, balance, currency, status, opened_at)
         VALUES (:uid, :num, :type, 0.00, \'USD\', \'active\', CURDATE())'
    );
    $stmt->execute([':uid' => $userId, ':num' => $number, ':type' => $type]);
    return ['id' => (int) db()->lastInsertId(), 'account_number' => $number];
}

/** Total balance across all of a user's accounts (single-currency demo: USD). */
function get_total_balance_for_user(int $userId): float
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(balance), 0) FROM accounts WHERE user_id = :uid'
    );
    $stmt->execute([':uid' => $userId]);
    return (float) $stmt->fetchColumn();
}

// -----------------------------------------------------------------------------
// Transactions
// -----------------------------------------------------------------------------

/** The most recent transactions across all of a user's accounts. */
function get_recent_transactions_for_user(int $userId, int $limit = 6): array
{
    $stmt = db()->prepare(
        'SELECT t.id, t.reference, t.type, t.category, t.amount, t.balance_after,
                t.description, t.counterparty, t.status, t.transacted_at,
                a.account_number, a.currency
           FROM transactions t
           JOIN accounts a ON a.id = t.account_id
          WHERE a.user_id = :uid
          ORDER BY t.transacted_at DESC, t.id DESC
          LIMIT :lim'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Build the shared WHERE clause + bound params for transaction searches.
 *
 * @param array<string,mixed> $f
 * @return array{0:string, 1:array<string,mixed>}
 */
function build_transaction_filters(array $f): array
{
    $where  = [];
    $params = [];

    if (!empty($f['user_id'])) {
        $where[] = 'a.user_id = :user_id';
        $params[':user_id'] = (int) $f['user_id'];
    }
    if (!empty($f['account_id'])) {
        $where[] = 't.account_id = :account_id';
        $params[':account_id'] = (int) $f['account_id'];
    }

    // --- Free-text search — Phase 7 SQL-injection demonstration point --------
    // INTENTIONALLY VULNERABLE: the search term is concatenated directly into
    // the query text instead of being bound as a parameter. This is the ONLY
    // clause changed for the demo; every other filter in this function (and
    // every other query in the app) still uses real bound parameters. See
    // README "Security notes" / plan.md Phase 7. Do not copy this pattern
    // elsewhere — restore the bound-parameter version (git history, the
    // commit before this one) once the exercise is done.
    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $where[] = "(t.description LIKE '%$q%' OR t.counterparty LIKE '%$q%' OR t.reference LIKE '%$q%')";
    }

    $type = (string) ($f['type'] ?? '');
    if ($type === 'credit' || $type === 'debit') {
        $where[] = 't.type = :type';
        $params[':type'] = $type;
    }

    $category = trim((string) ($f['category'] ?? ''));
    if ($category !== '') {
        $where[] = 't.category = :category';
        $params[':category'] = $category;
    }

    $from = (string) ($f['date_from'] ?? '');
    if ($from !== '' && is_valid_date($from)) {
        $where[] = 't.transacted_at >= :date_from';
        $params[':date_from'] = $from . ' 00:00:00';
    }

    $to = (string) ($f['date_to'] ?? '');
    if ($to !== '' && is_valid_date($to)) {
        $where[] = 't.transacted_at <= :date_to';
        $params[':date_to'] = $to . ' 23:59:59';
    }

    $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    return [$sql, $params];
}

/**
 * Search / filter transactions with pagination.
 *
 * Recognised filter keys: user_id, account_id, q, type, category,
 * date_from, date_to, limit, offset.
 *
 * @return array{rows: array<int,array<string,mixed>>, total: int}
 */
function search_transactions(array $f): array
{
    [$whereSql, $params] = build_transaction_filters($f);

    // Total (for pagination) with the same filters.
    $countStmt = db()->prepare(
        "SELECT COUNT(*)
           FROM transactions t
           JOIN accounts a ON a.id = t.account_id
         $whereSql"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $limit  = max(1, (int) ($f['limit'] ?? 15));
    $offset = max(0, (int) ($f['offset'] ?? 0));

    $stmt = db()->prepare(
        "SELECT t.id, t.reference, t.type, t.category, t.amount, t.balance_after,
                t.description, t.counterparty, t.status, t.transacted_at,
                a.account_number, a.currency, a.account_type,
                u.full_name AS owner_name, u.id AS owner_id
           FROM transactions t
           JOIN accounts a ON a.id = t.account_id
           JOIN users u    ON u.id = a.user_id
         $whereSql
          ORDER BY t.transacted_at DESC, t.id DESC
          LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/** Distinct transaction categories, optionally scoped to one user (for filters). */
function get_transaction_categories(?int $userId = null): array
{
    if ($userId !== null) {
        $stmt = db()->prepare(
            'SELECT DISTINCT t.category
               FROM transactions t
               JOIN accounts a ON a.id = t.account_id
              WHERE a.user_id = :uid
              ORDER BY t.category'
        );
        $stmt->execute([':uid' => $userId]);
    } else {
        $stmt = db()->query('SELECT DISTINCT category FROM transactions ORDER BY category');
    }
    return array_column($stmt->fetchAll(), 'category');
}

/** A single transaction, but only if it belongs to the given user. */
function get_transaction_for_user(int $txId, int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, a.account_number, a.account_type, a.currency
           FROM transactions t
           JOIN accounts a ON a.id = t.account_id
          WHERE t.id = :id AND a.user_id = :uid
          LIMIT 1'
    );
    $stmt->execute([':id' => $txId, ':uid' => $userId]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/** A single transaction for admin viewing (any account), with owner details. */
function get_transaction_admin(int $txId): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, a.account_number, a.account_type, a.currency,
                u.full_name AS owner_name, u.id AS owner_id
           FROM transactions t
           JOIN accounts a ON a.id = t.account_id
           JOIN users u    ON u.id = a.user_id
          WHERE t.id = :id
          LIMIT 1'
    );
    $stmt->execute([':id' => $txId]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

// -----------------------------------------------------------------------------
// Money movement (deposit / withdraw / transfer)
// -----------------------------------------------------------------------------
//
// All three operations are performed inside a database transaction with row
// locking (SELECT ... FOR UPDATE), so balances and ledger entries can never
// drift apart, even under concurrent requests. Every ledger row stores a
// balance_after snapshot. Amounts are validated by the caller (parse_amount)
// and re-checked here against the live, locked balance.

/** Per-transaction ceiling for this fictional demo. */
const MONEY_MAX = 1000000.00;

/** Look up any account by its number, including the owner's name. */
function get_account_by_number(string $accountNumber): ?array
{
    $stmt = db()->prepare(
        'SELECT a.id, a.user_id, a.account_number, a.account_type, a.balance,
                a.currency, a.status, u.full_name AS owner_name
           FROM accounts a
           JOIN users u ON u.id = a.user_id
          WHERE a.account_number = :num
          LIMIT 1'
    );
    $stmt->execute([':num' => trim($accountNumber)]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/** Generate a unique-enough transaction reference with a category prefix. */
function generate_reference(string $prefix): string
{
    return strtoupper(substr($prefix, 0, 4)) . date('ymdHis') . strtoupper(bin2hex(random_bytes(4)));
}

/** Lock a single account row for update inside an open transaction. */
function _lock_account_by_id(PDO $pdo, int $accountId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, user_id, account_number, balance, status
           FROM accounts WHERE id = :id FOR UPDATE'
    );
    $stmt->execute([':id' => $accountId]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/** Insert one ledger entry (used by deposit/withdraw/transfer). */
function _insert_ledger(
    PDO $pdo, int $accountId, string $reference, string $type, string $category,
    float $amount, float $balanceAfter, string $description, ?string $counterparty
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO transactions
            (account_id, reference, type, category, amount, balance_after,
             description, counterparty, status, transacted_at)
         VALUES
            (:aid, :ref, :type, :cat, :amt, :bal, :desc, :cp, \'completed\', NOW())'
    );
    $stmt->execute([
        ':aid'  => $accountId,
        ':ref'  => $reference,
        ':type' => $type,
        ':cat'  => $category,
        ':amt'  => number_format($amount, 2, '.', ''),
        ':bal'  => number_format($balanceAfter, 2, '.', ''),
        ':desc' => mb_substr($description, 0, 255),
        ':cp'   => $counterparty !== null ? mb_substr($counterparty, 0, 120) : null,
    ]);
}

/**
 * Deposit money into one of the user's own accounts.
 *
 * @return array{ok:bool, error?:string, reference?:string, balance?:float}
 */
function deposit_money(int $userId, int $accountId, float $amount, ?string $note = null): array
{
    if ($amount <= 0 || $amount > MONEY_MAX) {
        return ['ok' => false, 'error' => 'Please enter an amount between 0 and ' . format_money(MONEY_MAX) . '.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $acc = _lock_account_by_id($pdo, $accountId);
        if ($acc === null || (int) $acc['user_id'] !== $userId) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Account not found.'];
        }
        if ($acc['status'] !== 'active') {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'This account is not active.'];
        }

        $newBalance = round((float) $acc['balance'] + $amount, 2);
        $ref = generate_reference('DEP');
        $desc = 'Deposit' . ($note ? ' — ' . $note : '');

        _insert_ledger($pdo, $accountId, $ref, 'credit', 'deposit', $amount, $newBalance, $desc, 'Cash deposit');
        _set_account_balance($pdo, $accountId, $newBalance);

        $pdo->commit();
        return ['ok' => true, 'reference' => $ref, 'balance' => $newBalance];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Aurelia Bank] Deposit failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not complete the deposit. Please try again.'];
    }
}

/**
 * Withdraw money from one of the user's own accounts.
 *
 * @return array{ok:bool, error?:string, reference?:string, balance?:float}
 */
function withdraw_money(int $userId, int $accountId, float $amount, ?string $note = null): array
{
    if ($amount <= 0 || $amount > MONEY_MAX) {
        return ['ok' => false, 'error' => 'Please enter an amount between 0 and ' . format_money(MONEY_MAX) . '.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $acc = _lock_account_by_id($pdo, $accountId);
        if ($acc === null || (int) $acc['user_id'] !== $userId) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Account not found.'];
        }
        if ($acc['status'] !== 'active') {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'This account is not active.'];
        }
        if ((float) $acc['balance'] < $amount) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Insufficient funds for this withdrawal.'];
        }

        $newBalance = round((float) $acc['balance'] - $amount, 2);
        $ref = generate_reference('WDR');
        $desc = 'Withdrawal' . ($note ? ' — ' . $note : '');

        _insert_ledger($pdo, $accountId, $ref, 'debit', 'withdrawal', $amount, $newBalance, $desc, 'ATM / branch');
        _set_account_balance($pdo, $accountId, $newBalance);

        $pdo->commit();
        return ['ok' => true, 'reference' => $ref, 'balance' => $newBalance];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Aurelia Bank] Withdrawal failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not complete the withdrawal. Please try again.'];
    }
}

/**
 * Transfer money from one of the user's accounts to another account (their own
 * or another customer's), identified by account number. Creates a matched
 * debit + credit pair atomically.
 *
 * @return array{ok:bool, error?:string, reference?:string, recipient?:string}
 */
function transfer_money(int $userId, int $fromAccountId, string $toAccountNumber, float $amount, ?string $note = null): array
{
    if ($amount <= 0 || $amount > MONEY_MAX) {
        return ['ok' => false, 'error' => 'Please enter an amount between 0 and ' . format_money(MONEY_MAX) . '.'];
    }

    $dest = get_account_by_number($toAccountNumber);
    if ($dest === null) {
        return ['ok' => false, 'error' => 'Recipient account number was not found.'];
    }
    if ((int) $dest['id'] === $fromAccountId) {
        return ['ok' => false, 'error' => 'Please choose a different account to transfer to.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Lock both rows, always in ascending id order, to avoid deadlocks.
        $ids = [$fromAccountId, (int) $dest['id']];
        sort($ids);
        $locked = [];
        foreach ($ids as $id) {
            $locked[$id] = _lock_account_by_id($pdo, $id);
        }

        $src = $locked[$fromAccountId] ?? null;
        $dst = $locked[(int) $dest['id']] ?? null;

        if ($src === null || (int) $src['user_id'] !== $userId) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Source account not found.'];
        }
        if ($dst === null) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Recipient account not found.'];
        }
        if ($src['status'] !== 'active') {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Your account is not active.'];
        }
        if ($dst['status'] !== 'active') {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'The recipient account cannot receive transfers right now.'];
        }
        if ((float) $src['balance'] < $amount) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Insufficient funds for this transfer.'];
        }

        $srcNew = round((float) $src['balance'] - $amount, 2);
        $dstNew = round((float) $dst['balance'] + $amount, 2);

        $nameStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
        $nameStmt->execute([':id' => $userId]);
        $senderName    = (string) ($nameStmt->fetchColumn() ?: 'Aurelia customer');
        $recipientName = (string) $dest['owner_name'];

        $base = generate_reference('TRF');
        $memo = $note ? ' — ' . $note : '';

        _insert_ledger($pdo, $fromAccountId, $base . 'S', 'debit', 'transfer', $amount, $srcNew,
            'Transfer to ' . $recipientName . $memo, $recipientName);
        _insert_ledger($pdo, (int) $dst['id'], $base . 'R', 'credit', 'transfer', $amount, $dstNew,
            'Transfer from ' . $senderName . $memo, $senderName);

        _set_account_balance($pdo, $fromAccountId, $srcNew);
        _set_account_balance($pdo, (int) $dst['id'], $dstNew);

        $pdo->commit();
        return ['ok' => true, 'reference' => $base, 'recipient' => $recipientName];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Aurelia Bank] Transfer failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not complete the transfer. Please try again.'];
    }
}

/** Update an account balance (within an open transaction). */
function _set_account_balance(PDO $pdo, int $accountId, float $balance): void
{
    $stmt = $pdo->prepare('UPDATE accounts SET balance = :bal WHERE id = :id');
    $stmt->execute([':bal' => number_format($balance, 2, '.', ''), ':id' => $accountId]);
}

// -----------------------------------------------------------------------------
// Admin: overview, customers, management
// -----------------------------------------------------------------------------

/** Headline counts/sums for the admin dashboard. */
function admin_overview_stats(): array
{
    $pdo = db();
    return [
        'customers'     => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
        'accounts'      => (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn(),
        'transactions'  => (int) $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn(),
        'total_balance' => (float) $pdo->query('SELECT COALESCE(SUM(balance), 0) FROM accounts')->fetchColumn(),
    ];
}

/**
 * List customers, optionally filtered by a search term, with pagination.
 *
 * @return array{rows: array<int,array<string,mixed>>, total: int}
 */
function get_customers(string $q = '', int $limit = 15, int $offset = 0): array
{
    $where  = "WHERE u.role = 'customer'";
    $params = [];

    $q = trim($q);
    if ($q !== '') {
        // One placeholder per marker (native prepared statements).
        $where .= ' AND (u.username LIKE :q_user OR u.full_name LIKE :q_name OR u.email LIKE :q_email)';
        $like = '%' . $q . '%';
        $params[':q_user']  = $like;
        $params[':q_name']  = $like;
        $params[':q_email'] = $like;
    }

    $countStmt = db()->prepare("SELECT COUNT(*) FROM users u $where");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = db()->prepare(
        "SELECT u.id, u.username, u.full_name, u.email, u.status, u.created_at,
                COUNT(DISTINCT a.id)        AS account_count,
                COALESCE(SUM(a.balance), 0) AS total_balance
           FROM users u
           LEFT JOIN accounts a ON a.user_id = u.id
         $where
          GROUP BY u.id
          ORDER BY u.full_name
          LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

/** A single customer's full record (role-restricted to customers). */
function get_customer(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT id, username, email, full_name, role, phone, address, status, created_at, updated_at
           FROM users
          WHERE id = :id AND role = 'customer'
          LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/**
 * Update a customer's status (active | locked | disabled). Restricted to
 * customer accounts so an admin cannot lock another admin here.
 */
function set_customer_status(int $id, string $status): bool
{
    if (!in_array($status, ['active', 'locked', 'disabled'], true)) {
        return false;
    }
    $stmt = db()->prepare(
        "UPDATE users SET status = :status WHERE id = :id AND role = 'customer'"
    );
    $stmt->execute([':status' => $status, ':id' => $id]);
    return $stmt->rowCount() > 0;
}
