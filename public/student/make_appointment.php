<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../../includes/db.php';
$student_id = $_SESSION['user_id'];

// Get student info
$stmt = $pdo->prepare("SELECT name, balance FROM users WHERE id=?");
$stmt->execute([$student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'];
$balance = $user['balance'];

// Stats for the top cards
// 1. Total (All)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id=?");
$stmt->execute([$student_id]);
$total_appt = $stmt->fetchColumn();

// 2. New (Pending)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id=? AND status='pending'");
$stmt->execute([$student_id]);
$new_appt = $stmt->fetchColumn();

// 3. Accepted (Upcoming)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id=? AND status='accepted'");
$stmt->execute([$student_id]);
$accepted_appt = $stmt->fetchColumn();

// 4. Cancelled/Rejected
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id=? AND status='rejected'");
$stmt->execute([$student_id]);
$cancelled_appt = $stmt->fetchColumn();

// Get mentor info
$mentor_id = $_GET['mentor_id'] ?? null;
$mentor = null;
if ($mentor_id) {
    $stmt = $pdo->prepare("SELECT id, name, education, expertise, profile_photo, hourly_rate, is_volunteer FROM users WHERE id=? AND role='mentor' AND mentor_status='approved'");
    $stmt->execute([$mentor_id]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
}

$message = '';
$appointment_success = false;

// Handle appointment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mentor) {
    $scheduled_at = $_POST['scheduled_at'];
    $duration_hours = floatval($_POST['duration_hours']);
    
    // Dynamic Pricing Logic
    $hourly_rate = $mentor['is_volunteer'] ? 0 : $mentor['hourly_rate'];
    $points_required = $hourly_rate * $duration_hours;

    if ($balance < $points_required) {
        $message = "Insufficient balance!";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id=? AND mentor_id=? AND status IN ('pending','accepted')");
        $stmt->execute([$student_id, $mentor_id]);
        if ($stmt->fetchColumn() > 0) {
            $message = "You already have a pending or accepted appointment with this mentor.";
        } else {
            $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$points_required, $student_id]);
            $stmt = $pdo->prepare("
                INSERT INTO appointments 
                (student_id, mentor_id, scheduled_at, points, duration_minutes, status, price, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([$student_id, $mentor_id, $scheduled_at, $points_required, $duration_hours*60, $points_required]);
            $appointment_success = true;
            $message = "Appointment request submitted successfully!";
        }
    }
    // Refresh balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id=?");
    $stmt->execute([$student_id]);
    $balance = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MentorHub</title>
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
            --warning: #FFB81C;
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

        /* Sidebar Styling (Consistent) */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .brand-logo {
            display: flex;
            align-items: center; gap: 12px; padding: 0 15px; margin-bottom: 50px; text-decoration: none;
        }
        .brand-logo i { font-size: 24px; color: var(--primary); }
        .brand-title { font-size: 24px; font-weight: 800; color: var(--dark); }

        .nav-menu { flex: 1; overflow-y: auto; overflow-x: hidden; }
        .section-label { font-size: 12px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; padding: 20px 15px 10px; }

        .nav-item {
            display: flex; align-items: center; gap: 15px; padding: 14px 20px;
            color: var(--secondary); text-decoration: none; font-weight: 600;
            border-radius: 12px; transition: 0.3s; margin-bottom: 5px; font-size: 15px;
        }
        .nav-item.active { background: var(--primary-light); color: var(--primary); }
        .nav-item:hover:not(.active) { background: #f8fafc; color: var(--dark); }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; }

        .sidebar-footer { margin-top: auto; border-top: 1px solid #f1f4ff; padding-top: 20px; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 30px 40px; flex: 1; }

        /* Top Bar */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .search-box-top {
            background: var(--card-bg); border-radius: 30px; padding: 10px 25px;
            display: flex; align-items: center; gap: 12px; width: 400px; box-shadow: var(--shadow-md);
        }
        .search-box-top input { border: none; background: none; outline: none; width: 100%; font-weight: 500; font-size: 14px; color: var(--secondary); }

        .top-icons {
            display: flex; align-items: center; gap: 20px; background: var(--card-bg);
            padding: 8px 15px; border-radius: 30px; box-shadow: var(--shadow-md);
        }
        .user-avatar { width: 38px; height: 38px; background: var(--dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; text-transform: uppercase; }

        .btn-back {
            display: flex; align-items: center; justify-content: center;
            width: 45px; height: 45px; border-radius: 12px; background: var(--card-bg);
            color: var(--primary); font-size: 20px; box-shadow: var(--shadow-md);
            text-decoration: none; transition: 0.3s; margin-bottom: 25px;
        }
        .btn-back:hover { transform: translateX(-5px); color: var(--primary); }

        /* Stats Dashboard Styling */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card {
            background: var(--card-bg); padding: 20px; border-radius: 20px;
            display: flex; align-items: center; gap: 18px; box-shadow: var(--shadow-md);
        }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-size: 20px;
        }
        .stat-icon.blue { background: #F4F7FE; color: #4318FF; }
        .stat-icon.purple { background: #F4F7FE; color: #7033FF; }
        .stat-icon.green { background: #E6FAF5; color: #05CD99; }
        .stat-icon.orange { background: #FFF4E6; color: #FFB81C; }
        
        .stat-details h4 { font-size: 24px; font-weight: 800; margin: 0; color: var(--dark); }
        .stat-details p { font-size: 14px; color: var(--secondary); margin: 0; font-weight: 700; }

        .dashboard-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 30px; }

        .panel { background: var(--card-bg); border-radius: 25px; padding: 30px; box-shadow: var(--shadow-md); }
        .panel-title { font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }

        .mini-profile { display: flex; align-items: center; gap: 20px; margin-bottom: 35px; background: var(--primary-light); padding: 20px; border-radius: 20px; }
        .mini-avatar { width: 60px; height: 60px; border-radius: 15px; object-fit: cover; box-shadow: var(--shadow-md); }
        .mini-info h5 { font-size: 18px; font-weight: 800; margin: 0; color: var(--dark); }
        .mini-info p { font-size: 14px; color: var(--secondary); margin: 0; font-weight: 700; }

        .form-label { font-weight: 700; color: var(--dark); font-size: 14px; margin-bottom: 10px; }
        .form-control {
            border-radius: 12px; padding: 12px 18px; border: 2px solid #f1f4ff;
            background: #f8fafc; font-weight: 700; color: var(--dark); transition: 0.3s;
        }
        .form-control:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05); }

        .pricing-info { background: #F4F7FE; border-radius: 20px; padding: 20px; margin: 30px 0; border: 1px dashed var(--primary); }
        .pricing-info p { margin: 0; font-size: 14px; color: var(--primary); font-weight: 700; }

        .btn-premium {
            background: var(--primary); color: white; border: none; padding: 16px;
            border-radius: 16px; font-weight: 800; font-size: 15px; width: 100%;
            box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); transition: 0.3s;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(67, 24, 255, 0.3); color: white; }

        .calendar-widget { text-align: center; }
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .cal-month { font-size: 16px; font-weight: 800; color: var(--dark); }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; }
        .cal-day-name { font-size: 11px; font-weight: 800; color: var(--secondary); text-transform: uppercase; margin-bottom: 15px; }
        .cal-day { font-size: 13px; font-weight: 700; color: var(--dark); width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 10px; transition: 0.2s; cursor: pointer; }
        .cal-day:hover { background: #f1f4ff; color: var(--primary); }
        .cal-day.active { background: var(--primary); color: white; box-shadow: 0 5px 15px rgba(67, 24, 255, 0.3); }
        .cal-day.muted { color: #cbd5e0; }

    </style>
</head>
<body>

<!-- Sidebar (Consistent) -->
<div class="sidebar">
    <a href="index.php" class="brand-logo">
        <i class="fas fa-graduation-cap"></i>
        <div>
            <div class="brand-title">MentorHub</div>
            <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Student Portal</div>
        </div>
    </a>

    <div class="nav-menu">
        <a href="index.php" class="nav-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="find_mentor.php" class="nav-item active">
            <i class="fas fa-users"></i> Mentors
        </a>
        <a href="my_appointments.php" class="nav-item">
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

        <?php 
        // Note: fetch mentor_status for sidebar logic if not already available
        $stmt_s = $pdo->prepare("SELECT mentor_status FROM users WHERE id = ?");
        $stmt_s->execute([$_SESSION['user_id']]);
        $m_status = $stmt_s->fetchColumn();
        ?>

        <?php if ($m_status === 'approved'): ?>
            <div class="section-label">Mentor Mode</div>
            <a href="../mentor/index.php" class="nav-item" style="color: var(--primary);">
                <i class="fas fa-exchange-alt"></i> Switch to Mentor
            </a>
        <?php else: ?>
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
        <div class="search-box-top">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Explore mentors & sessions...">
        </div>
        <div class="top-icons">
            <i class="far fa-bell icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
            <i class="far fa-moon icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
            <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
        </div>
    </div>

    <a href="find_mentor.php" class="btn-back">
        <i class="fas fa-chevron-left"></i>
    </a>

    <!-- Stats Dashboard Row -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-details">
                <h4><?= $total_appt ?></h4>
                <p>Total Bookings</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
            <div class="stat-details">
                <h4><?= $new_appt ?></h4>
                <p>Pending Requests</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check"></i></div>
            <div class="stat-details">
                <h4><?= $accepted_appt ?></h4>
                <p>Accepted Today</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-wallet"></i></div>
            <div class="stat-details">
                <h4><?= number_format($balance, 1) ?></h4>
                <p>Available Points</p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left Panel: Booking Form -->
        <div class="panel">
            <div class="panel-title">
                Book Your Session
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-800" style="background: var(--primary) !important; font-size: 11px;">
                    <?= number_format($balance, 1) ?> Points Available
                </span>
            </div>

            <?php if ($mentor): ?>
                <div class="mini-profile">
                    <img src="<?= !empty($mentor['profile_photo']) ? '../../'.$mentor['profile_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($mentor['name']).'&background=random' ?>" class="mini-avatar">
                    <div class="mini-info">
                        <h5><?= htmlspecialchars($mentor['name']) ?></h5>
                        <p><?= htmlspecialchars($mentor['education'] ?? 'Certified Mentor') ?></p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?= $appointment_success ? 'alert-success' : 'alert-danger' ?> alert-premium mb-4 shadow-sm" style="border-radius: 12px; font-weight: 700;">
                        <i class="fas <?= $appointment_success ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <?php if (!$appointment_success): ?>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">When would you like to start?</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Session Duration (Hours)</label>
                                <input type="number" name="duration_hours" class="form-control" min="0.5" step="0.5" placeholder="e.g. 1.5" required>
                            </div>
                        </div>

                        <div class="pricing-info">
                            <p>
                                <i class="fas fa-info-circle me-2"></i>
                                <?php if ($mentor['is_volunteer']): ?>
                                    This mentor is in <strong>Volunteer Mode</strong>. The session is <span class="badge bg-success">FREE</span>.
                                <?php else: ?>
                                    <strong><?= number_format($mentor['hourly_rate'], 1) ?> points per hour</strong> will be deducted from your wallet.
                                <?php endif; ?>
                            </p>
                        </div>

                        <button class="btn-premium">
                            <i class="fas fa-calendar-plus me-2"></i> Confirm Booking Request
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <a href="my_appointments.php" class="btn-premium text-decoration-none d-inline-block" style="width: auto; padding: 16px 40px;">
                             View My Appointments
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-warning alert-premium border-0">
                    <i class="fas fa-user-slash me-2"></i> Mentor not found or selection invalid.
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Panel: Calendar & Info -->
        <div class="panel">
            <div class="panel-title" style="font-size: 16px;">Schedule Helper</div>
            
            <div class="calendar-widget">
                <div class="cal-header">
                    <i class="fas fa-chevron-left text-muted" style="cursor: pointer;"></i>
                    <span class="cal-month">December 2025</span>
                    <i class="fas fa-chevron-right text-muted" style="cursor: pointer;"></i>
                </div>
                <div class="cal-grid">
                    <div class="cal-day-name">Sun</div>
                    <div class="cal-day-name">Mon</div>
                    <div class="cal-day-name">Tue</div>
                    <div class="cal-day-name">Wed</div>
                    <div class="cal-day-name">Thu</div>
                    <div class="cal-day-name">Fri</div>
                    <div class="cal-day-name">Sat</div>
                    
                    <div class="cal-day muted">28</div>
                    <div class="cal-day muted">29</div>
                    <div class="cal-day">1</div>
                    <div class="cal-day">2</div>
                    <div class="cal-day">3</div>
                    <div class="cal-day">4</div>
                    <div class="cal-day">5</div>
                    <div class="cal-day">6</div>
                    <div class="cal-day">7</div>
                    <div class="cal-day">8</div>
                    <div class="cal-day">9</div>
                    <div class="cal-day">10</div>
                    <div class="cal-day">11</div>
                    <div class="cal-day">12</div>
                    <div class="cal-day">13</div>
                    <div class="cal-day">14</div>
                    <div class="cal-day">15</div>
                    <div class="cal-day">16</div>
                    <div class="cal-day">17</div>
                    <div class="cal-day">18</div>
                    <div class="cal-day">19</div>
                    <div class="cal-day">20</div>
                    <div class="cal-day">21</div>
                    <div class="cal-day">22</div>
                    <div class="cal-day">23</div>
                    <div class="cal-day">24</div>
                    <div class="cal-day">25</div>
                    <div class="cal-day">26</div>
                    <div class="cal-day active">27</div>
                    <div class="cal-day">28</div>
                    <div class="cal-day">29</div>
                    <div class="cal-day">30</div>
                    <div class="cal-day">31</div>
                </div>
            </div>

            <hr style="margin: 30px 0; border-color: #f1f4ff;">

            <div class="tip-box">
                <p style="font-size: 13px; color: var(--secondary); font-weight: 700; line-height: 1.6;">
                    <i class="fas fa-lightbulb text-warning me-2"></i>
                    Tip: Discuss specific learning goals with your mentor via chat before the session.
                </p>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary);"></div>
                        <span style="font-size: 10px; font-weight: 800; color: var(--secondary);">Booked</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                        <span style="font-size: 10px; font-weight: 800; color: var(--secondary);">Open</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

