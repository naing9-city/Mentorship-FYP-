<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../index.php");
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
    $points = floatval($appt['points']);

    if ($action === 'accept') {
        $pdo->prepare("UPDATE appointments SET status = 'accepted' WHERE id = ?")->execute([$appointment_id]);
        header("Location: schedule.php?tab=upcoming&success=accepted");
        exit();
    }
    elseif ($action === 'reject') {
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$points, $student_id]);
        $pdo->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?")->execute([$appointment_id]);
        header("Location: schedule.php?tab=history&success=rejected");
        exit();
    }
    elseif ($action === 'complete') {
        if ($appt['mentor_paid'] == 1) {
             header("Location: schedule.php?success=already_paid");
             exit();
        }

        $total_points = $points; 
        $mentor_earning = $total_points * 0.90; // 90% to Mentor
        $system_earning = $total_points * 0.10; // 10% to System (Super Admin)

        $pdo->beginTransaction();
        try {
            // 1. Update Monitor Balance
            if ($mentor_earning > 0) {
                $pdo->prepare("UPDATE users SET balance = balance + ?, teaching_balance = teaching_balance + ? WHERE id = ?")
                    ->execute([$mentor_earning, $mentor_earning, $mentor_id]);
            }

            // 2. Update System Wallet
            $pdo->prepare("UPDATE system_wallet SET balance = balance + ? WHERE id = 1")->execute([$system_earning]);

            // 3. Mark appointment as completed
            $pdo->prepare("UPDATE appointments SET status = 'completed', mentor_paid = 1 WHERE id = ?")->execute([$appointment_id]);
            
            $pdo->commit();
            header("Location: schedule.php?tab=history&success=completed");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Transaction failed: " . $e->getMessage());
        }
    }
}

/* ----------------------------------------------------------
   DATA FETCHING
-----------------------------------------------------------*/
$current_tab = $_GET['tab'] ?? 'upcoming';
if (!in_array($current_tab, ['upcoming', 'history'])) $current_tab = 'upcoming';

$status_filter = ($current_tab === 'upcoming') 
    ? "a.status IN ('pending', 'accepted')" 
    : "a.status IN ('completed', 'cancelled', 'rejected')";

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS student_name, u.profile_photo AS student_photo
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.mentor_id = ? AND $status_filter
    ORDER BY a.scheduled_at DESC
