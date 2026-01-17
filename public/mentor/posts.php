<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../index.php");
    exit;
}

$mentor_id = $_SESSION['user_id'];

// Mentor info for sidebar/header
$stmt = $pdo->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Post Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $content = trim($_POST['content']);
    $image_path = null;

    if (!empty($_FILES['image']['name'])) {
        $filename = time() . "_" . uniqid() . "_" . basename($_FILES['image']['name']);
        $upload_dir = "../../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $filename;
        }
    }

    if (!empty($content) || $image_path) {
        $stmt = $pdo->prepare("INSERT INTO mentor_posts (mentor_id, content, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$mentor_id, $content, $image_path]);
    }
    header("Location: posts.php?success=posted");
    exit;
}

// Fetch Mentor's Posts
$query = "
    SELECT mp.*, 
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = mp.id) as likes_count,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = mp.id AND pl.user_id = ?) as liked_by_me
    FROM mentor_posts mp 
    WHERE mp.mentor_id = ? 
    ORDER BY mp.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$mentor_id, $mentor_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$photo_url = !empty($mentor['profile_photo']) 
    ? htmlspecialchars($mentor['profile_photo']) 
    : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($mentor['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts - MentorHub</title>
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

        /* Feed Styling */
        .feed-wrapper { max-width: 1000px; display: grid; grid-template-columns: 1fr 320px; gap: 30px; margin: 0 auto; }
        
        .panel { background: var(--card-bg); border-radius: 25px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px; }
        .panel-title { font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 20px; }

        /* Create Post Panel */
        .post-input {
            border: 2px solid #F4F7FE; border-radius: 20px; padding: 20px;
            font-weight: 500; font-size: 15px; color: var(--dark); resize: none; width: 100%; transition: 0.3s;
        }
        .post-input:focus { outline: none; border-color: var(--primary); background: white; }

        .file-upload-btn {
            background: var(--primary-light); color: var(--primary); padding: 12px 20px;
            border-radius: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;
        }
        .btn-post {
            background: var(--primary); color: white; border: none; padding: 12px 30px;
            border-radius: 15px; font-weight: 800; font-size: 15px; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); transition: 0.3s;
        }
        .btn-post:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(67, 24, 255, 0.3); }

        /* Post Card Style */
        .post-card { background: var(--card-bg); border-radius: 25px; margin-bottom: 30px; overflow: hidden; box-shadow: var(--shadow-md); }
        .post-header { padding: 25px; display: flex; align-items: center; gap: 15px; }
        .poster-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; }
        .post-info h6 { margin: 0; font-weight: 800; color: var(--dark); font-size: 16px; }
        .post-time { font-size: 12px; font-weight: 700; color: var(--secondary); }
        
        .post-body { padding: 0 25px 25px; }
        .post-text { font-size: 15px; line-height: 1.6; color: #4A5568; margin-bottom: 20px; }
        .post-media { width: 100%; border-radius: 20px; object-fit: cover; border: 1px solid #f1f4ff; }

        .post-footer { padding: 15px 25px; background: #fafbfc; border-top: 1px solid #f1f4ff; display: flex; justify-content: space-between; align-items: center; }
        .post-stats { display: flex; gap: 20px; }
        .stat-item { display: flex; align-items: center; gap: 8px; color: var(--secondary); font-weight: 800; font-size: 14px; }
        .stat-item i { font-size: 16px; }
        .stat-item.liked i { color: #EE5D50; }

        /* Tip/Stat Sidebar */
        .side-panel { background: linear-gradient(135deg, var(--primary), #7033FF); border-radius: 25px; padding: 30px; color: white; }
        .side-panel h5 { font-weight: 800; margin-bottom: 20px; }
        .tip-card { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; margin-bottom: 15px; font-size: 13px; font-weight: 600; }

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
        <a href="posts.php" class="nav-item active">
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
        <h4>Manage <span class="fw-800">Your Updates</span></h4>
        <div class="top-icons">
            <i class="far fa-bell icon-btn"></i>
            <div class="user-avatar"><?= substr($mentor['name'], 0, 1) ?></div>
        </div>
    </div>

    <div class="feed-wrapper">
        <div class="feed-main">
            <!-- Create Post Panel -->
            <div class="panel">
                <h5 class="panel-title">Share an Update</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_post">
                    <textarea name="content" class="post-input mb-4" rows="3" placeholder="What's on your mind? Share study tips or session updates..."></textarea>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="file-upload-btn" for="postImage">
                            <i class="fas fa-image"></i>
                            Add Photo
                            <input type="file" name="image" id="postImage" accept="image/*" hidden onchange="this.parentElement.style.background='#E6FAF5'; this.parentElement.style.color='#05CD99'; this.parentElement.innerHTML='<i class=\'fas fa-check\'></i> Image Added';">
                        </label>
                        <button type="submit" class="btn-post">Post Now</button>
                    </div>
                </form>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'posted'): ?>
                <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 fw-800 mb-4" style="background:#E6FAF5; color:#05CD99;">
                    <i class="fas fa-check-circle me-2"></i> Update posted successfully!
                </div>
            <?php endif; ?>

            <!-- Posts Feed -->
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-header">
                        <img src="<?= $photo_url ?>" class="poster-avatar">
                        <div class="post-info">
                            <h6><?= htmlspecialchars($mentor['name']) ?></h6>
                            <span class="post-time"><?= date('M d, Y • h:i A', strtotime($post['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="post-body">
                        <div class="post-text">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>
                        <?php if ($post['image_path']): ?>
                            <img src="../../uploads/<?= htmlspecialchars($post['image_path']) ?>" class="post-media shadow-sm">
                        <?php endif; ?>
                    </div>
                    <div class="post-footer">
                        <div class="post-stats">
                            <span class="stat-item <?= $post['liked_by_me'] ? 'liked' : '' ?>">
                                <i class="fas fa-thumbs-up"></i> <?= $post['likes_count'] ?> Likes
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-comment-dots"></i> 0 Comments
                            </span>
                        </div>
                        <span class="text-secondary fw-700" style="font-size: 12px;">Visible to all students</span>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <div class="mb-3 opacity-20"><i class="fas fa-feather" style="font-size: 60px;"></i></div>
                    <h5 class="fw-800 text-secondary">No updates yet</h5>
                    <p class="text-muted fw-600">Start by sharing your first study tip!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="feed-side">
            <div class="side-panel">
                <h5>Tips for Higher Engagement</h5>
                <div class="tip-card">
                    <i class="fas fa-lightbulb me-2 text-warning"></i>
                    Post helpful study resources or shortcuts.
                </div>
                <div class="tip-card">
                    <i class="fas fa-images me-2 text-info"></i>
                    Posts with images get 2x more interaction.
                </div>
                <div class="tip-card">
                    <i class="fas fa-clock me-2 text-success"></i>
                    Updates about your availability help students book earlier.
                </div>
                
                <div class="mt-4 pt-4 border-top border-white border-opacity-10 text-center">
                    <div class="h3 fw-800 mb-0"><?= count($posts) ?></div>
                    <div class="small fw-700 opacity-70">Total Updates</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


