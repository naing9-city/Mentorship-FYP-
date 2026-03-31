<?php
session_start();
require_once '../../includes/db.php';

// Super admin session check
if (!isset($_SESSION['super_admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

$super_admin_name = $_SESSION['name'] ?? 'Super Admin';

// Handle code generation (MOVED BACK TO DASHBOARD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    $new_token = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO admin_keys (token, created_at) VALUES (?, NOW())");
    $stmt->execute([$new_token]);
    header("Location: system_dashboard.php?success=code_generated");
    exit;
}

// Fetch system balance
$stmt = $pdo->prepare("SELECT balance FROM system_wallet WHERE id = 1");
$stmt->execute();
$system_balance = $stmt->fetchColumn() ?? 0;

// Count total admins
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
$total_admins = $stmt->fetchColumn();

// Count total students
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
$stmt->execute();
$total_students = $stmt->fetchColumn();

// Count total mentors
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'mentor'");
$stmt->execute();
$total_mentors = $stmt->fetchColumn();

// Fetch keys for the portal
$stmt = $pdo->query("SELECT *, UNIX_TIMESTAMP(created_at) as created_time FROM admin_keys WHERE is_used = 0 AND created_at > NOW() - INTERVAL 5 MINUTE ORDER BY created_at DESC");
$active_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT *, UNIX_TIMESTAMP(created_at) as created_time FROM admin_keys WHERE is_used = 1 OR created_at <= NOW() - INTERVAL 5 MINUTE ORDER BY created_at DESC LIMIT 10");
$history_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - MentorHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #05CD99;
            /* Emerald Green */
            --primary-light: #F0FDF4;
            --secondary: #707EAE;
            --dark: #1B2559;
            --success: #05CD99;
            --danger: #EE5D50;
            --bg: #F0FDF4;
            /* Light Mint Bg */
            --card-bg: #FFFFFF;
            --sidebar-width: 290px;
            --shadow-sm: 0px 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0px 18px 40px rgba(5, 205, 153, 0.12);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--card-bg);
            padding: 40px 20px;
            z-index: 1000;
            border-right: 1px solid rgba(5, 205, 153, 0.1);
        }

        .brand {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 50px;
            padding-left: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand i {
            color: var(--primary);
            font-size: 28px;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            padding: 16px 22px;
            margin-bottom: 10px;
            color: var(--secondary);
            border-radius: 12px;
            text-decoration: none;
            transition: 0.2s;
            font-weight: 600;
        }

        .nav-item-custom i {
            font-size: 18px;
            margin-right: 15px;
            width: 25px;
            text-align: center;
        }

        .nav-item-custom:hover,
        .nav-item-custom.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .search-inner {
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 350px;
            box-shadow: var(--shadow-sm);
        }

        .search-inner input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 14px;
        }

        .top-icons {
            display: flex;
            gap: 20px;
            align-items: center;
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: var(--shadow-sm);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: var(--dark);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Stats & Banners */
        .wallet-banner {
            background: linear-gradient(135deg, var(--primary), #065F46);
            color: white;
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .wallet-banner::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .premium-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            border: none;
            box-shadow: var(--shadow-md);
            height: 100%;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: var(--primary-light);
            color: var(--primary);
        }

        .stat-info .label {
            color: var(--secondary);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .stat-info .value {
            color: var(--dark);
            font-size: 24px;
            font-weight: 800;
        }

        .btn-p-primary {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
            border: none;
        }

        .btn-p-primary:hover {
            background: #065F46;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(5, 205, 153, 0.3);
            color: white;
        }

        /* Portal specific styles */
        .badge-premium {
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 800;
        }

        .badge-active {
            background: #E6F8F9;
            color: #008080;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="system_dashboard.php" class="brand">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div>MentorHub</div>
                <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Super Admin
                </div>
            </div>
        </a>
        <nav>
            <a href="system_dashboard.php" class="nav-item-custom active">
                <i class="fas fa-th-large"></i> System Overview
            </a>
            <a href="manage_admins.php" class="nav-item-custom">
                <i class="fas fa-users-cog"></i> Manage Admins
            </a>
            <a href="global_reports.php" class="nav-item-custom">
                <i class="fas fa-chart-line"></i> Global Reports
            </a>
            <a href="logout.php" class="nav-item-custom text-danger mt-5">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h2 class="m-0" style="font-size: 28px; font-weight: 800; letter-spacing: -1px;"><?= $greeting ?>, <span style="color: var(--primary);"><?= htmlspecialchars($super_admin_name) ?></span>! 👋</h2>
            <div class="top-icons">
                <span class="fw-bold text-dark me-2 d-none d-md-inline"><?= htmlspecialchars($super_admin_name) ?></span>
                <div class="admin-avatar">SA</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <div class="wallet-banner h-100 p-4">
                    <div class="small fw-700 opacity-75 mb-1 text-uppercase">System Revenue</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-4 fw-700 opacity-75">$</span>
                        <span class="display-6 fw-800"><?= number_format($system_balance, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="premium-card">
                    <div class="icon-circle">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Admins</div>
                        <div class="value"><?= $total_admins ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="premium-card">
                    <div class="icon-circle" style="background: #e1fdf0; color: #05cd99;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Students</div>
                        <div class="value"><?= $total_students ?></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="premium-card">
                    <div class="icon-circle" style="background: #e6fffa; color: #4fd1c5;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Mentors</div>
                        <div class="value"><?= $total_mentors ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-lg-7">
                <div class="premium-card flex-column align-items-start p-4">
                    <div class="d-flex justify-content-between w-100 mb-4 align-items-center">
                        <h5 class="fw-800 m-0"><i class="fas fa-key me-2 text-primary"></i> Key Activity Portal</h5>
                        <form method="POST">
                            <button type="submit" name="generate_code" class="btn-p-primary btn-sm border-0">
                                <i class="fas fa-plus"></i> NEW KEY
                            </button>
                        </form>
                    </div>

                    <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3 w-100" id="keyTabs" role="tablist">
                        <li class="nav-item flex-grow-1" role="presentation">
                            <button class="nav-link active w-100 fw-700 py-2 border-0" id="active-tab"
                                data-bs-toggle="pill" data-bs-target="#active-keys" type="button" role="tab"
                                style="font-size: 13px;">ACTIVE (<?= count($active_keys) ?>)</button>
                        </li>
                        <li class="nav-item flex-grow-1" role="presentation">
                            <button class="nav-link w-100 fw-700 py-2 border-0" id="history-tab" data-bs-toggle="pill"
                                data-bs-target="#history-keys" type="button" role="tab"
                                style="font-size: 13px;">HISTORY</button>
                        </li>
                    </ul>

                    <div class="tab-content w-100" id="keyTabsContent">
                        <!-- Active Keys -->
                        <div class="tab-pane fade show active" id="active-keys" role="tabpanel">
                            <?php if (empty($active_keys)): ?>
                                <div class="text-center py-4 bg-light rounded-4 border-dashed w-100">
                                    <p class="text-secondary small m-0 italic">No active invite keys. Generate one to allow
                                        admin registration.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush w-100">
                                    <?php foreach ($active_keys as $key): ?>
                                        <div
                                            class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 border-bottom py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="p-2 bg-primary-light text-primary rounded-3 fw-800"
                                                    style="letter-spacing: 2px;">
                                                    <?= $key['token'] ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <i class="far fa-clock me-1"></i>
                                                    <span class="countdown"
                                                        data-expiry="<?= $key['created_time'] + 300 ?>">Calculating...</span>
                                                </div>
                                            </div>
                                            <span class="badge-premium badge-active shadow-sm"
                                                style="font-size: 10px;">VALID</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- History Keys -->
                        <div class="tab-pane fade" id="history-keys" role="tabpanel">
                            <?php if (empty($history_keys)): ?>
                                <div class="text-center py-4 text-muted small italic">No historical key activity.</div>
                            <?php else: ?>
                                <div class="list-group list-group-flush w-100">
                                    <?php foreach ($history_keys as $key): ?>
                                        <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 border-bottom py-3"
                                            style="opacity: 0.7;">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="p-2 bg-light text-secondary rounded-3 fw-700"
                                                    style="letter-spacing: 1px; text-decoration: line-through;">
                                                    <?= $key['token'] ?>
                                                </div>
                                                <div class="xtra-small text-muted">
                                                    Created <?= date('H:i', $key['created_time']) ?>
                                                </div>
                                            </div>
                                            <?php if ($key['is_used']): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-800"
                                                    style="font-size: 9px; padding: 4px 8px; border-radius: 6px;">CONSUMED</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger fw-800"
                                                    style="font-size: 9px; padding: 4px 8px; border-radius: 6px;">EXPIRED</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="premium-card flex-column align-items-start p-4">
                    <h5 class="fw-800 mb-4"><i class="fas fa-info-circle me-2 text-primary"></i> Registration Guard</h5>
                    <p class="text-secondary small line-height-lg mb-4">
                        Admins cannot register without a valid <strong>one-time-use</strong> code. Keys are strictly
                        valid for <strong>5 minutes</strong>.
                    </p>
                    <div class="p-3 rounded-4 bg-primary-light w-100">
                        <div class="d-flex align-items-center gap-2 text-primary fw-800 small mb-2">
                            <i class="fas fa-history"></i> Audit Trail
                        </div>
                        <div class="xtra-small opacity-75">
                            The history tab tracks the last 10 generated keys. <span
                                class="text-success fw-bold">Consumed</span> indicates a successful admin registration.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateCountdowns() {
            const now = Math.floor(Date.now() / 1000);
            let shouldRefresh = false;
            document.querySelectorAll('.countdown').forEach(el => {
                const expiry = parseInt(el.dataset.expiry);
                const diff = expiry - now;
                if (diff <= 0) {
                    el.innerHTML = '<span class="text-danger">EXPIRED</span>';
                    el.closest('.list-group-item').style.opacity = '0.5';
                    shouldRefresh = true;
                } else {
                    const mins = Math.floor(diff / 60);
                    const secs = diff % 60;
                    el.innerHTML = `${mins}s remaining`;
                    if (mins > 0) el.innerHTML = `${mins}m ${secs}s remaining`;
                }
            });

            if (shouldRefresh) {
                setTimeout(() => { window.location.reload(); }, 2000);
            }
        }
        setInterval(updateCountdowns, 1000);
        updateCountdowns();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
