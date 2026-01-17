<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$current_view = $_GET['view'] ?? 'dashboard';

// --- DATA FETCHING FOR WIDGETS ---

// 1. Admin Balance
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_balance = $stmt->fetchColumn();

// 2. Statistics
// Total Students created by this admin
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_by = ? AND role = 'student'");
$stmt->execute([$admin_id]);
$total_students = $stmt->fetchColumn();

// Total Mentors (Approved)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM users u
    JOIN mentor_applications ma ON u.id = ma.user_id
    WHERE ma.admin_id = ? AND ma.status = 'approved' AND u.role = 'mentor'
");
$stmt->execute([$admin_id]);
$total_mentors = $stmt->fetchColumn();

// 3. Recent Mentor Applications (Limit 5)
$stmt = $pdo->prepare("
    SELECT ma.id, u.name, ma.status, ma.experience
    FROM mentor_applications ma
    JOIN users u ON ma.user_id = u.id
    WHERE ma.admin_id = ?
    ORDER BY ma.created_at DESC
    LIMIT 5
");
$stmt->execute([$admin_id]);
$recent_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. All Users Table (for the list view)
$stmt = $pdo->prepare("SELECT * FROM users WHERE created_by = ? AND role = 'student' ORDER BY id DESC");
$stmt->execute([$admin_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Approved Mentors (for the mentor list view)
$stmt = $pdo->prepare("
    SELECT u.*, ma.experience 
    FROM users u
    JOIN mentor_applications ma ON u.id = ma.user_id
    WHERE ma.admin_id = ? AND ma.status = 'approved' AND u.role = 'mentor'
    ORDER BY u.id DESC
");
$stmt->execute([$admin_id]);
$mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- ACTION HANDLERS (Create Student / Topup) ---

$create_error = '';
$create_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $balance = floatval($_POST['balance'] ?? 0);

    $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $create_error = "Email already exists!";
    } else {
        if ($admin_balance < $balance) {
            $create_error = "Insufficient balance ($admin_balance) to assign $balance points.";
        } else {
            $updateAdmin = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $updateAdmin->execute([$balance, $admin_id]);

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, balance, created_by) VALUES (?, ?, ?, 'student', ?, ?)");
            $insert->execute([$name, $email, $hashed, $balance, $admin_id]);
            $new_user_id = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO transactions (admin_id, type, amount, user_id) VALUES (?, 'assign', ?, ?)")
                ->execute([$admin_id, $balance, $new_user_id]);

            $create_success = "Student account created successfully!";
            // Refetch balance and students
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin_balance = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_by = ? AND role = 'student'");
            $stmt->execute([$admin_id]);
            $total_students = $stmt->fetchColumn();
        }
    }
}

// --- QR CODE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_qr'])) {
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === 0) {
        $upload_dir = '../uploads/qrcodes/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
        $filename = 'qr_' . $admin_id . '_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $target)) {
            $stmt = $pdo->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
            $stmt->execute(['qrcodes/' . $filename, $admin_id]);
            $create_success = "QR Code updated successfully!";
        } else {
            $create_error = "Failed to upload QR code.";
        }
    }
}

// --- ACCOUNT STATUS TOGGLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $target_user_id = intval($_POST['user_id']);
    $new_status = $_POST['new_status'] === 'suspended' ? 'suspended' : 'active';

    // Check if it's a student created by this admin OR a mentor approved by this admin
    $check = $pdo->prepare("
        SELECT id FROM users WHERE id = ? AND created_by = ? 
        UNION 
        SELECT u.id FROM users u JOIN mentor_applications ma ON u.id = ma.user_id WHERE u.id = ? AND ma.admin_id = ? AND ma.status = 'approved'
    ");
    $check->execute([$target_user_id, $admin_id, $target_user_id, $admin_id]);

    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $target_user_id]);
        $create_success = "User status updated to $new_status.";
    } else {
        $create_error = "Unauthorized to update this user.";
    }
}

