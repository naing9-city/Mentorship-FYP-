<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id']; // current admin

// Approve or Reject an application
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action']; // 'approve' or 'reject'
    $application_id = intval($_GET['id']);

    // Fetch the application
    $stmt = $pdo->prepare("SELECT user_id, admin_id FROM mentor_applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($app) {
        // --- SECURITY CHECK: Ensure this application belongs to THIS admin ---
        if ($app['admin_id'] != $admin_id) {
            die("Unauthorized action");
        }
        
        $user_id = $app['user_id'];

        if ($action === 'approve') {
            $pdo->prepare("UPDATE mentor_applications SET status = 'approved', reviewed_at = NOW() WHERE id = ?")->execute([$application_id]);
            $pdo->prepare("UPDATE users SET role = 'mentor', mentor_status = 'approved', is_mentor = 1 WHERE id = ?")->execute([$user_id]);
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE mentor_applications SET status = 'rejected', reviewed_at = NOW() WHERE id = ?")->execute([$application_id]);
            $pdo->prepare("UPDATE users SET mentor_status = 'rejected', is_mentor = 0 WHERE id = ?")->execute([$user_id]);
        }
    }

    header("Location: mentor_applications.php");
    exit;
}

// Fetch applications only for THIS admin
$stmt = $pdo->prepare("
    SELECT ma.id AS application_id,
           u.id AS user_id,
           u.name,
           u.email,
           ma.experience,
           ma.certificate,
           ma.status
    FROM mentor_applications ma
    JOIN users u ON ma.user_id = u.id
    WHERE ma.admin_id = ?
    ORDER BY ma.id DESC
");
$stmt->execute([$admin_id]);
$applications = $stmt->fetchAll();

// --- LAYOUT INCLUSION ---
$page_title = "Mentor Applications - EduNexus";
include 'layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-800 fs-3 m-0">Mentor Applications</h1>
            <p class="text-secondary fw-500 m-0">Review and vet potential educational leaders</p>
        </div>
    </div>

    <div class="premium-card p-0 overflow-hidden border-0">
        <div class="px-4 pt-4">
            <h5 class="section-title">Submission Queue (<?= count($applications) ?>)</h5>
        </div>
        <div class="table-responsive p-3">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Experience</th>
                        <th>Credentials</th>
                        <th>Status</th>
                        <th class="text-end">Verification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($app['name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($app['email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-600 text-dark" style="max-width: 300px;"><?= nl2br(htmlspecialchars($app['experience'])) ?></div>
                        </td>
                        <td>
                            <?php if ($app['certificate']): ?>
                                <a href="../../uploads/<?= $app['certificate'] ?>" target="_blank" class="text-primary fw-700 text-decoration-none">
                                    <i class="fas fa-file-signature me-1"></i>View Document
                                </a>
                            <?php else: ?>
                                <span class="text-muted italic">No certificate uploaded</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-premium badge-<?= $app['status'] ?>">
                                <?= strtoupper($app['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                <?php if ($app['status'] === 'pending'): ?>
                                    <a href="?action=approve&id=<?= $app['application_id'] ?>" 
                                       class="btn-premium btn-p-primary btn-sm" 
                                       onclick="return confirm('Officially approve this mentor?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?action=reject&id=<?= $app['application_id'] ?>" 
                                       class="btn-premium btn-p-danger btn-sm"
                                       onclick="return confirm('Reject this application?')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary small fw-700 italic">Resolution Finalized</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($applications)) echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No mentor applications found for your account.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include 'layout_footer.php'; ?>
