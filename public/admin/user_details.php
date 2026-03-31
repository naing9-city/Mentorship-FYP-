<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$user_id = $_GET['id'] ?? 0;

// Fetch user details - Check if student created by this admin OR mentor approved by this admin
$stmt = $pdo->prepare("
    SELECT u.* 
    FROM users u 
    LEFT JOIN mentor_applications ma ON u.id = ma.user_id
    WHERE u.id = ? AND (u.created_by = ? OR (ma.admin_id = ? AND ma.status = 'approved'))
");
$stmt->execute([$user_id, $admin_id, $admin_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found or unauthorized.");
}

// Handle Post Deletion for Mentors
if (isset($_GET['action']) && $_GET['action'] === 'delete_post' && isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    
    // Authorization check
    $stmt = $pdo->prepare("SELECT image_path FROM mentor_posts WHERE id = ? AND mentor_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post_to_del = $stmt->fetch();
    
    if ($post_to_del) {
        if ($post_to_del['image_path']) {
            $image_file = __DIR__ . '/../uploads/' . $post_to_del['image_path'];
            if (file_exists($image_file)) unlink($image_file);
        }
        $pdo->prepare("DELETE FROM mentor_posts WHERE id = ?")->execute([$post_id]);
        $success_msg = "Post deleted successfully.";
    }
}

// Fetch Appointment History
$stmt = $pdo->prepare("
    SELECT a.*, u_mentor.name AS mentor_name, u_student.name AS student_name
    FROM appointments a
    JOIN users u_mentor ON a.mentor_id = u_mentor.id
    JOIN users u_student ON a.student_id = u_student.id
    WHERE a.student_id = ? OR a.mentor_id = ?
    ORDER BY a.scheduled_at DESC
");
$stmt->execute([$user_id, $user_id]);
$appointments = $stmt->fetchAll();

// Fetch Transaction History
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? OR admin_id = ? AND user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id, $admin_id, $user_id]);
$transactions = $stmt->fetchAll();

// Fetch Mentor Posts (if user is mentor)
$mentor_posts = [];
if ($user['role'] === 'mentor') {
    $stmt = $pdo->prepare("SELECT * FROM mentor_posts WHERE mentor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $mentor_posts = $stmt->fetchAll();
}

// --- LAYOUT INCLUSION ---
$page_title = "User Details - " . htmlspecialchars($user['name']);
$back_view = ($user['role'] === 'mentor') ? 'mentors' : 'students';
$extra_css = '
<style>
    .profile-header {
        background: var(--primary); color: white; border-radius: 24px; padding: 40px; margin-bottom: 40px; position: relative; overflow: hidden;
    }
    .profile-header::after {
        content: ""; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px;
        background: rgba(255,255,255,0.1); border-radius: 50%;
    }
    .status-pill { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .status-active { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
    .status-suspended { background: #fee2e2; color: #ef4444; }
    .stat-card { background: white; border-radius: 20px; padding: 25px; box-shadow: var(--shadow-md); height: 100%; border: none; }
</style>
';
include 'layout_header.php';
?>

    <div class="mb-5">
        <a href="users.php?view=<?= $back_view ?>" class="btn-premium btn-p-secondary text-decoration-none">
            <i class="fas fa-arrow-left me-2"></i> Back to Navigation
        </a>
    </div>

    <!-- Profile Header -->
    <div class="profile-header shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-auto mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); font-size: 40px; border-radius: 30px; border: 4px solid rgba(255,255,255,0.3);">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
            </div>
            <div class="col">
                <h1 class="fw-800 mb-1"><?= htmlspecialchars($user['name']) ?></h1>
                <p class="mb-0 opacity-75 fw-500">
                    <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?> 
                    <span class="ms-3 d-inline-block px-3 py-1 bg-white text-primary rounded-pill small fw-800">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </p>
            </div>
            <div class="col-md-auto text-md-end mt-4 mt-md-0">
                <span class="status-pill <?= $user['status'] === 'active' ? 'status-active' : 'status-suspended' ?> px-4 py-2 fs-6">
                    <i class="fas fa-circle me-2" style="font-size: 8px;"></i><?= ucfirst($user['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success border-0 shadow-sm mb-5 rounded-4">
            <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- User Info -->
        <div class="col-md-4">
            <div class="stat-card">
                <h5 class="section-title">Core Statistics</h5>
                <div class="d-flex flex-column gap-4">
                    <div class="stat-item">
                        <div class="text-secondary small fw-700 mb-2">CURRENT BALANCE</div>
                        <div class="fs-2 fw-800 text-primary">$<?= number_format($user['balance'], 2) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="text-secondary small fw-700 mb-2">JOINED SINCE</div>
                        <div class="fw-700"><?= date('F d, Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <?php if($user['role'] === 'mentor'): ?>
                    <div class="stat-item">
                        <div class="text-secondary small fw-700 mb-2">EXPERIENCE</div>
                        <div class="fw-700"><?= htmlspecialchars($user['experience'] ?? 'N/A') ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- History Content -->
        <div class="col-md-8">
            <!-- Session History -->
            <div class="premium-card p-0 overflow-hidden border-0 mb-5">
                <div class="px-4 pt-4">
                    <h5 class="section-title">Session History</h5>
                </div>
                <div class="table-responsive p-3">
                    <table class="premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Participants</th>
                                <th>Points</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($appt['scheduled_at'])) ?></td>
                                <td>
                                    <div class="small fw-700 mb-1">Mentor: <span class="text-dark"><?= htmlspecialchars($appt['mentor_name']) ?></span></div>
                                    <div class="small fw-700">Student: <span class="text-dark"><?= htmlspecialchars($appt['student_name']) ?></span></div>
                                </td>
                                <td class="fw-800 text-primary"><?= $appt['points'] ?> pts</td>
                                <td class="text-end">
                                    <span class="badge-premium" style="background: var(--bg); color: var(--secondary);">
                                        <?= ucfirst($appt['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($appointments)) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No session history recorded.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="premium-card p-0 overflow-hidden border-0 mb-5">
                <div class="px-4 pt-4">
                    <h5 class="section-title">Ledger / Transactions</h5>
                </div>
                <div class="table-responsive p-3">
                    <table class="premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Category</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="text-muted small"><?= date('M d, Y H:i', strtotime($tx['created_at'])) ?></td>
                                <td class="fw-bold">
                                    <?php 
                                        if ($tx['type'] === 'direct_topup') echo "<span class='text-warning'><i class='fas fa-shield-alt me-1'></i>Admin Direct</span>";
                                        else echo ucfirst($tx['type']);
                                    ?>
                                </td>
                                <td class="fw-bold text-end <?= $tx['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx['amount'] > 0 ? '+' : '' ?>$<?= number_format($tx['amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)) echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No financial records found.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mentor Posts section -->
            <?php if ($user['role'] === 'mentor'): ?>
            <div class="premium-card">
                <h5 class="section-title">Mentor Content Stream</h5>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($mentor_posts as $post): ?>
                    <div class="p-4 rounded-4" style="background: var(--bg); position: relative;">
                        <a href="?id=<?= $user_id ?>&action=delete_post&post_id=<?= $post['id'] ?>" 
                           class="btn-premium btn-p-danger btn-sm shadow-sm" style="position: absolute; top: 15px; right: 15px;"
                           onclick="return confirm('Permanently delete this post?')">
                           <i class="fas fa-trash-alt"></i>
                        </a>
                        <div class="text-secondary small fw-700 mb-3"><?= date('F d, Y \a\t H:i', strtotime($post['created_at'])) ?></div>
                        <?php if ($post['image_path']): ?>
                            <div class="mb-3">
                                <img src="../uploads/<?= htmlspecialchars($post['image_path']) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 350px; width: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <p class="mb-0 fw-500 lh-lg" style="color: var(--dark);"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($mentor_posts)) echo "<div class='text-center py-5 text-muted'>This mentor hasn't posted anything yet.</div>"; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'layout_footer.php'; ?>
