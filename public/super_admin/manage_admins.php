<?php
session_start();
require_once '../../includes/db.php';

// Super admin session check
if (!isset($_SESSION['super_admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

// Fetch admins
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name ASC");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$super_admin_name = $_SESSION['name'] ?? 'Super Admin';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Administrators - MentorHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #05CD99;
            --primary-light: #F0FDF4;
            --secondary: #707EAE;
            --dark: #1B2559;
            --success: #05CD99;
            --danger: #EE5D50;
            --bg: #F0FDF4;
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

        /* Tables */
        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .premium-table th {
            color: var(--secondary);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px 20px;
        }

        .premium-table td {
            background: var(--card-bg);
            padding: 20px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            vertical-align: middle;
        }

        .premium-table td:first-child {
            border-radius: 20px 0 0 20px;
        }

        .premium-table td:last-child {
            border-radius: 0 20px 20px 0;
        }

        .premium-table tr:hover td {
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
            z-index: 1;
            transition: 0.2s;
        }

        .btn-p-primary {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            border: none;
        }

        .btn-p-primary:hover {
            background: #065F46;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(5, 205, 153, 0.4);
            color: white;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            margin-top: 50px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="system_dashboard.php" class="brand" style="text-decoration: none;">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div>MentorHub</div>
                <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Super Admin
                </div>
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
                <div class="small fw-700 text-secondary mb-1">Super Admin / <span class="text-dark">Admin Hub</span>
                </div>
                <h4 class="fw-800 m-0">Administrator Directory</h4>
            </div>
            <div class="top-icons">
                <span class="fw-bold text-dark me-2"><?= htmlspecialchars($super_admin_name) ?></span>
                <div class="admin-avatar">SA</div>
            </div>
        </div>

        <div class="section-header">
            <h5 class="section-title">Active Administrators</h5>
            <a href="system_dashboard.php" class="btn-p-primary btn-sm">
                <i class="fas fa-plus me-1"></i> INVITE NEW ADMIN
            </a>
        </div>

        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Admin Name</th>
                        <th>Email Address</th>
                        <th>System ID</th>
                        <th class="text-end">Portfolio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="admin-avatar"
                                        style="background: var(--primary-light); color: var(--primary); font-size: 14px;">
                                        <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($admin['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-secondary"><?= htmlspecialchars($admin['email']) ?></td>
                            <td class="text-muted">#<?= $admin['id'] ?></td>
                            <td class="text-end">
                                <a href="admin_users.php?admin_id=<?= $admin['id'] ?>" class="btn-p-primary">
                                    Control Panel
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No administrators registered in the system.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>