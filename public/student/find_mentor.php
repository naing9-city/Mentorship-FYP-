<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student's info
$stmt = $pdo->prepare("SELECT created_by, mentor_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_id = $user_info['created_by'];
$mentor_status = $user_info['mentor_status'];

// Get Search Term
$search = $_GET['search'] ?? '';

// Fetch Mentors with Stats (Restricted to same admin)
$query = "
    SELECT u.id, u.name, u.expertise, u.education, u.profile_photo, u.hourly_rate, u.is_volunteer,
           (SELECT COUNT(*) FROM appointments a WHERE a.mentor_id = u.id AND a.status = 'completed') as session_count,
           (SELECT COALESCE(SUM(a.duration_minutes), 0) FROM appointments a WHERE a.mentor_id = u.id AND a.status = 'completed') as total_minutes,
           (SELECT COALESCE(AVG(r.rating), 0) FROM ratings r JOIN appointments a ON r.appointment_id = a.id WHERE a.mentor_id = u.id) as avg_rating
    FROM users u 
    WHERE u.role = 'mentor' AND u.mentor_status = 'approved' AND u.created_by = ?
";

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.expertise LIKE ? OR u.education LIKE ?)";
}

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->execute([$admin_id, $search_param, $search_param, $search_param]);
} else {
    $stmt->execute([$admin_id]);
}
$mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentors - MentorHub</title>
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

        /* Mentor Cards Styling */
        .mentor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        .mentor-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .mentor-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 60px rgba(112, 144, 176, 0.15);
        }

        .mentor-cover {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .mentor-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 12px;
            color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .mentor-content {
            padding: 25px;
            flex: 1;
        }

        .mentor-name {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .mentor-sub {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .mentor-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            background: var(--light-bg);
            padding: 18px;
            border-radius: 18px;
        }

        .m-stat {
            flex: 1;
            text-align: center;
        }

        .m-stat .val {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .m-stat .lbl {
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mentor-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(226, 232, 240, 0.5);
            display: flex;
            gap: 12px;
        }

        .btn-premium-sm {
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            flex: 1;
        }

        .btn-p-light {
            background: var(--light-bg);
            color: var(--primary-blue);
        }

        .btn-p-primary {
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.2);
        }

        .btn-p-icon {
            background: var(--light-bg);
            color: var(--text-muted);
            flex: 0 0 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-premium-sm:hover {
            transform: translateY(-2px);
            opacity: 0.95;
            color: inherit;
        }

        .btn-p-primary:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(67, 24, 255, 0.3);
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
            <form action="" method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by name, expertise..."
                    value="<?= htmlspecialchars($search) ?>">
            </form>
            <div class="top-icons">
                <i class="far fa-bell icon-btn"></i>
                <i class="far fa-moon icon-btn"></i>
                <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
            </div>
        </div>

        <div class="page-header mb-5">
            <h1 class="fw-800" style="letter-spacing: -1px;">Find Your Mentor</h1>
            <p class="text-muted fw-600">Connect with experts to grow your skills.</p>
        </div>

        <div class="mentor-grid">
            <?php foreach ($mentors as $m): ?>
                <?php
                $photo = !empty($m['profile_photo']) ? '../../uploads/' . $m['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['name']) . '&background=random';
                ?>
                <div class="mentor-card">
                    <div class="mentor-cover" style="background-image: url('<?= $photo ?>')">
                        <div class="mentor-badge">
                            <?php if ($m['is_volunteer']): ?>
                                FREE
                            <?php else: ?>
                                <?= number_format($m['hourly_rate'], 0) ?> PTS/HR
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mentor-content">
                        <h5 class="mentor-name"><?= htmlspecialchars($m['name']) ?></h5>
                        <p class="mentor-sub"><?= htmlspecialchars($m['expertise'] ?? 'Expert Mentor') ?></p>

                        <div class="mentor-stats">
                            <div class="m-stat">
                                <span class="val"><?= number_format($m['avg_rating'], 1) ?> <i
                                        class="fas fa-star text-warning small"></i></span>
                                <span class="lbl">Rating</span>
                            </div>
                            <div class="m-stat">
                                <span class="val"><?= number_format($m['session_count']) ?></span>
                                <span class="lbl">Sessions</span>
                            </div>
                            <div class="m-stat">
                                <span class="val"><?= number_format($m['total_minutes']) ?></span>
                                <span class="lbl">Minutes</span>
                            </div>
                        </div>
                    </div>
                    <div class="mentor-footer">
                        <a href="../mentor/mentor_profile.php?id=<?= $m['id'] ?>&return_to=student"
                            class="btn-premium-sm btn-p-light">Profile</a>
                        <a href="make_appointment.php?mentor_id=<?= $m['id'] ?>" class="btn-premium-sm btn-p-primary">Book
                            Now</a>
                        <a href="chat.php?mentor_id=<?= $m['id'] ?>" class="btn-premium-sm btn-p-icon"><i
                                class="far fa-comment-dots"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($mentors)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x mb-3 text-muted opacity-25"></i>
                    <h5 class="text-muted fw-800">No mentors found matching your search.</h5>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>