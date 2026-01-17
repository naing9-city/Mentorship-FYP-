<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../../includes/db.php';
$user_id = $_SESSION['user_id'];

// --- FETCH DATA ---
$stmt = $pdo->prepare("SELECT name, balance, mentor_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'];
$wallet = $user['balance'];
$mentor_status = $user['mentor_status'];

// Next Upcoming Session (Accepted, Future)
$stmt = $pdo->prepare("
    SELECT a.*, u.name as mentor_name 
    FROM appointments a 
    JOIN users u ON a.mentor_id = u.id 
    WHERE a.student_id = ? AND a.status = 'accepted' AND a.scheduled_at > NOW()
    ORDER BY a.scheduled_at ASC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$next_session = $stmt->fetch(PDO::FETCH_ASSOC);

// Unread Messages Count (Disabled: 'is_read' column missing)
// $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
// $stmt->execute([$user_id]);
// $unread_msg_count = $stmt->fetchColumn();
$unread_msg_count = 0;

// Recent Sessions (Completed or Past)
$stmt = $pdo->prepare("
    SELECT a.*, u.name as mentor_name, r.rating as user_rating
    FROM appointments a 
    JOIN users u ON a.mentor_id = u.id 
    LEFT JOIN ratings r ON a.id = r.appointment_id AND r.student_id = ?
    WHERE a.student_id = ? AND (a.status = 'completed' OR a.scheduled_at < NOW())
    ORDER BY a.scheduled_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MentorHub Student Dashboard</title>
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
            color: var(--secondary);
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

        /* Dashboard Overview */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -1px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .premium-card {
            background: var(--white);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
        }

        .premium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(112, 144, 176, 0.12);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 24px;
            box-shadow: inset 0 0 15px rgba(67, 24, 255, 0.05);
        }

        .card-icon.yellow {
            background: #FFF9E6;
            color: #FFB81C;
        }

        .card-icon.green {
            background: #E6FFFB;
            color: #05CD99;
        }

        .card-info .label {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-info .value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
        }

        /* Layout Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .panel {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .panel-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 25px;
            letter-spacing: -0.5px;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .action-card {
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 14px;
        }

        .action-blue {
            background: var(--light-bg);
            color: var(--primary-blue);
        }

        .action-purple {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            color: white;
        }

        .action-yellow {
            background: #FFF9E6;
            color: #FFB81C;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .action-card i {
            font-size: 20px;
        }

        /* Session List */
        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f4ff;
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .session-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f1f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 800;
        }

        .session-details .name {
            font-weight: 800;
            color: var(--text-dark);
            font-size: 16px;
            margin: 0;
        }

        .session-details .meta {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
            font-weight: 600;
        }

        .btn-view {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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
            <a href="index.php" class="nav-item active">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="find_mentor.php" class="nav-item">
                <i class="fas fa-users"></i> Mentors
            </a>
            <a href="my_appointments.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i> Appointments
            </a>
            <a href="chat.php" class="nav-item">
                <i class="fas fa-comment-dots"></i> Messages
                <?php if ($unread_msg_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto"><?= $unread_msg_count ?></span>
                <?php endif; ?>
            </a>

            <div class="section-label">Education</div>
            <a href="learn.php" class="nav-item">
                <i class="fas fa-book-open"></i> Learning Feed
            </a>

            <div class="section-label">Finance</div>
            <a href="wallet.php" class="nav-item">
                <i class="fas fa-wallet"></i> My Wallet
            </a>
            <a href="wallet.php?tab=history" class="nav-item">
                <i class="fas fa-history"></i> Transactions
            </a>

            <?php if ($mentor_status === 'approved'): ?>
                <div class="section-label">Mentor Mode</div>
                <a href="../mentor/index.php" class="nav-item"
                    style="color: var(--primary-blue); background: rgba(67, 24, 255, 0.05);">
                    <i class="fas fa-exchange-alt"></i> Switch to Mentor
                </a>
            <?php elseif ($mentor_status !== 'pending'): ?>
                <div class="section-label">Opportunities</div>
                <a href="apply_mentor.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> Become a Mentor
                </a>
            <?php endif; ?>
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
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search mentors, topics, IDs...">
            </div>
            <div class="top-icons">
                <i class="far fa-bell icon-btn"></i>
                <i class="far fa-moon icon-btn"></i>
                <i class="fas fa-info-circle icon-btn"></i>
                <div class="user-avatar">
                    <?= substr($user_name, 0, 1) ?>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="page-header">
            <h1>Dashboard Overview</h1>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="premium-card">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-info">
                    <div class="label">My Balance</div>
                    <div class="value"><?= number_format($wallet, 2) ?> Points</div>
                </div>
            </div>

            <div class="premium-card">
                <div class="card-icon yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-info">
                    <div class="label">Next Session</div>
                    <div class="value" style="font-size: 16px;">
                        <?php if ($next_session): ?>
                            <?= date('M d, g:i A', strtotime($next_session['scheduled_at'])) ?>
                        <?php else: ?>
                            None Scheduled
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="premium-card">
                <div class="card-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-info">
                    <div class="label">Mentor Status</div>
                    <div class="value" style="font-size: 18px; text-transform: capitalize;">
                        <?= $mentor_status ?: 'Student' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout Grid -->
        <div class="bottom-grid">

            <!-- Left Column -->
            <div class="left-col">

                <!-- Quick Actions Panel -->
                <div class="panel mb-4">
                    <div class="panel-title">Quick Actions</div>
                    <div class="quick-actions-grid">
                        <a href="find_mentor.php" class="action-card action-blue">
                            <i class="fas fa-user-plus"></i>
                            Find Mentor
                        </a>
                        <a href="wallet.php" class="action-card action-purple">
                            <i class="fas fa-credit-card"></i>
                            Top-up Wallet
                        </a>
                        <a href="my_appointments.php" class="action-card action-yellow">
                            <i class="fas fa-exchange-alt"></i>
                            Schedules
                        </a>
                    </div>
                </div>

                <!-- Recent Sessions Panel -->
                <div class="panel">
                    <div class="panel-title">Recent Activity</div>
                    <div class="session-list">
                        <?php foreach ($recent_sessions as $session): ?>
                            <div class="session-item">
                                <div class="session-user">
                                    <div class="session-avatar">
                                        <?= strtoupper(substr($session['mentor_name'], 0, 1)) ?>
                                    </div>
                                    <div class="session-details">
                                        <p class="name">Mentor <?= htmlspecialchars($session['mentor_name']) ?></p>
                                        <p class="meta"><?= date('M d, Y', strtotime($session['scheduled_at'])) ?> •
                                            <?= ucfirst($session['status']) ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="my_appointments.php" class="btn-view">Details</a>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($recent_sessions)): ?>
                            <div class="text-center py-4 text-muted">
                                <p>No recent activity found.</p>
                                <a href="find_mentor.php" class="btn btn-sm btn-outline-primary rounded-pill">Find a
                                    Mentor</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>


        </div>

    </div>

    <!-- Bootstrap Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
l