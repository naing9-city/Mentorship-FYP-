<?php
session_start();
require_once '../../includes/db.php';

// Allow viewing by ID (if passed) or fallback to session user (if mentor viewing themselves)
$mentor_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if (!$mentor_id) {
    die("Mentor ID not specified.");
}

// Mentor info
$stmt = $pdo->prepare("SELECT name, email, education, expertise, bio, profile_photo, hourly_rate, is_volunteer FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mentor) {
    die("Mentor not found.");
}

// Stats (Calculated from appointments and ratings)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mentor_id = ? AND status = 'completed'");
$stmt->execute([$mentor_id]);
$total_sessions = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(duration_minutes) FROM appointments WHERE mentor_id = ? AND status = 'completed'");
$stmt->execute([$mentor_id]);
$total_minutes = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT AVG(rating) FROM ratings WHERE mentor_id = ?");
$stmt->execute([$mentor_id]);
$avg_rating = $stmt->fetchColumn();
$rating_display = $avg_rating ? number_format($avg_rating, 1) : 'N/A';

// Handle Profile Photo / Video URL
$is_video_avatar = false;
$initials = strtoupper(substr($mentor['name'] ?? 'M', 0, 1));
$raw_photo = $mentor['profile_photo'] ?? '';
$photo_disk = !empty($raw_photo) ? __DIR__ . '/../uploads/' . $raw_photo : '';
$photo_url = (!empty($raw_photo) && file_exists($photo_disk))
    ? '../uploads/' . htmlspecialchars($raw_photo)
    : null;
if ($photo_url) {
    $ext = strtolower(pathinfo($raw_photo, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
        $is_video_avatar = true;
    }
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Profile Update (owner only)
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $mentor_id) {
        if ($_POST['action'] === 'update_profile') {
            $name = trim($_POST['name']);
            $education = trim($_POST['education']);
            $expertise = trim($_POST['expertise']);
            $bio = trim($_POST['bio']);
            $hourly_rate = floatval($_POST['hourly_rate']);
            $is_volunteer = isset($_POST['is_volunteer']) ? 1 : 0;
            $remove_photo = isset($_POST['remove_photo']) ? 1 : 0;
            $profile_photo = $mentor['profile_photo'];

            if ($remove_photo) {
                $profile_photo = null;
            }

            if (!empty($_FILES['profile_photo']['name'])) {
                $filename = "profile_" . time() . "_" . uniqid() . "_" . basename($_FILES['profile_photo']['name']);
                $target = "../uploads/" . $filename;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
                    $profile_photo = $filename;
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET name = ?, education = ?, expertise = ?, bio = ?, hourly_rate = ?, is_volunteer = ?, profile_photo = ? WHERE id = ?");
            $stmt->execute([$name, $education, $expertise, $bio, $hourly_rate, $is_volunteer, $profile_photo, $mentor_id]);
            
            header("Location: mentor_profile.php?id=" . $mentor_id . "&success=updated");
            exit;
        }
        elseif ($_POST['action'] === 'create_post') {
            $content = trim($_POST['content']);
            $image_path = null;

            if (!empty($_FILES['image']['name'])) {
                $filename = time() . "_" . uniqid() . "_" . basename($_FILES['image']['name']);
                $target = "../uploads/" . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $image_path = $filename;
                }
            }

            if (!empty($content) || $image_path) {
                $stmt = $pdo->prepare("INSERT INTO mentor_posts (mentor_id, content, image_path) VALUES (?, ?, ?)");
                $stmt->execute([$mentor_id, $content, $image_path]);
            }
            header("Location: mentor_profile.php?id=" . $mentor_id . "&success=posted");
            exit;
        }
    }
    
    // Follow/Unfollow (any logged in user except self)
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $mentor_id) {
        $follower_id = $_SESSION['user_id'];
        
        if ($_POST['action'] === 'follow') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$follower_id, $mentor_id]);
            header("Location: mentor_profile.php?id=" . $mentor_id);
            exit;
        }
        elseif ($_POST['action'] === 'unfollow') {
            $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$follower_id, $mentor_id]);
            header("Location: mentor_profile.php?id=" . $mentor_id);
            exit;
        }
    }
}

// Fetch Follow Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$mentor_id]);
$follower_count = $stmt->fetchColumn();

