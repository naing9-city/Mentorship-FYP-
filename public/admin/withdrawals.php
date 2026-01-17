<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $req_id = (int)$_POST['id'];
    $action = $_POST['action']; // approve or reject

    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND admin_id = ? AND status = 'pending'");
    $stmt->execute([$req_id, $admin_id]);
    $req = $stmt->fetch();

    if ($req) {
        if ($action === 'approve') {
            $proof_filename = null;
            
            // Handle Proof Upload
            if (isset($_FILES['admin_proof']) && $_FILES['admin_proof']['error'] === 0) {
                $upload_dir = '../../uploads/payouts/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['admin_proof']['name'], PATHINFO_EXTENSION);
                $proof_filename = 'payout_' . $req_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['admin_proof']['tmp_name'], $upload_dir . $proof_filename);
            }

            if ($proof_filename) {
                $pdo->prepare("UPDATE withdrawal_requests SET status = 'approved', admin_proof = ? WHERE id = ?")
                    ->execute(['payouts/' . $proof_filename, $req_id]);
                header("Location: withdrawals.php?success=paid");
                exit;
            } else {
                header("Location: withdrawals.php?error=proof_required");
                exit;
            }
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            
            $pdo->beginTransaction();
            try {
                // Refund mentor points
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$req['amount'], $req['mentor_id']]);
                // Mark Rejected with reason
                $pdo->prepare("UPDATE withdrawal_requests SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $req_id]);
                
                $pdo->commit();
                header("Location: withdrawals.php?success=rejected");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }
    }
}

// Fetch Pending Requests
$stmt = $pdo->prepare("
    SELECT wr.*, u.name as mentor_name, u.email as mentor_email 
    FROM withdrawal_requests wr 
    JOIN users u ON wr.mentor_id = u.id 
    WHERE wr.admin_id = ? AND wr.status = 'pending' 
    ORDER BY wr.created_at ASC
");
$stmt->execute([$admin_id]);
$pending = $stmt->fetchAll();

// Fetch History
$stmt = $pdo->prepare("
    SELECT wr.*, u.name as mentor_name 
    FROM withdrawal_requests wr 
    JOIN users u ON wr.mentor_id = u.id 
    WHERE wr.admin_id = ? AND wr.status != 'pending' 
    ORDER BY wr.created_at DESC 
    LIMIT 30
");
$stmt->execute([$admin_id]);
$history = $stmt->fetchAll();

// --- LAYOUT INCLUSION ---
$page_title = "Withdrawal Management - EduNexus";
$extra_css = '
<style>
    .bank-info-box { background: var(--bg); border: 1px solid rgba(0,0,0,0.05); border-radius: 12px; padding: 12px; font-size: 12px; }
    .modal-content-premium { border-radius: 24px; border: none; padding: 20px; box-shadow: var(--shadow-md); }
</style>
';
include 'layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-800 fs-3 m-0">Withdrawal Management</h1>
            <p class="text-secondary fw-500 m-0">Review and disburse mentor earnings</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-5 rounded-4">
            <i class="fas fa-check-circle me-2"></i> Action processed successfully!
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-5 rounded-4">
            <i class="fas fa-exclamation-circle me-2"></i> 
            <?= $_GET['error'] === 'proof_required' ? 'Payment proof is mandatory for approvals.' : 'Withdrawal processing failed.' ?>
        </div>
    <?php endif; ?>

    <!-- Pending Queue -->
    <div class="premium-card p-0 overflow-hidden border-0 mb-5">
        <div class="px-4 pt-4">
            <h5 class="section-title">Payout Queue (<?= count($pending) ?>)</h5>
        </div>
        <div class="table-responsive p-3">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>Mentor</th>
                        <th>Amount</th>
                        <th style="width: 30%">Payment Destination</th>
                        <th class="text-end">Verification & Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p['mentor_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($p['mentor_email']) ?></div>
                        </td>
                        <td class="fw-800 text-success">$<?= number_format($p['amount'], 2) ?></td>
                        <td>
                            <div class="bank-info-box">
                                <?php 
                                    $details = json_decode($p['payment_details'], true);
                                    if ($details): ?>
                                        <div class="mb-1">Bank: <strong><?= htmlspecialchars($details['bank_name']) ?></strong></div>
                                        <div class="mb-1">Acc: <strong><?= htmlspecialchars($details['account_number']) ?></strong></div>
                                        <div>Name: <strong><?= htmlspecialchars($details['account_holder']) ?></strong></div>
                                    <?php else: ?>
                                        <div class="text-muted"><?= nl2br(htmlspecialchars($p['payment_details'])) ?></div>
                                    <?php endif; 
                                ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <div style="max-width: 150px;">
                                        <input type="file" name="admin_proof" class="form-control form-control-sm" accept="image/*" required title="Attach Payment Receipt">
                                    </div>
                                    <button type="submit" name="action" value="approve" class="btn-premium btn-p-primary btn-sm" onclick="return confirm('Officially confirm this payout?')">
                                        <i class="fas fa-file-invoice-dollar"></i> Mark Paid
                                    </button>
                                </form>
                                <button type="button" class="btn-premium btn-p-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $p['id'] ?>">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($pending)) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>Empty queue. All withdrawals processed.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals (Moved outside table) -->
    <?php foreach($pending as $p): ?>
    <div class="modal fade" id="rejectModal<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-premium">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <div class="modal-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 48px;"></i>
                        </div>
                        <h5 class="fw-800 text-dark mb-3">Reject Withdrawal</h5>
                        <p class="text-secondary mb-4">Are you sure? Points will be automatically returned to <?= htmlspecialchars($p['mentor_name']) ?>'s balance.</p>
                        <div class="text-start mb-4">
                            <label class="form-label fw-700 small">Reason for Rejection</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" style="border-radius:12px; background:var(--bg); border:none; padding: 15px;" placeholder="e.g. Invalid bank details..." required></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn-premium btn-p-danger justify-content-center py-3">Confirm Rejection</button>
                            <button type="button" class="btn btn-link text-secondary fw-700 text-decoration-none" data-bs-dismiss="modal">Go Back</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- History -->
    <div class="premium-card p-0 overflow-hidden border-0">
        <div class="px-4 pt-4">
            <h5 class="section-title">Resolution History</h5>
        </div>
        <div class="table-responsive p-3">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>Mentor</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Resolution Insight</th>
                        <th class="text-end">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $h): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($h['mentor_name']) ?></div>
                        </td>
                        <td class="fw-800">$<?= number_format($h['amount'], 2) ?></td>
                        <td>
                            <span class="badge-premium badge-<?= $h['status'] ?>">
                                <?= strtoupper($h['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($h['status'] === 'approved' && $h['admin_proof']): ?>
                                <a href="../../uploads/<?= $h['admin_proof'] ?>" target="_blank" class="text-primary fw-700 text-decoration-none">
                                    <i class="fas fa-receipt me-1"></i>View Payout Receipt
                                </a>
                            <?php elseif ($h['status'] === 'rejected' && $h['rejection_reason']): ?>
                                <div class="text-danger small fw-600">
                                    <?= htmlspecialchars($h['rejection_reason']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small italic">No insights provided</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted small"><?= date('M d, Y', strtotime($h['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'layout_footer.php'; ?>
