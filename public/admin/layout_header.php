<?php
// layout_header.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_name = $stmt->fetchColumn() ?: 'Admin';

// Fetch Pending Counts for Notifications
// 1. Pending Mentor Applications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM mentor_applications WHERE admin_id = ? AND status = 'pending'");
$stmt->execute([$admin_id]);
$pending_mentor_count = $stmt->fetchColumn();

// 2. Pending Top-up Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topup_requests WHERE admin_id = ? AND status = 'pending'");
$stmt->execute([$admin_id]);
$pending_topup_count = $stmt->fetchColumn();

// 3. Pending Withdrawal Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawal_requests WHERE admin_id = ? AND status = 'pending'");
$stmt->execute([$admin_id]);
$pending_withdraw_count = $stmt->fetchColumn();

$total_pending_count = $pending_mentor_count + $pending_topup_count + $pending_withdraw_count;

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_view = $_GET['view'] ?? '';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'MentorHub Admin' ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            overflow-x: hidden;
            margin: 0;
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
            transition: 0.3s;
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
            font-size: 15px;
            position: relative;
        }

        .nav-item-custom i {
            font-size: 18px;
            margin-right: 15px;
            width: 25px;
            text-align: center;
        }

        .nav-item-custom:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-item-custom.active {
            background: var(--primary-light);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .nav-item-custom.active i {
            color: var(--primary);
        }

        .nav-item-custom.active::after {
            content: '';
            position: absolute;
            right: 0;
            width: 4px;
            height: 36px;
            background: var(--primary);
            border-radius: 4px 0 0 4px;
        }

        .notif-badge {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--danger);
            color: white;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
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
            border-radius: 30px;
            padding: 12px 25px;
            width: 400px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .search-inner input {
            border: none;
            outline: none;
            width: 100%;
            margin-left: 12px;
            font-size: 14px;
            background: transparent;
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

        .icon-btn {
            color: var(--secondary);
            cursor: pointer;
            transition: 0.2s;
            font-size: 18px;
            position: relative;
        }

        .icon-btn:hover {
            color: var(--primary);
        }

        .bell-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 9px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
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

        /* Common Premium Card & Table Styles */
        .premium-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            border: none;
            box-shadow: var(--shadow-md);
            height: 100%;
            transition: 0.3s;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: var(--primary);
            border-radius: 10px;
        }

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
            border: none;
        }

        .premium-table td {
            background: var(--card-bg);
            padding: 20px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            vertical-align: middle;
            transition: 0.2s;
        }

        .premium-table td:first-child {
            border-radius: 20px 0 0 20px;
        }

        .premium-table td:last-child {
            border-radius: 0 20px 20px 0;
        }

        .premium-table tr:hover td {
            background: var(--bg);
            box-shadow: none;
            z-index: 1;
        }

        .btn-premium {
            padding: 10px 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-p-primary {
            background: var(--primary);
            color: white;
        }

        .btn-p-primary:hover {
            background: #3311db;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 24, 255, 0.4);
            color: white;
        }

        .btn-p-info {
            background: var(--primary-light);
            color: var(--primary);
        }

        .btn-p-info:hover {
            color: var(--primary);
            background: #e3ebff;
        }

        .btn-p-danger {
            background: #FFF5F5;
            color: var(--danger);
        }

        .btn-p-danger:hover {
            color: var(--danger);
            background: #ffebeb;
        }

        .btn-p-warning {
            background: #FFFCEB;
            color: var(--warning);
        }

        .btn-p-warning:hover {
            color: var(--warning);
            background: #fff8d6;
        }

        .btn-p-secondary {
            background: var(--card-bg);
            color: var(--secondary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-p-secondary:hover {
            color: var(--dark);
        }

        .badge-premium {
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-active,
        .badge-approved {
            background: #E6FFFB;
            color: var(--success);
        }

        .badge-suspended,
        .badge-rejected {
            background: #FFF1F0;
            color: var(--danger);
        }

        .badge-pending {
            background: #FFF9E6;
            color: var(--warning);
        }

        .logout-item {
            margin-top: 30px !important;
            background: rgba(238, 93, 80, 0.05);
            color: var(--danger) !important;
            border: 1px solid rgba(238, 93, 80, 0.1);
        }

        .logout-item:hover {
            background: var(--danger) !important;
            color: white !important;
        }
    </style>
    <?php if (isset($extra_css))
        echo $extra_css; ?>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="users.php" class="brand">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div>MentorHub</div>
                <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Admin Portal
                </div>
            </div>
        </a>
        <nav>
            <a href="users.php?view=dashboard"
                class="nav-item-custom <?= ($current_page === 'users.php' && ($current_view === 'dashboard' || !$current_view)) ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="users.php?view=students"
                class="nav-item-custom <?= ($current_page === 'users.php' && $current_view === 'students') ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a href="users.php?view=mentors"
                class="nav-item-custom <?= ($current_page === 'users.php' && $current_view === 'mentors') ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i> Mentor List
            </a>
            <a href="mentor_applications.php"
                class="nav-item-custom <?= $current_page === 'mentor_applications.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Applications
                <?php if ($pending_mentor_count > 0): ?>
                    <span class="notif-badge"><?= $pending_mentor_count ?></span>
                <?php endif; ?>
            </a>

            <div class="px-4 mt-5 mb-2 small text-uppercase fw-bold text-muted" style="letter-spacing: 1px;">Finance
            </div>
            <a href="admin_topup.php"
                class="nav-item-custom <?= $current_page === 'admin_topup.php' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i> Self Top-up
            </a>
            <a href="topup.php" class="nav-item-custom <?= $current_page === 'topup.php' ? 'active' : '' ?>">
                <i class="fas fa-wallet"></i> Top-up Req
                <?php if ($pending_topup_count > 0): ?>
                    <span class="notif-badge"><?= $pending_topup_count ?></span>
                <?php endif; ?>
            </a>
            <a href="withdrawals.php"
                class="nav-item-custom <?= $current_page === 'withdrawals.php' ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i> Withdrawals
                <?php if ($pending_withdraw_count > 0): ?>
                    <span class="notif-badge"><?= $pending_withdraw_count ?></span>
                <?php endif; ?>
            </a>

            <a href="logout.php" class="nav-item-custom logout-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <h2 class="m-0" style="font-size: 28px; font-weight: 800; letter-spacing: -1px;"><?= $greeting ?>, <span style="background: linear-gradient(135deg, var(--dark), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>! 👋</h2>
            <div class="top-icons">
                <div class="icon-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($total_pending_count > 0): ?>
                        <span class="bell-badge"><?= $total_pending_count ?></span>
                    <?php endif; ?>
                </div>
                <i class="fas fa-moon icon-btn"></i>
                <i class="fas fa-info-circle icon-btn"></i>
                <div class="admin-avatar"><?= strtoupper(substr($_SESSION['role'], 0, 1)) ?></div>
            </div>
        </div>
