<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../../includes/db.php';
$student_id = $_SESSION['user_id'];

// Get student's info for sidebar
$stmt = $pdo->prepare("SELECT name, mentor_status FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user_data['name'];
$mentor_status = $user_data['mentor_status'];
$unread_msg_count = 0; // Column missing in DB, keeping consistent with index.php

// Handle Cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT points, status FROM appointments WHERE id = ? AND student_id = ?");
    $stmt->execute([$appt_id, $student_id]);
    $appt = $stmt->fetch();

    if ($appt && in_array($appt['status'], ['pending', 'accepted'])) {
        $undo_points = floatval($appt['points']);

        $pdo->beginTransaction();
        try {
            // Refund points
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$undo_points, $student_id]);

            // Mark as cancelled
            $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$appt_id]);

            $pdo->commit();
            header("Location: my_appointments.php?success=cancelled");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Cancellation failed: " . $e->getMessage();
        }
    }
}

// Tabs logic
$current_tab = $_GET['tab'] ?? 'history'; // Default to History as per image
if (!in_array($current_tab, ['upcoming', 'history']))
    $current_tab = 'history';

// Define status filters for tabs
$status_filter = ($current_tab === 'upcoming')
    ? "a.status IN ('pending', 'accepted')"
    : "a.status IN ('completed', 'cancelled', 'rejected')";

// Fetch appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS mentor_name, u.profile_photo AS mentor_photo, r.rating AS student_rating
    FROM appointments a
    LEFT JOIN users u ON a.mentor_id = u.id
    LEFT JOIN ratings r ON a.id = r.appointment_id AND r.student_id = a.student_id
    WHERE a.student_id = ? AND $status_filter
    ORDER BY a.scheduled_at DESC
