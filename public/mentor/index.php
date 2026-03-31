<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Ensure database connection is available
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role, mentor_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$db_user = $stmt->fetch();

if (!$db_user) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Sync session role if user is approved as mentor in database
if ($db_user['role'] === 'mentor' && $db_user['mentor_status'] === 'approved') {
    $_SESSION['role'] = 'mentor';
}

// strictly enforce mentor access only for approved mentors
if ($_SESSION['role'] !== 'mentor' || $db_user['mentor_status'] !== 'approved') {
    header("Location: ../student/index.php");
    exit;
}

$mentor_id = $_SESSION['user_id'];

/* ----------------------------------------------------------
   BACKEND LOGIC (Actions)
-----------------------------------------------------------*/
if (isset($_GET['action'], $_GET['id'])) {
    $appointment_id = $_GET['id'];
    $action = $_GET['action'];

    $stmt = $pdo->prepare("SELECT student_id, points, mentor_id, duration_minutes, mentor_paid FROM appointments WHERE id = ?");
    $stmt->execute([$appointment_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt || $appt['mentor_id'] != $mentor_id) {
        die("Unauthorized access.");
    }

    $student_id = $appt['student_id'];
    $points = floatval($appt['points']); // Explicit float for safety

    if ($action === 'accept') {
        $pdo->prepare("UPDATE appointments SET status = 'accepted' WHERE id = ?")->execute([$appointment_id]);
        header("Location: index.php?success=accepted");
        exit();
    } elseif ($action === 'reject') {
        // Refund student
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$points, $student_id]);
        $pdo->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?")->execute([$appointment_id]);
        header("Location: index.php?success=rejected");
        exit();
    } elseif ($action === 'complete') {
        if ($appt['mentor_paid'] == 1) {
            header("Location: index.php?success=already_paid");
            exit();
        }

        $total_points = $points;

        $mentor_earning = $total_points * 0.90; // 90% to Mentor
        $system_earning = $total_points * 0.10; // 10% to System (10% Commission)

        $pdo->beginTransaction();
        try {
            // Mentor wallet (Balance + Teaching Balance)
            if ($mentor_earning > 0) {
                $pdo->prepare("UPDATE users SET balance = balance + ?, teaching_balance = teaching_balance + ? WHERE id = ?")
                    ->execute([$mentor_earning, $mentor_earning, $mentor_id]);
            }
            // System wallet (Commission)
            $pdo->prepare("UPDATE system_wallet SET balance = balance + ? WHERE id = 1")->execute([$system_earning]);

            // Mark complete
            $pdo->prepare("UPDATE appointments SET status = 'completed', mentor_paid = 1 WHERE id = ?")->execute([$appointment_id]);

            $pdo->commit();
            header("Location: index.php?success=completed");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Transaction failed: " . $e->getMessage());
        }
    }
}