// Check if current user is following
$is_following = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $mentor_id]);
    $is_following = $stmt->fetchColumn() > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($mentor['name']) ?> - Profile - MentorHub</title>
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

        /* Profile Header Card */
        .profile-card {
            background: var(--card-bg); border-radius: 30px; overflow: hidden;
            box-shadow: var(--shadow-md); margin-bottom: 30px; position: relative;
        }
        .profile-banner {
            height: 160px; background: linear-gradient(135deg, #4318FF 0%, #B4A0FF 100%);
            position: relative;
        }
        .profile-avatar-initials {
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #4318FF, #7033FF);
            color: white; font-weight: 800; font-size: 64px;
            width: 170px; height: 170px; border-radius: 40px;
            border: 8px solid white; margin-top: -85px;
            position: relative; z-index: 2;
            box-shadow: 0 20px 40px rgba(67,24,255,0.25);
        }
        .profile-info-section { padding: 0 40px 40px; margin-top: -60px; position: relative; text-align: center; }
        .profile-avatar-large {
            width: 130px; height: 130px; border-radius: 40px; object-fit: cover;
            border: 5px solid white; box-shadow: var(--shadow-md); background: white;
            margin: 0 auto 20px;
        }
        .profile-name { font-size: 32px; font-weight: 800; color: var(--dark); margin-bottom: 5px; }
        .profile-education { font-size: 16px; font-weight: 600; color: var(--secondary); margin-bottom: 25px; }

        /* Stats Grid */
        .stats-grid { display: flex; justify-content: center; gap: 30px; margin-bottom: 30px; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 24px; font-weight: 800; color: var(--dark); display: block; }
        .stat-label { font-size: 13px; font-weight: 700; color: var(--secondary); text-transform: uppercase; }

        /* Action Buttons */
        .profile-actions { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
        .btn-premium {
            padding: 12px 35px; border-radius: 18px; font-weight: 800; font-size: 15px;
            transition: 0.3s; display: flex; align-items: center; gap: 10px; border: none;
        }
        .btn-primary-p { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); }
        .btn-primary-p:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 24, 255, 0.3); color: white; }
        .btn-outline-p { background: var(--primary-light); color: var(--primary); }
        .btn-outline-p:hover { background: #e0e8ff; transform: translateY(-3px); }
        .btn-danger-p { background: #FDEEEE; color: var(--danger); }

        /* Content Sections */
        .content-panel { background: var(--card-bg); border-radius: 25px; padding: 35px; box-shadow: var(--shadow-md); margin-bottom: 30px; }
        .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid #F4F7FE; padding-bottom: 15px; }
        .section-header i { color: var(--primary); font-size: 20px; }
        .section-header h5 { margin: 0; font-weight: 800; color: var(--dark); }
        .section-text { font-size: 15px; line-height: 1.8; color: var(--secondary); font-weight: 500; }

        .price-badge-header {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            text-transform: uppercase;
        }
        .price-pts { font-size: 20px; color: var(--dark); font-weight: 800; }

        /* Modal Styling */
        .modal-content { border-radius: 30px; border: none; box-shadow: var(--shadow-md); }
        .modal-header { border-bottom: 1px solid #F4F7FE; padding: 30px 40px; }
        .modal-title { font-weight: 800; color: var(--dark); }
        .modal-body { padding: 30px 40px; }
        .form-label { font-weight: 700; color: var(--dark); margin-bottom: 10px; font-size: 14px; }
        .form-control {
            border-radius: 15px; border: 2px solid #F4F7FE; padding: 12px 20px;
            font-weight: 600; color: var(--dark); transition: 0.3s;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: none; background: white; }
        .modal-footer { border-top: 1px solid #F4F7FE; padding: 20px 40px 30px; }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="index.php" class="brand-logo">
        <i class="fas fa-graduation-cap"></i>
        <span class="brand-title">MentorHub</span>
    </a>

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
        <a href="schedule.php" class="nav-item">
            <i class="far fa-calendar-alt"></i> Schedule
        </a>
        <a href="chat.php" class="nav-item">
            <i class="far fa-comments"></i> Messages
        </a>
        <a href="mentor_profile.php?id=<?= $mentor_id ?>" class="nav-item active">
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
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'updated'): ?>
                <div class="alert border-0 rounded-4 shadow-sm py-3 fw-800 mb-4 d-flex align-items-center" style="background: #E6FAF5; color: #05CD99;">
                    <i class="fas fa-check-circle me-3"></i>
                    <div>Profile information updated successfully!</div>
                </div>
            <?php endif; ?>

            <!-- Profile Header Card -->
            <div class="profile-card">
                <div class="profile-banner">
                    <div class="position-absolute top-0 start-0 p-3">
                        <?php 
                            $back_link = (isset($_GET['return_to']) && $_GET['return_to'] === 'student') ? '../student/index.php' : 'index.php';
                            $back_label = (isset($_GET['return_to']) && $_GET['return_to'] === 'student') ? 'Go Back' : 'Dashboard';
                        ?>
                        <a href="<?= $back_link ?>" class="btn btn-white btn-sm fw-800 rounded-pill px-3 shadow-sm" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(5px);">
                            <i class="fas fa-arrow-left me-2"></i> <?= $back_label ?>
                        </a>
                    </div>
                    <!-- Pricing Badge on Banner -->
                    <div class="price-badge-header">
                        <?php if ($mentor['is_volunteer']): ?>
                            VOLUNTEER / FREE
                        <?php else: ?>
                            <?= number_format($mentor['hourly_rate'], 0) ?> PTS / HR
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-info-section">
                    <?php if ($is_video_avatar): ?>
                        <video src="<?= $photo_url ?>" class="profile-avatar-large shadow-lg" autoplay loop muted playsinline></video>
                    <?php else: ?>
                        <img src="<?= $photo_url ?>" class="profile-avatar-large shadow-lg">
                    <?php endif; ?>
                    <h2 class="profile-name"><?= htmlspecialchars($mentor['name']) ?></h2>
                    <p class="profile-education"><?= htmlspecialchars($mentor['education'] ?? 'MentorHub Mentor') ?></p>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value"><?= $total_sessions ?></span>
                            <span class="stat-label">Sessions</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($total_minutes / 60, 1) ?>h</span>
                            <span class="stat-label">Tutoring</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $rating_display ?> <i class="fas fa-star text-warning small"></i></span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $follower_count ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $mentor_id): ?>
                            <form method="POST" class="d-inline">
                                <?php if($is_following): ?>
                                    <input type="hidden" name="action" value="unfollow">
                                    <button class="btn-premium btn-danger-p"><i class="fas fa-user-minus"></i> Unfollow</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="follow">
                                    <button class="btn-premium btn-primary-p"><i class="fas fa-user-plus"></i> Follow Mentor</button>
                                <?php endif; ?>
                            </form>
                            <a href="../student/chat.php?mentor_id=<?= $mentor_id ?>&return_to=mentor" class="btn-premium btn-outline-p">
                                <i class="far fa-comments"></i> Send Message
                            </a>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $mentor_id): ?>
                            <button class="btn-premium btn-primary-p" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit"></i> Edit Public Profile
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Details -->
            <div class="row">
                <div class="col-md-7">
                    <div class="content-panel">
                        <div class="section-header">
                            <i class="fas fa-award"></i>
                            <h5>Area of Expertise</h5>
                        </div>
                        <p class="section-text">
                            <?= !empty($mentor['expertise']) ? nl2br(htmlspecialchars($mentor['expertise'])) : 'This mentor hasn\'t listed their specific skills yet.' ?>
                        </p>
                    </div>

                    <div class="content-panel">
                        <div class="section-header">
                            <i class="fas fa-id-card"></i>
                            <h5>Biography & Experience</h5>
                        </div>
                        <p class="section-text">
                            <?= !empty($mentor['bio']) ? nl2br(htmlspecialchars($mentor['bio'])) : 'No biography provided.' ?>
                        </p>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="content-panel">
                        <div class="section-header">
                            <i class="fas fa-tag"></i>
                            <h5>Session Pricing</h5>
                        </div>
                        <div class="mt-3">
                            <?php if ($mentor['is_volunteer']): ?>
                                <div class="price-badge-premium" style="background: rgba(5, 205, 153, 0.1); color: #05CD99; border: 1px solid rgba(5, 205, 153, 0.2); padding: 25px; border-radius: 20px; text-align: center;">
                                    <i class="fas fa-heart fa-2x mb-3 d-block"></i>
                                    <span class="fs-5 fw-800">FREE / VOLUNTEER</span>
                                    <p class="text-muted small mt-2 fw-600 mb-0">Contributing as a community mentor</p>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-between p-4 rounded-4" style="background: var(--primary-light); border: 2px solid #E0E8FF;">
                                    <div>
                                        <div class="text-secondary fw-800 small text-uppercase mb-1" style="letter-spacing: 1px;">Standard Rate</div>
                                        <div class="text-dark fw-400 small">Per hour session</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="price-pts text-primary" style="font-size: 32px; letter-spacing: -1px;"><?= number_format($mentor['hourly_rate'], 0) ?> <span class="fs-6" style="color: var(--secondary); letter-spacing: 0;">Pts</span></div>
                                    </div>
                                </div>
                                <div class="alert alert-light border mt-3 rounded-3 py-2 px-3">
                                    <p class="text-muted small m-0 fw-600"><i class="fas fa-info-circle me-1 text-primary"></i> Secure payment processed via wallet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-panel">
                        <div class="section-header">
                            <i class="fas fa-user-check"></i>
                            <h5>Verification</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3 bg-light rounded-4 p-3 border-start border-4 border-primary">
                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-800">Verified Mentor</h6>
                                <p class="text-muted small mb-0 fw-600">Identity and credentials verified</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $mentor_id): ?>
                <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <form class="modal-content" method="POST" enctype="multipart/form-data">
                      <div class="modal-header">
                        <h4 class="modal-title">Update Your Profile</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-4">
                            <div class="col-md-12 text-center mb-2">
                                <div class="position-relative d-inline-block">
                                    <?php if ($is_video_avatar): ?>
                                        <video src="<?= $photo_url ?>" style="width: 120px; height: 120px; border-radius: 30px; object-fit: cover;" class="mb-2 shadow-sm" autoplay loop muted playsinline></video>
                                    <?php else: ?>
                                        <img src="<?= $photo_url ?>" style="width: 120px; height: 120px; border-radius: 30px; object-fit: cover;" class="mb-2 shadow-sm">
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <label class="btn btn-premium-sm btn-p-light btn-sm px-4">
                                        <i class="fas fa-camera me-2"></i> Change Photo
                                        <input type="file" name="profile_photo" hidden accept="image/*,video/*">
                                    </label>
                                    <?php if (!empty($mentor['profile_photo'])): ?>
                                        <div class="form-check mt-2 justify-content-center d-flex">
                                            <input class="form-check-input" type="checkbox" name="remove_photo" id="removePhoto">
                                            <label class="form-check-label ms-2 text-danger fw-600" for="removePhoto" style="font-size: 13px;">Remove photo</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Display Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($mentor['name']) ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Professional Headline / Education</label>
                                <input type="text" name="education" class="form-control" value="<?= htmlspecialchars($mentor['education'] ?? '') ?>" placeholder="e.g. Senior Software Engineer / PhD in Economics">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Expertise (Comma separated or keywords)</label>
                                <textarea name="expertise" class="form-control" rows="3"><?= htmlspecialchars($mentor['expertise'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Bio & Detailed Experience</label>
                                <textarea name="bio" class="form-control" rows="5"><?= htmlspecialchars($mentor['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Standard Rate (Points / Hour)</label>
                                <div class="input-group">
                                    <input type="number" name="hourly_rate" class="form-control" value="<?= $mentor['hourly_rate'] ?>" step="0.5" min="0">
                                    <span class="input-group-text bg-white border-start-0" style="border-radius: 0 15px 15px 0; border: 2px solid #F4F7FE; border-left: none;">Pts</span>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch bg-light w-100 p-3 rounded-4 d-flex align-items-center gap-3">
                                    <input class="form-check-input ms-0" type="checkbox" name="is_volunteer" id="isVolunteer" style="width: 45px; height: 22px; cursor: pointer;" <?= $mentor['is_volunteer'] ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-800 text-dark pt-1" for="isVolunteer">Offer sessions for free</label>
                                </div>
                            </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn-premium btn-outline-p" data-bs-dismiss="modal">Discard Changes</button>
                        <button type="submit" class="btn-premium btn-primary-p">Submit Updates</button>
                      </div>
                    </form>
                  </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS for auto-opening modal on edit request -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("edit") === "profile") {
            const modalEl = document.getElementById("editProfileModal");
            if (modalEl) {
                const editModal = new bootstrap.Modal(modalEl);
                editModal.show();
            }
        }
    });
</script>
</body>
</html>