");
$stmt->execute([$student_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for the tabs (optional but helpful)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status IN ('pending', 'accepted')");
$stmt->execute([$student_id]);
$count_upcoming = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status IN ('completed', 'cancelled', 'rejected')");
$stmt->execute([$student_id]);
$count_history = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - MentorHub</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* Premium Blue Palette */
            --primary-blue: #4318FF;
            --primary-dark: #11047A;
            --accent-blue: #00D1FF;
            --light-bg: #F4F7FE;
            --white: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #707EAE;
            --shadow-premium: 0px 40px 80px rgba(67, 24, 255, 0.15);
            --sidebar-width: 290px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F4F7FE 0%, #E9EDF7 100%);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            display: flex;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 15px;
            margin-bottom: 50px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .brand-logo:hover {
            transform: scale(1.02);
        }

        .brand-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .section-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 25px 15px 10px;
            opacity: 0.8;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 700;
            border-radius: 16px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .nav-item.active {
            background: var(--light-bg);
            color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.05);
        }

        .nav-item:hover:not(.active) {
            background: #F8FAFC;
            color: var(--text-dark);
            transform: translateX(5px);
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid #f1f4ff;
            padding-top: 20px;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            flex: 1;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .search-box {
            background: var(--white);
            border-radius: 20px;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 400px;
            box-shadow: 0 10px 30px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .search-box input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
            font-weight: 500;
            font-size: 14px;
            color: var(--secondary);
        }

        .top-icons {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white);
            padding: 10px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .icon-btn {
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: 0.2s;
        }

        .icon-btn:hover {
            color: var(--primary-blue);
            transform: scale(1.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(67, 24, 255, 0.2);
        }

        /* Tabs Styling */
        .premium-tabs {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }

        .p-tab-btn {
            padding: 15px 5px;
            font-size: 15px;
            font-weight: 800;
            color: var(--text-muted);
            text-decoration: none;
            position: relative;
            transition: all 0.3s;
            letter-spacing: -0.2px;
        }

        .p-tab-btn.active {
            color: var(--primary-blue);
        }

        .p-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-blue);
            border-radius: 3px 3px 0 0;
        }

        /* Appointments Panel */
        .panel {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .premium-table th {
            font-size: 12px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            padding: 0 20px 10px;
            letter-spacing: 1px;
        }

        .premium-table td {
            background: #F8FAFC;
            padding: 20px;
            font-size: 15px;
            font-weight: 700;
            vertical-align: middle;
            border: 1px solid rgba(226, 232, 240, 0.3);
        }

        .premium-table td:first-child {
            border-radius: 20px 0 0 20px;
            border-right: none;
        }

        .premium-table td:last-child {
            border-radius: 0 20px 20px 0;
            border-left: none;
        }

        .premium-table td:not(:first-child):not(:last-child) {
            border-left: none;
            border-right: none;
        }

        .mentor-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mentor-img {
            width: 45px;
            height: 45px;
            border-radius: 14px;
            object-fit: cover;
            background: var(--light-bg);
            border: 2px solid var(--white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #FFF9E6;
            color: var(--warning);
        }

        .status-accepted {
            background: #F0EDFF;
            color: #8C62FF;
        }

        .status-completed {
            background: #E6FFFB;
            color: #05CD99;
        }

        .status-cancelled,
        .status-rejected {
            background: #FFF1F0;
            color: #EE5D50;
        }

        .btn-premium-sm {
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-p-primary {
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.15);
        }

        .btn-p-light {
            background: var(--light-bg);
            color: var(--primary-blue);
        }

        .btn-p-danger {
            background: #FFF1F0;
            color: #EE5D50;
            border: none;
        }

        .btn-premium-sm:hover {
            transform: translateY(-2px);
            opacity: 0.95;
            color: inherit;
        }

        .btn-p-primary:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(67, 24, 255, 0.25);
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="brand-logo">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div class="brand-title">MentorHub</div>
                <div
                    style="font-size: 11px; color: var(--text-muted); font-weight: 800; margin-top: -4px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                    Student Portal</div>
            </div>
        </a>

        <div class="nav-menu">
            <a href="index.php" class="nav-item">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="find_mentor.php" class="nav-item">
                <i class="fas fa-users"></i> Mentors
            </a>
            <a href="my_appointments.php" class="nav-item active">
                <i class="fas fa-calendar-alt"></i> Appointments
            </a>
            <a href="chat.php" class="nav-item">
                <i class="fas fa-comment-dots"></i> Messages
            </a>

            <div class="section-label">Education</div>
            <a href="learn.php" class="nav-item">
                <i class="fas fa-book-open"></i> Learning Feed
            </a>

            <div class="section-label">Finance</div>
            <a href="wallet.php" class="nav-item">
                <i class="fas fa-wallet"></i> My Wallet
            </a>

            <?php if ($mentor_status === 'approved'): ?>
                <div class="section-label">Mentor Mode</div>
                <a href="../mentor/index.php" class="nav-item" style="color: var(--primary-blue);">
                    <i class="fas fa-exchange-alt"></i> Switch to Mentor
                </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Bar -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search sessions, mentors...">
            </div>
            <div class="top-icons">
                <i class="far fa-bell icon-btn"></i>
                <i class="far fa-moon icon-btn"></i>
                <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
            </div>
        </div>

        <div class="page-header mb-4">
            <h1 class="fw-800" style="letter-spacing: -1px;">My Appointments</h1>
            <p class="text-muted fw-600">Track and manage your scheduled learning sessions.</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4 p-3 fw-700">
                <i class="fas fa-check-circle me-2"></i> Session successfully cancelled.
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="premium-tabs">
            <a href="?tab=upcoming" class="p-tab-btn <?= $current_tab === 'upcoming' ? 'active' : '' ?>">Upcoming
                (<?= $count_upcoming ?>)</a>
            <a href="?tab=history" class="p-tab-btn <?= $current_tab === 'history' ? 'active' : '' ?>">History
                (<?= $count_history ?>)</a>
        </div>

        <!-- Panel -->
        <div class="panel">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Mentor</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appointments): ?>
                            <?php foreach ($appointments as $a): ?>
                                <?php
                                $photo = $a['mentor_photo'] ? "../../{$a['mentor_photo']}" : "https://ui-avatars.com/api/?name=" . urlencode($a['mentor_name'] ?? 'M') . "&background=random";
                                ?>
                                <tr>
                                    <td>
                                        <div class="mentor-cell">
                                            <img src="<?= $photo ?>" class="mentor-img" alt="Mentor">
                                            <div class="fw-800 text-dark"><?= htmlspecialchars($a['mentor_name'] ?? '-') ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-800"><?= date('M d, Y', strtotime($a['scheduled_at'])) ?></div>
                                        <div class="small text-muted fw-700"><?= date('h:i A', strtotime($a['scheduled_at'])) ?>
                                        </div>
                                    </td>
                                    <td><span class="fw-800"><?= $a['duration_minutes'] / 60 ?> Hours</span></td>
                                    <td><span class="text-dark fw-800"><?= number_format($a['points'], 0) ?></span></td>
                                    <td>
                                        <span class="status-badge status-<?= $a['status'] ?>">
                                            <?= $a['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <?php if ($a['status'] === 'completed' && !$a['student_rating']): ?>
                                                <a href="rate_mentor.php?appointment_id=<?= $a['id'] ?>"
                                                    class="btn-premium-sm btn-p-light"><i class="fas fa-star"></i> Rate</a>
                                            <?php elseif ($a['status'] === 'accepted'): ?>
                                                <a href="../video_room.php?id=<?= $a['id'] ?>"
                                                    class="btn-premium-sm btn-p-primary"><i class="fas fa-video"></i> Join
                                                    Session</a>
                                                <a href="?action=cancel&id=<?= $a['id'] ?>" class="btn-premium-sm btn-p-danger"
                                                    onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                            <?php elseif ($a['status'] === 'pending'): ?>
                                                <a href="?action=cancel&id=<?= $a['id'] ?>" class="btn-premium-sm btn-p-danger"
                                                    onclick="return confirm('Cancel request?')">Withdraw</a>
                                            <?php elseif ($a['status'] === 'completed' && $a['student_rating']): ?>
                                                <span class="text-success small fw-800"><i class="fas fa-check-circle"></i>
                                                    Rated</span>
                                            <?php else: ?>
                                                <span class="text-muted fw-800">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted fw-700">No sessions found in this
                                    section.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>