/* ----------------------------------------------------------
   DATA FETCHING FOR DASHBOARD
-----------------------------------------------------------*/
// 1. Mentor Info & Balance
$stmt = $pdo->prepare("SELECT name, balance, teaching_balance, profile_photo FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine Avatar Media
$is_video_avatar = false;
$avatar_html = '<div class="user-avatar-initials"><i class="fas fa-user"></i></div>';
$profile_photo = $mentor['profile_photo'] ?? null;

if ($profile_photo) {
    $ext = strtolower(pathinfo($profile_photo, PATHINFO_EXTENSION));
    $is_video_avatar = in_array($ext, ['mp4', 'webm', 'ogg']);
    $media_url = '../uploads/' . htmlspecialchars($profile_photo);
    if ($is_video_avatar) {
        $avatar_html = '<video src="' . $media_url . '" class="user-avatar-media" autoplay loop muted playsinline></video>';
    } else {
        $avatar_html = '<img src="' . $media_url . '" class="user-avatar-media">';
    }
}

// 2. Pending Payout (Sum of points for 'accepted' appointments * 0.9)
$stmt = $pdo->prepare("SELECT SUM(points) FROM appointments WHERE mentor_id = ? AND status = 'accepted'");
$stmt->execute([$mentor_id]);
$pending_points = $stmt->fetchColumn() ?: 0;
$pending_payout = $pending_points * 0.9;

// 3. Profile Rating
$stmt = $pdo->prepare("SELECT AVG(rating), COUNT(rating) FROM ratings WHERE mentor_id = ?");
$stmt->execute([$mentor_id]);
$rating_data = $stmt->fetch(PDO::FETCH_NUM);
$avg_rating = $rating_data[0] ? round($rating_data[0], 1) : 0;
$review_count = $rating_data[1];

// 4. Booking Requests (Pending)
$stmt = $pdo->prepare("
    SELECT a.id, a.scheduled_at, u.name as student_name, a.points
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.mentor_id = ? AND a.status = 'pending'
    ORDER BY a.created_at DESC
");
$stmt->execute([$mentor_id]);
$booking_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Today's/Upcoming Schedule (Accepted)
// Fetching accepted appointments that are NOT completed
$stmt = $pdo->prepare("
    SELECT a.id, a.scheduled_at, a.duration_minutes, u.name as student_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.mentor_id = ? AND a.status = 'accepted'
    ORDER BY a.scheduled_at ASC
");
$stmt->execute([$mentor_id]);
$upcoming_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Mentor Dashboard - MentorHub</title>
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
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 20px 15px 10px;
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px 40px;
            flex: 1;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .search-box-top {
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

        .search-box-top input {
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

        .user-avatar-initials {
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
        
        .user-avatar-media {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(67, 24, 255, 0.2);
        }

        /* Stats Dashboard Styling */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(112, 144, 176, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: inset 0 0 15px rgba(67, 24, 255, 0.05);
        }

        .stat-icon.blue {
            background: var(--light-bg);
            color: var(--primary-blue);
        }

        .stat-icon.purple {
            background: var(--light-bg);
            color: #7033FF;
        }

        .stat-icon.green {
            background: #E6FAF5;
            color: #05CD99;
        }

        .stat-icon.orange {
            background: #FFF4E6;
            color: #FFB81C;
        }

        .stat-details h4 {
            font-size: 26px;
            font-weight: 800;
            margin: 0;
            color: var(--text-dark);
        }

        .stat-details p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Split Panels */
        .dashboard-panels {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 30px;
        }

        .panel {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .panel-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .btn-view-all {
            font-size: 14px;
            color: var(--primary-blue);
            font-weight: 800;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-view-all:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Schedule Item */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .schedule-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #f1f4ff;
            transition: 0.3s;
        }

        .schedule-item:hover {
            transform: translateY(-3px);
            border-color: var(--primary-light);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .sched-main {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sched-date-box {
            background: white;
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            min-width: 60px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .sched-day {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-blue);
            display: block;
            line-height: 1;
        }

        .sched-month {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .sched-info h6 {
            margin: 0;
            font-weight: 800;
            color: var(--text-dark);
            font-size: 15px;
        }

        .sched-info p {
            margin: 2px 0 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .btn-action-sm {
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-action-blue {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            color: white;
        }

        .btn-action-outline {
            border: 2px solid rgba(226, 232, 240, 0.5);
            color: var(--text-dark);
            background: var(--white);
        }

        .btn-action-sm:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 24, 255, 0.2);
        }

        .btn-action-outline:hover {
            background: var(--light-bg);
            color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        /* Request Item */
        .request-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f4ff;
        }

        .request-item:last-child {
            border-bottom: none;
        }

        .requester {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .requester-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: var(--dark);
        }

        .requester-info h6 {
            margin: 0;
            font-weight: 800;
            color: var(--text-dark);
            font-size: 14px;
        }

        .requester-info span {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .req-actions {
            display: flex;
            gap: 8px;
        }

        .req-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
        }

        .req-accept {
            background: #E6FAF5;
            color: #05CD99;
        }

        .req-decline {
            background: #FFF4E6;
            color: #FFB81C;
        }

        .req-btn:hover {
            transform: scale(1.1);
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
                    Mentor Portal</div>
            </div>
        </a>

        <div class="nav-menu">
            <div class="section-label">Main Menu</div>
            <a href="index.php" class="nav-item active">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="posts.php" class="nav-item">
                <i class="fas fa-feather"></i> My Posts
            </a>
            <a href="withdraw.php" class="nav-item">
                <i class="fas fa-hand-holding-usd"></i> Withdrawals
            </a>
            <a href="schedule.php" class="nav-item">
                <i class="far fa-calendar-alt"></i> Schedule
                <?php if (count($booking_requests) > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"
                        style="font-size: 10px;"><?= count($booking_requests) ?></span>
                <?php endif; ?>
            </a>
            <a href="chat.php" class="nav-item">
                <i class="far fa-comments"></i> Messages
            </a>

            <div class="section-label">Profile</div>
            <a href="mentor_profile.php?id=<?= $mentor_id ?>" class="nav-item">
                <i class="far fa-user"></i> Public Profile
            </a>

            <div class="section-label">Mentor Mode</div>
            <a href="../student/index.php" class="nav-item"
                style="color: var(--primary-blue); background: rgba(67, 24, 255, 0.05);">
                <i class="fas fa-exchange-alt"></i> Switch to Student View
            </a>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Bar -->
        <div class="topbar">
            <h2 class="m-0" style="font-size: 28px; font-weight: 800; letter-spacing: -1px;"><?= $greeting ?>, <span style="background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= htmlspecialchars(explode(' ', $mentor['name'])[0]) ?></span>! 👋</h2>
            <div class="top-icons">
                <i class="far fa-bell icon-btn"></i>
                <i class="far fa-moon icon-btn"></i>
                <a href="mentor_profile.php?id=<?= $mentor_id ?>&edit=profile" class="user-avatar-container">
                    <?= $avatar_html ?>
                </a>
            </div>
        </div>

        <!-- Stats Dashboard Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h4>$<?= number_format($mentor['balance'], 2) ?></h4>
                    <p>Total Balance</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-details">
                    <h4>$<?= number_format($mentor['teaching_balance'], 2) ?></h4>
                    <p>Teaching Earnings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-details">
                    <h4>$<?= number_format($pending_payout, 2) ?></h4>
                    <p>Pending Payout</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-star"></i></div>
                <div class="stat-details">
                    <h4><?= $avg_rating ?></h4>
                    <p><?= $review_count ?> Reviews</p>
                </div>
            </div>
        </div>

        <div class="dashboard-panels">
            <!-- Upcoming Schedule Panel -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Upcoming Schedule</span>
                    <a href="schedule.php" class="btn-view-all">View All</a>
                </div>

                <div class="schedule-list">
                    <?php if (empty($upcoming_schedule)): ?>
                        <div class="text-center py-5">
                            <i class="far fa-calendar-times mb-3 opacity-20" style="font-size: 40px;"></i>
                            <p class="text-secondary fw-600">No classes scheduled today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_schedule as $sched): ?>
                            <div class="schedule-item">
                                <div class="sched-main">
                                    <div class="sched-date-box">
                                        <span class="sched-day"><?= date('d', strtotime($sched['scheduled_at'])) ?></span>
                                        <span class="sched-month"><?= date('M', strtotime($sched['scheduled_at'])) ?></span>
                                    </div>
                                    <div class="sched-info">
                                        <h6>Session with <?= htmlspecialchars($sched['student_name']) ?></h6>
                                        <p><i class="far fa-clock me-1"></i> Starting at
                                            <?= date('h:i A', strtotime($sched['scheduled_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="../video_room.php?id=<?= $sched['id'] ?>" class="btn-action-sm btn-action-outline">
                                        <i class="fas fa-video"></i>
                                    </a>
                                    <a href="?action=complete&id=<?= $sched['id'] ?>" class="btn-action-sm btn-action-blue"
                                        onclick="return confirm('Recieve payout and mark as completed?')">
                                        <i class="fas fa-check-circle me-1"></i> Complete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Requests Panel -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">New Requests</span>
                    <span class="badge bg-primary-light text-primary rounded-pill px-3 py-2 fw-800"
                        style="font-size: 10px;">
                        <?= count($booking_requests) ?> Pending
                    </span>
                </div>

                <div class="request-list">
                    <?php if (empty($booking_requests)): ?>
                        <div class="text-center py-5">
                            <i class="far fa-envelope-open mb-3 opacity-20" style="font-size: 30px;"></i>
                            <p class="text-secondary fw-700" style="font-size: 13px;">No pending requests.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($booking_requests as $req): ?>
                            <div class="request-item">
                                <div class="requester">
                                    <div class="requester-avatar">
                                        <?= strtoupper(substr($req['student_name'], 0, 1)) ?>
                                    </div>
                                    <div class="requester-info">
                                        <h6><?= htmlspecialchars($req['student_name']) ?></h6>
                                        <span><?= date('M d, h:i A', strtotime($req['scheduled_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="req-actions">
                                    <a href="?action=accept&id=<?= $req['id'] ?>" class="req-btn req-accept" title="Accept">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=reject&id=<?= $req['id'] ?>" class="req-btn req-decline" title="Decline"
                                        onclick="return confirm('Decline this request?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