// Fetch current admin QR
$stmt = $pdo->prepare("SELECT qr_code FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_qr = $stmt->fetchColumn();

// --- LAYOUT INCLUSION ---
$page_title = "Admin Dashboard - MentorHub";
$extra_css = '
<style>
    .stat-group { display: flex; align-items: center; gap: 20px; }
    .icon-circle {
        width: 56px; height: 56px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
    }
    .stat-info .label { color: var(--secondary); font-size: 14px; font-weight: 600; margin-bottom: 4px; }
    .stat-info .value { color: var(--dark); font-size: 24px; font-weight: 800; }
    .modal-content-premium { border-radius: 24px; border: none; padding: 20px; box-shadow: var(--shadow-md); }
</style>
';

include 'layout_header.php';
?>

<?php if ($create_success)
    echo "<div class='alert alert-success rounded-4 border-0 shadow-sm mb-4'>$create_success</div>"; ?>
<?php if ($create_error)
    echo "<div class='alert alert-danger rounded-4 border-0 shadow-sm mb-4'>$create_error</div>"; ?>

<?php if ($current_view === 'dashboard'): ?>
    <!-- Overview Section -->
    <div class="section-header">
        <h5 class="section-title">Dashboard Overview</h5>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="premium-card">
                <div class="stat-group">
                    <div class="icon-circle" style="background: var(--primary-light); color: var(--primary);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Students</div>
                        <div class="value"><?= $total_students ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="premium-card">
                <div class="stat-group">
                    <div class="icon-circle" style="background: #F3E5F5; color: #7B1FA2;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Mentors</div>
                        <div class="value"><?= $total_mentors ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="premium-card">
                <div class="stat-group">
                    <div class="icon-circle" style="background: #FFFCEB; color: var(--warning);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pending Apps</div>
                        <div class="value"><?= $pending_mentor_count ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="premium-card">
                <div class="stat-group">
                    <div class="icon-circle" style="background: #E6FFFB; color: var(--success);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">My Balance</div>
                        <div class="value">$<?= number_format($admin_balance, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & QR -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="premium-card">
                <div class="section-header">
                    <h6 class="fw-bold mb-0">Quick Actions</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <button class="btn-premium btn-p-info w-100 py-3 flex-column" data-bs-toggle="modal"
                            data-bs-target="#createStudentModal">
                            <i class="fas fa-user-plus mb-2"></i>
                            New Student
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="admin_topup.php"
                            class="btn-premium btn-p-primary w-100 py-3 flex-column text-decoration-none">
                            <i class="fas fa-credit-card mb-2"></i>
                            Stripe Refill
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="topup.php"
                            class="btn-premium btn-p-warning w-100 py-3 flex-column text-decoration-none text-warning">
                            <i class="fas fa-exchange-alt mb-2"></i>
                            Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="premium-card">
                <div class="section-header">
                    <h6 class="fw-bold mb-0">Payment QR Section</h6>
                </div>
                <div class="text-center">
                    <div id="qrPreviewContainer"
                        class="mb-3 position-relative d-inline-block p-2 bg-white rounded-4 shadow-sm"
                        style="border: 2px solid var(--primary-light); min-width: 200px; min-height: 200px; display: flex !important; align-items: center; justify-content: center;">
                        <?php
                        $qr_image_path = $admin_qr ?: "qrcodes/admin4.jpeg";
                        if (strpos($qr_image_path, 'qrcodes/') === false) {
                            $qr_image_path = 'qrcodes/' . $qr_image_path;
                        }
                        ?>
                        <img id="qrPreview" src="../uploads/<?= $qr_image_path ?>" alt="QR Code"
                            style="width: 200px; height: 200px; border-radius: 12px; object-fit: contain;">
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="qrUploadForm">
                        <input type="hidden" name="upload_qr" value="1">
                        <div class="d-flex gap-2">
                            <label class="btn-premium btn-p-info btn-sm flex-grow-1 cursor-pointer">
                                <i class="fas fa-camera me-1"></i> Select New QR
                                <input type="file" name="qr_image" id="qrInput" class="d-none" accept="image/*">
                            </label>
                            <button type="submit" id="qrSubmitBtn" class="btn-premium btn-p-primary btn-sm d-none">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                document.getElementById('qrInput').addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (event) {
                            const preview = document.getElementById('qrPreview');
                            const placeholder = document.getElementById('qrPlaceholder');
                            const submitBtn = document.getElementById('qrSubmitBtn');

                            preview.src = event.target.result;
                            preview.style.display = 'block';
                            if (placeholder) placeholder.style.display = 'none';

                            submitBtn.classList.remove('d-none');
                            // Highlight preview container
                            document.getElementById('qrPreviewContainer').style.borderColor = 'var(--primary)';
                        }
                        reader.readAsDataURL(file);
                    }
                });
            </script>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="section-header">
        <h5 class="section-title">Mentor Applications</h5>
        <a href="mentor_applications.php" class="btn btn-premium btn-p-info btn-sm">View All</a>
    </div>

    <div class="premium-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>Mentor Profile</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_apps as $app): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="admin-avatar"
                                        style="background: var(--primary-light); color: var(--primary); font-size: 14px;">
                                        <?= strtoupper(substr($app['name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($app['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($app['experience']) ?></td>
                            <td>
                                <span
                                    class="badge-premium <?= $app['status'] === 'approved' ? 'badge-approved' : ($app['status'] === 'pending' ? 'badge-pending' : 'badge-suspended') ?>">
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="mentor_applications.php" class="btn-premium btn-p-primary btn-sm">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_apps))
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No recent applications found.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; // End Dashboard View ?>

<?php if ($current_view === 'students'): ?>
    <div class="section-header">
        <h2 class="section-title">Student Management</h2>
        <button class="btn btn-premium btn-p-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
            <i class="fas fa-plus"></i> New Student
        </button>
    </div>

    <div class="premium-card p-0 overflow-hidden border-0">
        <div class="table-responsive">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $u): ?>
                        <tr>
                            <td class="text-muted small">#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="admin-avatar"
                                        style="background: var(--primary-light); color: var(--primary); font-size: 14px;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($u['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span
                                    class="badge-premium <?= $u['status'] === 'active' ? 'badge-approved' : 'badge-suspended' ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td class="fw-bold">$<?= number_format($u['balance'], 2) ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="user_details.php?id=<?= $u['id'] ?>" class="btn btn-premium btn-p-info btn-sm"
                                        title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_status"
                                            value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                        <button type="submit"
                                            class="btn btn-premium btn-sm <?= $u['status'] === 'active' ? 'btn-p-warning' : 'btn-p-primary' ?>"
                                            title="<?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>">
                                            <i class="fas <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                        </button>
                                    </form>
                                    <a href="topup.php?id=<?= $u['id'] ?>" class="btn btn-premium btn-p-primary btn-sm"
                                        title="Top Up">
                                        <i class="fas fa-dollar-sign"></i>
                                    </a>
                                    <a href="delete_user.php?id=<?= $u['id'] ?>" onclick="return confirm('Are you sure?')"
                                        class="btn btn-premium btn-p-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; // End Students View ?>

