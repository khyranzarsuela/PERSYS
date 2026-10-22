
<?php
require_once '../../session.php';
require_once '../../database.php';

start_app_session();
require_admin();

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'POST requests only.'
    ]);
}

$requestId = filter_var(
    $_POST['request_id'] ?? null,
    FILTER_VALIDATE_INT
);

$targetStatus = $_POST['target_status'] ?? '';
$adminId = $_SESSION['account_id'] ?? null;

if (!$adminId || !$requestId || $requestId < 1) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid request or admin session.'
    ]);
}

if (!in_array($targetStatus, ['Approved', 'Rejected'], true)) {
    respond(400, [
        'success' => false,
        'message' => 'Choose Approved or Rejected.'
    ]);
}

$creditTypeId = null;

if ($targetStatus === 'Approved') {
    $creditTypeId = filter_var(
        $_POST['credit_type_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!in_array($creditTypeId, [1, 2], true)) {
        respond(400, [
            'success' => false,
            'message' => 'Select Local or National Service Credits.'
        ]);
    }
}

try {
    $pdo->beginTransaction();

    // Lock this request to prevent concurrent finalization.
    $stmt = $pdo->prepare("
        SELECT
            lr.*,
            e.employee_id,
            lt.leave_type_name
        FROM leave_requests lr
        INNER JOIN employees e
            ON e.employee_id = lr.employee_id
        INNER JOIN leave_types lt
            ON lt.leave_type_id = lr.leave_type_id
        WHERE lr.leave_request_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Leave request not found.'
        ]);
    }

    if ($request['status'] !== 'Pending') {
        $pdo->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'Only Pending requests can be finalized.'
        ]);
    }

    // Rejection: record the official decision without a deduction.
    if ($targetStatus === 'Rejected') {
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET status = 'Rejected',
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE leave_request_id = ?
              AND status = 'Pending'
        ");

        $stmt->execute([$adminId, $requestId]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The request could not be marked Rejected.'
            );
        }

        $pdo->commit();

        respond(200, [
            'success' => true,
            'status' => 'Rejected',
            'message' => 'Status updated to Rejected. No service credits were deducted.'
        ]);
    }

    // Approval: serialize credit-balance changes for this employee.
    $stmt = $pdo->prepare("
        SELECT employee_id
        FROM employees
        WHERE employee_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$request['employee_id']]);

    if (!$stmt->fetch()) {
        throw new RuntimeException('Employee record not found.');
    }

    // Calculate the chosen pool's current balance.
    $stmt = $pdo->prepare("
        SELECT COALESCE(
            SUM(
                CASE
                    WHEN transaction_type = 'EARNED'
                        THEN credit_amount
                    WHEN transaction_type = 'DEDUCTED'
                        THEN -credit_amount
                    ELSE 0
                END
            ),
            0
        ) AS balance
        FROM service_credit_transactions
        WHERE employee_id = ?
          AND credit_type_id = ?
    ");

    $stmt->execute([
        $request['employee_id'],
        $creditTypeId
    ]);

    $balance = (float) $stmt->fetchColumn();
    $leaveDays = (float) $request['total_leave_days'];

    if ($leaveDays <= 0) {
        throw new RuntimeException(
            'The request has an invalid number of leave days.'
        );
    }

    if ($balance < $leaveDays) {
        $pdo->rollBack();

        respond(422, [
            'success' => false,
            'message' => sprintf(
                'Insufficient service credits. Available: %.2f; required: %.2f.',
                $balance,
                $leaveDays
            )
        ]);
    }

    // Save the official decision and selected pool.
    $stmt = $pdo->prepare("
        UPDATE leave_requests
        SET status = 'Approved',
            credit_type_id = ?,
            reviewed_by = ?,
            reviewed_at = NOW()
        WHERE leave_request_id = ?
          AND status = 'Pending'
    ");

    $stmt->execute([
        $creditTypeId,
        $adminId,
        $requestId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'The request could not be marked Approved.'
        );
    }

    // Record exactly one deduction as part of this transaction.
    $stmt = $pdo->prepare("
        INSERT INTO service_credit_transactions (
            employee_id,
            credit_type_id,
            upload_batch_id,
            leave_request_id,
            activity_name,
            activity_start_date,
            activity_end_date,
            credit_amount,
            transaction_type,
            created_by
        ) VALUES (
            ?, ?, NULL, ?, ?, ?, ?, ?, 'DEDUCTED', ?
        )
    ");

    $stmt->execute([
        $request['employee_id'],
        $creditTypeId,
        $requestId,
        'Leave deduction - ' . $request['request_number'],
        date('Y-m-d', strtotime($request['start_datetime'])),
        date('Y-m-d', strtotime($request['end_datetime'])),
        number_format($leaveDays, 2, '.', ''),
        $adminId
    ]);

    $remainingBalance = $balance - $leaveDays;

    $pdo->commit();

    respond(200, [
        'success' => true,
        'status' => 'Approved',
        'balance' => $balance,
        'deduction' => $leaveDays,
        'remaining_balance' => $remainingBalance,
        'message' => 'Status updated to Approved and service credits deducted successfully.'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Transaction status update error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'The status could not be updated. Please try again.'
    ]);
}
