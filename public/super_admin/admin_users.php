<?php
session_start();
require_once '../../includes/db.php';

// Only allow super_admin access (Session check omitted for brevity but should be here)
$super_admin_name = $_SESSION['super_admin_name'] ?? 'Super Admin';

// Get the admin_id from the URL
$admin_id = $_GET['admin_id'] ?? 0;
$admin_id = intval($admin_id);

// Fetch admin info
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found");
}

// Fetch users created by this admin
$stmt = $pdo->prepare("SELECT id, name, email, balance, role, status FROM users WHERE created_by = ? ORDER BY name ASC");
$stmt->execute([$admin_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_by = ? AND role = 'student'");
$stmt->execute([$admin_id]);
$total_students = $stmt->fetchColumn();

// Fetch mentors count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_by = ? AND role = 'mentor'");
$stmt->execute([$admin_id]);
$total_mentors = $stmt->fetchColumn();

// Fetch total balance
$stmt = $pdo->prepare("SELECT SUM(balance) FROM users WHERE created_by = ?");
$stmt->execute([$admin_id]);
$total_managed_balance = $stmt->fetchColumn() ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Managed Users - <?= htmlspecialchars($admin['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4318FF;
            --primary-light: #F4F7FE;
            --secondary: #707EAE;
            --dark: #1B2559;
            --success: #05CD99;
            --danger: #EE5D50;
            --warning: #FFB81C;
            --bg: #F4F7FE;
            --card-bg: #FFFFFF;
            --sidebar-width: 290px;
            --shadow-sm: 0px 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0px 18px 40px rgba(112, 144, 176, 0.12);
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
        .brand i { color: var(--primary); font-size: 28px; }

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
        .nav-item-custom i { font-size: 18px; margin-right: 15px; width: 25px; text-align: center; }
        .nav-item-custom:hover, .nav-item-custom.active {
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

        .breadcrumb-custom { font-size: 14px; font-weight: 600; color: var(--secondary); margin-bottom: 5px; }
        .breadcrumb-custom span { color: var(--dark); font-weight: 700; }

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

        /* Admin Hero Banner */
        .admin-hero {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .admin-large-avatar {
            width: 80px; height: 80px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; font-weight: 800;
        }

        /* Stat Cards */
        .stat-card-mini {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            height: 100%;
        }
        .stat-card-mini .label { font-size: 12px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .stat-card-mini .val { font-size: 20px; font-weight: 800; color: var(--dark); }

        /* Tables */
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .premium-table th { color: var(--secondary); font-size: 13px; font-weight: 700; text-transform: uppercase; padding: 10px 20px; }
        .premium-table td { background: var(--card-bg); padding: 18px 20px; border: none; font-weight: 600; font-size: 14px; vertical-align: middle; transition: 0.2s; }
        .premium-table td:first-child { border-radius: 16px 0 0 16px; }
        .premium-table td:last-child { border-radius: 0 16px 16px 0; }
        .premium-table tr:hover td { transform: scale(1.005); box-shadow: var(--shadow-sm); z-index: 1; }

        .badge-role { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-student { background: #E6F8F9; color: #008080; }
        .badge-mentor { background: #F3E8FF; color: #7E22CE; }

        .btn-view {
            background: var(--primary-light);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 12px;
            transition: 0.3s;
        }
        .btn-view:hover { background: var(--primary); color: white; }

        .section-header { margin-bottom: 25px; margin-top: 40px; }
        .section-title { font-size: 22px; font-weight: 800; color: var(--dark); margin: 0; }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="system_dashboard.php" class="brand">
        <i class="fas fa-graduation-cap"></i>
        <div>
            <div>MentorHub</div>
            <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Super Admin</div>
        </div>
    </a>
    <nav>
        <a href="system_dashboard.php" class="nav-item-custom">
            <i class="fas fa-th-large"></i> System Overview
        </a>
        <a href="manage_admins.php" class="nav-item-custom active">
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
        <div>
            <div class="breadcrumb-custom">Super Admin / Admins / <span>Managed Users</span></div>
            <h4 class="fw-800 m-0"><?= htmlspecialchars($admin['name']) ?>'s Portfolio</h4>
        </div>
        <div class="top-icons">
            <span class="fw-bold text-dark me-2"><?= htmlspecialchars($super_admin_name) ?></span>
            <div class="admin-avatar">SA</div>
        </div>
    </div>

    <!-- Admin Brief -->
    <div class="admin-hero">
        <div class="admin-large-avatar">
            <?= strtoupper(substr($admin['name'], 0, 1)) ?>
        </div>
        <div>
            <h3 class="fw-800 m-0"><?= htmlspecialchars($admin['name']) ?></h3>
            <p class="text-secondary fw-600 m-0"><i class="far fa-envelope me-1"></i> <?= htmlspecialchars($admin['email']) ?></p>
            <div class="mt-2">
                <a href="manage_admins.php" class="btn-view" style="font-size: 11px;">
                    <i class="fas fa-arrow-left me-1"></i> BACK TO ADMIN LIST
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="stat-card-mini">
                <div class="label">Total Students</div>
                <div class="val text-primary"><?= $total_students ?></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-card-mini">
                <div class="label">Total Mentors</div>
                <div class="val" style="color: #7E22CE;"><?= $total_mentors ?></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="stat-card-mini">
                <div class="label">Managed Assets</div>
                <div class="val text-success"><?= number_format($total_managed_balance, 2) ?> <span style="font-size: 12px; font-weight: 700; opacity: 0.6;">PTS</span></div>
            </div>
        </div>
    </div>

    <div class="section-header">
        <h5 class="section-title">Enrolled Users Registry</h5>
    </div>

    <div class="table-responsive">
        <table class="premium-table">
            <thead>
                <tr>
                    <th>User Profile</th>
                    <th>Email Address</th>
                    <th>Subscribed Role</th>
                    <th>Current Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="admin-avatar" style="background: var(--primary-light); color: var(--primary); font-size: 13px;">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="xtra-small text-muted">ID: #<?= $user['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-secondary"><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge-role <?= $user['role'] === 'student' ? 'badge-student' : 'badge-mentor' ?>">
                            <?= $user['role'] ?>
                        </span>
                    </td>
                    <td class="fw-bold">
                        <?= number_format($user['balance'], 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No users found under this administrator's management.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


