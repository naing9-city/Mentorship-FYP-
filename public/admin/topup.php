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

    $stmt = $pdo->prepare("SELECT * FROM topup_requests WHERE id = ? AND admin_id = ? AND status = 'pending'");
    $stmt->execute([$req_id, $admin_id]);
    $req = $stmt->fetch();

    if ($req) {
        if ($action === 'approve') {
            // Check admin balance
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin_balance = $stmt->fetchColumn();

            if ($admin_balance >= $req['amount']) {
                $pdo->beginTransaction();
                try {
                    // Deduct Admin
                    $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$req['amount'], $admin_id]);
                    // Add Student
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$req['amount'], $req['student_id']]);
                    // Update Status
                    $pdo->prepare("UPDATE topup_requests SET status = 'approved' WHERE id = ?")->execute([$req_id]);
                    // Log Transaction
                    $pdo->prepare("INSERT INTO transactions (admin_id, user_id, type, amount) VALUES (?, ?, 'topup', ?)")
                        ->execute([$admin_id, $req['student_id'], $req['amount']]);
                    
                    $pdo->commit();
                    header("Location: topup.php?success=approved");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                }
            } else {
                header("Location: topup.php?error=insufficient_balance");
                exit;
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            $proof_filename = null;

            // Handle Refund Proof Upload
            if (isset($_FILES['rejection_proof']) && $_FILES['rejection_proof']['error'] === 0) {
                $upload_dir = '../uploads/payouts/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['rejection_proof']['name'], PATHINFO_EXTENSION);
                $proof_filename = 'refund_' . $req_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['rejection_proof']['tmp_name'], $upload_dir . $proof_filename);
            }

            if ($proof_filename) {
                $pdo->prepare("UPDATE topup_requests SET status = 'rejected', rejection_reason = ?, rejection_proof = ? WHERE id = ?")
                    ->execute([$reason, 'payouts/' . $proof_filename, $req_id]);
                header("Location: topup.php?success=rejected");
                exit;
            } else {
                header("Location: topup.php?error=proof_required");
                exit;
            }
        }
    }
}

// Handle Direct Topup (No request required)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_topup'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $amount = floatval($_POST['amount']);

    if ($amount > 0) {
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin_balance = $stmt->fetchColumn();

        if ($admin_balance >= $amount) {
            $pdo->beginTransaction();
            try {
                // Deduct Admin
                $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $admin_id]);
                // Add User
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $target_user_id]);
                
                // Log Transaction as direct
                $pdo->prepare("INSERT INTO transactions (admin_id, user_id, type, amount) VALUES (?, ?, 'direct_topup', ?)")
                    ->execute([$admin_id, $target_user_id, $amount]);
                
                // Insert into topup_requests as a dummy record for history
                $pdo->prepare("INSERT INTO topup_requests (student_id, admin_id, amount, status, proof_image, bank_info) VALUES (?, ?, ?, 'approved', 'Direct Assignment', 'Direct Admin Top-up')")
                    ->execute([$target_user_id, $admin_id, $amount]);

                $pdo->commit();
                header("Location: topup.php?success=direct_approved");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $create_error = "Transaction failed: " . $e->getMessage();
            }
        } else {
            header("Location: topup.php?error=insufficient_balance");
            exit;
        }
    }
}

// Fetch target user if ?id= is present
$target_user = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $target_user = $stmt->fetch();
}

// Fetch Admin balance
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$balance = $stmt->fetchColumn();

