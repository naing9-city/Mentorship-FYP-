<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student's info for sidebar and admin
$stmt = $pdo->prepare("SELECT name, balance, mentor_status, role, created_by FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['name'];
$mentor_status = $user['mentor_status'];
$admin_id = $user['created_by'];
$unread_msg_count = 0; // Column missing in DB

if ($user['role'] === 'mentor' && $user['mentor_status'] === 'approved') {
    header("Location: ../mentor/index.php");
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expertise = $_POST['expertise'];
    $education = $_POST['education'];
    $bio = $_POST['bio'];
    
    // File upload handling
    $document_path = null;
    if (isset($_FILES['documents']) && $_FILES['documents']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/docs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
        $filename = 'doc_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['documents']['tmp_name'], $target_file)) {
            $document_path = 'docs/' . $filename; // Store path relative to 'uploads/'
        }
    }

    // Simple validation
    if (empty($expertise) || empty($education) || empty($bio)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // 1. Update users table
        $stmt = $pdo->prepare("UPDATE users SET mentor_status = 'pending', role = 'mentor', expertise = ?, education = ?, bio = ?, mentor_documents = ? WHERE id = ?");
        $stmt->execute([$expertise, $education, $bio, $document_path, $user_id]);
        
        // 2. Insert into mentor_applications for Admin Review
        $stmt = $pdo->prepare("INSERT INTO mentor_applications (user_id, admin_id, experience, certificate, status, applied_at, created_at) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())");
        $stmt->execute([$user_id, $admin_id, $expertise . " | " . $bio, $document_path]);

        $message = "Application submitted! Wait for admin approval.";
        $message_type = "success";
        
        // Refresh local user data
        $user['mentor_status'] = 'pending';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Mentor - MentorHub</title>
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

        /* Panel Premium */
        .panel { background: var(--card-bg); border-radius: 25px; padding: 40px; box-shadow: var(--shadow-md); max-width: 800px; margin: 0 auto; }
        
        .form-label { font-weight: 700; color: var(--dark); font-size: 14px; margin-bottom: 10px; }
        .form-control {
            border-radius: 15px; padding: 15px 20px; border: 2px solid #f1f4ff;
            background: #f8fafc; font-weight: 600; color: var(--dark); transition: 0.3s;
        }
        .form-control:focus {
            background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05);
        }

        .btn-premium {
            background: var(--primary); color: white; border: none; padding: 16px;
            border-radius: 16px; font-weight: 800; font-size: 15px; width: 100%;
            margin-top: 10px; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); transition: 0.3s;
        }
        .btn-premium:hover { transform: translateY(-2px); opacity: 0.95; }

        .alert-premium { border-radius: 15px; border: none; font-weight: 700; }

        .pending-state { text-align: center; padding: 40px 0; }
        .pending-icon { font-size: 60px; color: var(--warning); opacity: 0.5; margin-bottom: 25px; }

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
        <a href="find_mentor.php" class="nav-item">
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
            <a href="../mentor/index.php" class="nav-item" style="color: var(--primary);">
                <i class="fas fa-exchange-alt"></i> Switch to Mentor
            </a>
        <?php else: ?>
            <div class="section-label">Opportunities</div>
            <a href="apply_mentor.php" class="nav-item active">
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
            <input type="text" placeholder="Explore opportunities...">
        </div>
        <div class="top-icons">
            <i class="far fa-bell icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
            <i class="far fa-moon icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
            <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
        </div>
    </div>

    <div class="panel">
        <div class="mb-5">
            <h2 class="fw-800 text-dark">Join Our Mentor Network</h2>
            <p class="text-secondary fw-700">Empire your skills, share your knowledge, and earn points.</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-premium mb-4 shadow-sm"><?= $message ?></div>
        <?php endif; ?>

        <?php if($user['mentor_status'] === 'pending'): ?>
            <div class="pending-state">
                <i class="fas fa-hourglass-half pending-icon"></i>
                <h4 class="fw-800">Application Under Review</h4>
                <p class="text-secondary fw-600 mb-4">Our administrators are reviewing your profile. You'll be notified of the decision shortly.</p>
                <a href="index.php" class="btn btn-outline-primary rounded-pill px-5 fw-800">Return to Dashboard</a>
            </div>
        <?php else: ?>
            <?php if($user['mentor_status'] === 'rejected'): ?>
                <div class="alert alert-danger alert-premium mb-4">
                    <i class="fas fa-info-circle me-2"></i> Your previous application was not approved. You can resubmit with updated information.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Areas of Expertise</label>
                    <input type="text" name="expertise" class="form-control" placeholder="e.g., UI/UX Design, Data Science, Guitar" required>
                    <div class="form-text fw-600">Separate multiple skills with commas.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Education / Experience</label>
                    <input type="text" name="education" class="form-control" placeholder="Your degree or years of professional experience" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Supporting Documents</label>
                    <input type="file" name="documents" class="form-control">
                    <div class="form-text fw-600">Upload your CV or certificates (PDF, JPG, PNG).</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Tell us about yourself</label>
                    <textarea name="bio" class="form-control" rows="5" placeholder="Highlight your mentoring approach and what students can expect from your sessions..." required></textarea>
                </div>

                <button type="submit" class="btn-premium">Submit My Application</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