<?php if ($current_view === 'mentors'): ?>
    <div class="section-header">
        <h2 class="section-title">Mentor Directory</h2>
    </div>

    <div class="premium-card p-0 overflow-hidden border-0">
        <div class="table-responsive">
            <table class="premium-table mb-0">
                <thead>
                    <tr>
                        <th>Mentor Profile</th>
                        <th>Contact Info</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th>Wallet</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentors as $m): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="admin-avatar"
                                        style="background: var(--primary-light); color: var(--primary); font-size: 14px;">
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($m['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($m['email']) ?></td>
                            <td class="text-muted small" style="max-width: 200px;"><?= htmlspecialchars($m['experience']) ?>
                            </td>
                            <td>
                                <span
                                    class="badge-premium <?= $m['status'] === 'active' ? 'badge-approved' : 'badge-suspended' ?>">
                                    <?= ucfirst($m['status']) ?>
                                </span>
                            </td>
                            <td class="fw-bold text-success">$<?= number_format($m['balance'], 2) ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="user_details.php?id=<?= $m['id'] ?>" class="btn btn-premium btn-p-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                        <input type="hidden" name="new_status"
                                            value="<?= $m['status'] === 'active' ? 'suspended' : 'active' ?>">
                                        <button type="submit"
                                            class="btn btn-premium btn-sm <?= $m['status'] === 'active' ? 'btn-p-warning' : 'btn-p-primary' ?>">
                                            <i
                                                class="fas <?= $m['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                    <a href="topup.php?id=<?= $m['id'] ?>" class="btn btn-premium btn-p-primary btn-sm">
                                        <i class="fas fa-wallet"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mentors))
                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No approved mentors found.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; // End Mentors View ?>

<!-- Create Student Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-premium">
            <div class="modal-header border-0 pb-0">
                <h5 class="section-title">Create New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="create_student" value="1">
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            style="padding:12px; border-radius:12px; background:var(--bg); border:none;"
                            placeholder="Enter student name" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Email Address</label>
                        <input type="email" name="email" class="form-control"
                            style="padding:12px; border-radius:12px; background:var(--bg); border:none;"
                            placeholder="student@example.com" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Password</label>
                        <input type="password" name="password" class="form-control"
                            style="padding:12px; border-radius:12px; background:var(--bg); border:none;"
                            placeholder="Set a secure password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Initial Points Allocation</label>
                        <input type="number" step="0.01" name="balance" class="form-control"
                            style="padding:12px; border-radius:12px; background:var(--bg); border:none;" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn-premium btn-p-primary justify-content-center py-3">Create
                            Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>