");
$stmt->execute([$mentor_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mentor info for sidebar/topbar
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - MentorHub</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4318FF;
            --primary-light: #F4F7FE;
            --secondary: #707EAE;
            --dark: #1B2559;
            --success: #05CD99;
            --danger: #EE5D50;
            --bg: #F4F7FE;
            --card-bg: #FFFFFF;
            --sidebar-width: 290px;
            --shadow-md: 0px 18px 40px rgba(112, 144, 176, 0.12);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            min-height: 100vh;
            margin: 0;
            display: flex;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            height: 100vh;
            position: fixed;
            left: 0; top: 0; padding: 40px 20px;
            display: flex; flex-direction: column; z-index: 1000;
        }
        .brand-logo { display: flex; align-items: center; gap: 12px; padding: 0 15px; margin-bottom: 50px; text-decoration: none; }
        .brand-logo i { font-size: 24px; color: var(--primary); }
        .brand-title { font-size: 24px; font-weight: 800; color: var(--dark); }

        .nav-item {
            display: flex; align-items: center; gap: 15px; padding: 14px 20px;
            color: var(--secondary); text-decoration: none; font-weight: 600;
            border-radius: 12px; transition: 0.3s; margin-bottom: 5px; font-size: 15px;
        }
        .nav-item.active { background: var(--primary-light); color: var(--primary); }
        .nav-item:hover:not(.active) { background: #f8fafc; color: var(--dark); }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 30px 40px; flex: 1; }

        /* Top Bar */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .top-icons {
            display: flex; align-items: center; gap: 20px; background: var(--card-bg);
            padding: 8px 15px; border-radius: 30px; box-shadow: var(--shadow-md);
        }
        .icon-btn { color: var(--secondary); font-size: 18px; cursor: pointer; }
        .user-avatar { width: 38px; height: 38px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; text-transform: uppercase; }

        /* Tabs */
        .premium-tabs { display: flex; gap: 15px; margin-bottom: 40px; }
        .tab-btn {
            background: rgba(112, 144, 176, 0.08); color: var(--secondary);
            padding: 12px 25px; border-radius: 15px; font-weight: 700; font-size: 14px;
            text-decoration: none; transition: 0.3s; border: 2px solid transparent;
        }
        .tab-btn.active { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); }
        .tab-btn:hover:not(.active) { background: rgba(112, 144, 176, 0.15); color: var(--dark); }

        /* Appointment Card */
        .panel { background: var(--card-bg); border-radius: 25px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px; }
        
        .appt-card {
            background: #f8fafc; padding: 25px; border-radius: 25px; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #f1f4ff; transition: 0.3s;
        }
        .appt-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: var(--primary-light); }

        .student-profile { display: flex; align-items: center; gap: 20px; }
        .student-avatar {
            width: 60px; height: 60px; border-radius: 18px; object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .student-info h6 { margin: 0; font-weight: 800; color: var(--dark); font-size: 16px; }
        .student-info p { margin: 2px 0 0; font-size: 13px; color: var(--secondary); font-weight: 700; }

        .appt-time-info { text-align: right; margin-right: 30px; }
        .appt-date { font-weight: 800; color: var(--dark); font-size: 14px; display: block; }
        .appt-clock { font-size: 12px; color: var(--secondary); font-weight: 700; }

        .status-badge {
            padding: 8px 16px; border-radius: 30px; font-size: 11px; font-weight: 800;
            text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
        }
        .status-pending { background: #FFF4E6; color: #FFB81C; }
        .status-accepted { background: #E6FAF5; color: #05CD99; }
        .status-completed { background: #F4F7FE; color: #4318FF; }
        .status-cancelled { background: #FDEEEE; color: #EE5D50; }
        .status-rejected { background: #FDEEEE; color: #EE5D50; }

        .btn-action-sm {
            padding: 10px 20px; border-radius: 14px; font-weight: 800; font-size: 13px;
            text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .btn-action-primary { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(67, 24, 255, 0.2); }
        .btn-action-outline { border: 2px solid #f1f4ff; color: var(--dark); background: white; }
        .btn-action-primary:hover { transform: scale(1.05); box-shadow: 0 6px 15px rgba(67, 24, 255, 0.3); color: white; }
        .btn-action-outline:hover { background: #f8fafc; transform: scale(1.05); }
        .btn-action-danger { color: var(--danger); }
        .btn-action-danger:hover { background: #FDEEEE; }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="index.php" class="brand-logo">
        <i class="fas fa-graduation-cap"></i>
        <span class="brand-title">MentorHub</span>
    </a>

    <?php
    // Fetch pending count for badge
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mentor_id = ? AND status = 'pending'");
    $stmt->execute([$mentor_id]);
    $pending_count = $stmt->fetchColumn(); 
    ?>
    <div class="nav-menu">
        <a href="index.php" class="nav-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="posts.php" class="nav-item">
            <i class="fas fa-feather"></i> My Posts
        </a>
        <a href="withdraw.php" class="nav-item">
            <i class="fas fa-hand-holding-usd"></i> Withdrawals
        </a>
        <a href="schedule.php" class="nav-item active">
            <i class="far fa-calendar-alt"></i> Schedule
            <?php if ($pending_count > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 10px;"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="chat.php" class="nav-item">
            <i class="far fa-comments"></i> Messages
        </a>
        <a href="mentor_profile.php?id=<?= $mentor_id ?>" class="nav-item">
            <i class="far fa-user"></i> Public Profile
        </a>
        <a href="../student/index.php" class="nav-item mt-auto" style="color: var(--primary);">
            <i class="fas fa-exchange-alt"></i> Switch to Student View
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <h4>Weekly <span class="fw-800">Schedule</span></h4>
        <div class="top-icons">
            <i class="far fa-bell icon-btn"></i>
            <div class="user-avatar"><?= substr($mentor['name'], 0, 1) ?></div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert border-0 rounded-4 shadow-sm py-3 fw-800 mb-4 d-flex align-items-center" style="background: #E6FAF5; color: #05CD99;">
            <i class="fas fa-check-circle me-3"></i>
            <div>
                <?php
                if ($_GET['success'] == 'accepted') echo "Session request accepted successfully.";
                if ($_GET['success'] == 'rejected') echo "Session request has been rejected.";
                if ($_GET['success'] == 'completed') echo "Session marked as completed. Earnings added to your balance.";
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="premium-tabs">
        <a href="?tab=upcoming" class="tab-btn <?= $current_tab === 'upcoming' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="?tab=history" class="tab-btn <?= $current_tab === 'history' ? 'active' : '' ?>">Session History</a>
    </div>

    <div class="panel">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="far fa-calendar-times mb-3 opacity-20" style="font-size: 60px;"></i>
                <h5 class="fw-800 text-secondary">No sessions scheduled</h5>
                <p class="text-muted fw-600">You don't have any <?= $current_tab ?> sessions listed yet.</p>
            </div>
        <?php else: ?>
            <div class="schedule-grid">
                <?php foreach($appointments as $appt): ?>
                    <?php 
                        $photo = !empty($appt['student_photo']) ? '../../uploads/' . $appt['student_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($appt['student_name']).'&background=4318FF&color=fff';
                    ?>
                    <div class="appt-card">
                        <div class="student-profile">
                            <img src="<?= $photo ?>" class="student-avatar">
                            <div class="student-info">
                                <h6><?= htmlspecialchars($appt['student_name']) ?></h6>
                                <p><?= $appt['duration_minutes'] ?> Minutes Session</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <div class="appt-time-info">
                                <span class="appt-date"><?= date('M d, Y', strtotime($appt['scheduled_at'])) ?></span>
                                <span class="appt-clock"><?= date('h:i A', strtotime($appt['scheduled_at'])) ?></span>
                            </div>

                            <div class="status-box me-4">
                                <span class="status-badge status-<?= $appt['status'] ?>">
                                    <?php if($appt['status'] == 'pending'): ?>
                                        <i class="fas fa-hourglass-half"></i>
                                    <?php elseif($appt['status'] == 'accepted'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php endif; ?>
                                    <?= $appt['status'] ?>
                                </span>
                            </div>

                            <div class="appt-actions d-flex gap-2">
                                <?php if ($appt['status'] === 'pending'): ?>
                                    <a href="?action=accept&id=<?= $appt['id'] ?>" class="btn-action-sm btn-action-primary">Accept</a>
                                    <a href="?action=reject&id=<?= $appt['id'] ?>" class="btn-action-sm btn-action-outline btn-action-danger" onclick="return confirm('Reject this request?')">Decline</a>
                                <?php elseif ($appt['status'] === 'accepted'): ?>
                                    <a href="../video_room.php?id=<?= $appt['id'] ?>" class="btn-action-sm btn-action-outline">
                                        <i class="fas fa-video"></i> Start Call
                                    </a>
                                    <a href="?action=complete&id=<?= $appt['id'] ?>" class="btn-action-sm btn-action-primary" onclick="return confirm('Complete session and receive payout?')">
                                        <i class="fas fa-check-circle"></i> Done
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