// Fetch Pending Requests
$stmt = $pdo->prepare("
    SELECT tr.*, u.name as student_name, u.email as student_email 
    FROM topup_requests tr 
    JOIN users u ON tr.student_id = u.id 
    WHERE tr.admin_id = ? AND tr.status = 'pending' 
    ORDER BY tr.created_at ASC
");
$stmt->execute([$admin_id]);
$pending = $stmt->fetchAll();

// Fetch History
$stmt = $pdo->prepare("
    SELECT tr.*, u.name as student_name 
    FROM topup_requests tr 
    JOIN users u ON tr.student_id = u.id 
    WHERE tr.admin_id = ? AND tr.status != 'pending' 
    ORDER BY tr.created_at DESC 
    LIMIT 30
");
$stmt->execute([$admin_id]);
$history = $stmt->fetchAll();

// --- LAYOUT INCLUSION ---
$page_title = "Top-up Management - EduNexus";
$extra_css = '
<style>
    .proof-img { width: 50px; height: 50px; object-fit: cover; border-radius: 12px; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .proof-img:hover { transform: scale(1.1); }
    .wallet-banner {
        background: var(--primary);
        color: white;
        border-radius: 24px; padding: 30px; margin-bottom: 30px;
        position: relative; overflow: hidden;
    }
    .wallet-banner::after {
        content: ""; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px;
        background: rgba(255,255,255,0.1); border-radius: 50%;
    }
    .badge-direct { background: var(--bg); color: var(--primary); }
</style>
';
include 'layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-800 fs-3 m-0">Top-up Management</h1>
            <p class="text-secondary fw-500 m-0">Validate payments and allocate student credits</p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-5 rounded-4">
            <i class="fas fa-check-circle me-2"></i> 
            <?= $_GET['success'] === 'approved' ? 'Request approved!' : ($_GET['success'] === 'direct_approved' ? 'Points allocated!' : 'Request rejected.') ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-5 rounded-4">
            <i class="fas fa-exclamation-circle me-2"></i> 
            <?= $_GET['error'] === 'insufficient_balance' ? 'Insufficient admin balance.' : ($_GET['error'] === 'proof_required' ? 'Refund receipt is mandatory for rejections.' : 'Action failed.') ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar: Wallet & Direct -->
        <div class="col-lg-4">
            <div class="wallet-banner shadow-lg">
                <div class="small fw-700 opacity-75 mb-1 text-uppercase">Your Available Balance</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-4 fw-700 opacity-75">$</span>
                    <span class="display-6 fw-800"><?= number_format($balance, 2) ?></span>
                </div>
                <a href="admin_topup.php" class="btn btn-light btn-sm mt-3 px-4 rounded-pill fw-800 text-primary border-0 shadow-sm">
                    <i class="fas fa-bolt me-1"></i> Stripe Refill
                </a>
            </div>

            <div class="premium-card">
                <h5 class="section-title">Direct Allocation</h5>
                <form method="POST">
                    <input type="hidden" name="direct_topup" value="1">
                    <?php if ($target_user): ?>
                        <div class="p-3 mb-4 rounded-4" style="background: var(--bg);">
                            <div class="small text-secondary fw-700 mb-1">TARGET RECIPIENT</div>
                            <div class="fw-800 text-dark"><?= htmlspecialchars($target_user['name']) ?></div>
                            <div class="small text-primary fw-700"><?= strtoupper($target_user['role']) ?> • ID #<?= $target_user['id'] ?></div>
                            <input type="hidden" name="target_user_id" value="<?= $target_user['id'] ?>">
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <label class="form-label fw-700 small">User ID</label>
                            <input type="number" name="target_user_id" class="form-control" style="padding:12px; border-radius:12px; background:var(--bg); border:none;" placeholder="Target ID" required>
                        </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <label class="form-label fw-700 small">Amount to Credit</label>
                        <div class="input-group">
                            <span class="input-group-text border-0" style="background:var(--bg); border-radius:12px 0 0 12px;">$</span>
                            <input type="number" step="0.01" name="amount" class="form-control" style="padding:12px; border-radius:0 12px 12px 0; background:var(--bg); border:none;" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-premium btn-p-primary justify-content-center py-3">
                            <i class="fas fa-gift"></i> Allocate Points
                        </button>
                        <?php if ($target_user): ?>
                            <a href="topup.php" class="btn btn-link btn-sm text-secondary text-decoration-none">Cancel Selection</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="col-lg-8">
            <!-- Pending Requests -->
            <div class="premium-card p-0 overflow-hidden border-0 mb-5">
                <div class="px-4 pt-4">
                    <h5 class="section-title">Pending Approvals (<?= count($pending) ?>)</h5>
                </div>
                <div class="table-responsive p-3">
                    <table class="premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Proof</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['student_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['student_email']) ?></div>
                                </td>
                                <td class="fw-800 text-primary">$<?= number_format($p['amount'], 2) ?></td>
                                <td>
                                    <?php if ($p['proof_image'] && $p['proof_image'] !== 'Direct Assignment'): ?>
                                    <a href="../uploads/<?= $p['proof_image'] ?>" target="_blank">
                                        <img src="../uploads/<?= $p['proof_image'] ?>" class="proof-img">
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-premium btn-p-primary btn-sm" title="Approve" onclick="return confirm('Confirm points assignment?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn-premium btn-p-danger btn-sm" title="Reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $p['id'] ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pending)) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>All set! No pending requests.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modals (Moved outside table) -->
            <?php foreach($pending as $p): ?>
            <div class="modal fade" id="rejectModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-content-premium">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <div class="modal-body text-center p-5">
                                <div class="mb-4">
                                    <i class="fas fa-undo-alt text-danger" style="font-size: 48px;"></i>
                                </div>
                                <h5 class="fw-800 text-dark mb-3">Reject & Refund</h5>
                                <p class="text-secondary mb-4">Please provide a reason and proof of refund to notify <?= htmlspecialchars($p['student_name']) ?>.</p>
                                
                                <div class="text-start mb-3">
                                    <label class="form-label small fw-700">Rejection Reason</label>
                                    <input type="text" name="rejection_reason" class="form-control border-0" style="background:var(--bg); padding: 12px; border-radius: 12px;" placeholder="e.g. Invalid transaction ID" required>
                                </div>
                                <div class="text-start mb-4">
                                    <label class="form-label small fw-700">Refund Proof (Required)</label>
                                    <input type="file" name="rejection_proof" class="form-control border-0" style="background:var(--bg); padding: 12px; border-radius: 12px;" accept="image/*" required>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn-premium btn-p-danger justify-content-center py-3">Confirm Refund & Reject</button>
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
                                <th>Student</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($history as $h): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($h['student_name']) ?></div>
                                    <div class="small text-muted"><?= date('M d, Y', strtotime($h['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($h['proof_image'] === 'Direct Assignment'): ?>
                                        <span class="badge-premium badge-direct">ADMIN DIRECT</span>
                                    <?php else: ?>
                                        <span class="badge-premium badge-<?= $h['status'] ?>">
                                            <?= strtoupper($h['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-800 text-end">$<?= number_format($h['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include 'layout_footer.php'; ?>
