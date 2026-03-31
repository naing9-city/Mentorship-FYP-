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

// Fetch Posts from mentors who share the same admin
$query = "
    SELECT mp.*, u.name as mentor_name, u.profile_photo as mentor_photo,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = mp.id) as likes_count,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = mp.id AND pl.user_id = ?) as liked_by_me,
           (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = mp.id) as comments_count
    FROM mentor_posts mp
    JOIN users u ON mp.mentor_id = u.id
    WHERE u.role = 'mentor' AND u.created_by = ?
    ORDER BY mp.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $admin_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Feed - MentorHub</title>
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
            align-items: center;
            gap: 12px;
            padding: 0 15px;
            margin-bottom: 50px;
            text-decoration: none;
        }

        .brand-logo i {
            font-size: 24px;
            color: var(--primary);
        }

        .brand-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
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
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            transition: 0.3s;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-item:hover:not(.active) {
            background: #f8fafc;
            color: var(--dark);
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
            background: var(--card-bg);
            border-radius: 30px;
            padding: 10px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 400px;
            box-shadow: var(--shadow-md);
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
            gap: 20px;
            background: var(--card-bg);
            padding: 8px 15px;
            border-radius: 30px;
            box-shadow: var(--shadow-md);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: var(--dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Feed Layout */
        .feed-wrapper {
            max-width: 650px;
            margin: 0 auto;
        }

        /* Post Card Premium */
        .post-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .m-avatar {
            width: 45px;
            height: 45px;
            border-radius: 14px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .m-initials {
            width: 45px;
            height: 45px;
            border-radius: 14px;
            background: linear-gradient(135deg, #11047A, #4318FF);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .c-initials {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #11047A, #4318FF);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .m-name {
            font-weight: 800;
            color: var(--dark);
            font-size: 15px;
            margin: 0;
        }

        .p-time {
            font-size: 12px;
            color: var(--secondary);
            font-weight: 600;
        }

        .p-content {
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .p-image {
            width: calc(100% + 50px);
            margin: 0 -25px 20px;
            max-height: 450px;
            object-fit: cover;
        }

        .p-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #f1f4ff;
            margin-bottom: 5px;
        }

        .stat-item {
            font-size: 13px;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-item i {
            font-size: 16px;
        }

        .p-actions {
            display: flex;
            border-top: 1px solid #f1f4ff;
            padding-top: 10px;
            gap: 5px;
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            color: var(--secondary);
            text-decoration: none;
            transition: 0.3s;
        }

        .action-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .action-btn.liked {
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: var(--secondary);
        }
    </style>
</head>

<body>

    <!-- Sidebar (Consistent) -->
    <div class="sidebar">
        <a href="index.php" class="brand-logo">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div class="brand-title">MentorHub</div>
                <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Student
                    Portal</div>
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
            <a href="learn.php" class="nav-item active">
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
                <input type="text" placeholder="Search posts, topics...">
            </div>
            <div class="top-icons">
                <i class="far fa-bell icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
                <i class="far fa-moon icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
                <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
            </div>
        </div>

        <div class="feed-wrapper">
            <div class="mb-4">
                <h2 class="fw-800 text-dark">Learning Feed</h2>
                <p class="text-secondary fw-700">Insights and updates from your mentors.</p>
            </div>

            <?php if (empty($posts)): ?>
                <div class="post-card text-center py-5">
                    <i class="fas fa-rss fa-4x mb-4 opacity-25"></i>
                    <h4 class="fw-800">No updates yet</h4>
                    <p class="text-secondary fw-600">Follow mentors to see their insights here.</p>
                    <a href="find_mentor.php" class="btn btn-primary rounded-pill px-4 mt-2">Explore Mentors</a>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    // Fetch comments for this post
                    $stmt_c = $pdo->prepare("SELECT pc.*, u.name, u.profile_photo FROM post_comments pc JOIN users u ON pc.user_id = u.id WHERE pc.post_id = ? ORDER BY pc.created_at ASC LIMIT 5");
                    $stmt_c->execute([$post['id']]);
                    $post_comments = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="post-card" id="post-<?= $post['id'] ?>">
                        <div class="post-header">
                            <?php if (!empty($post['mentor_photo'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($post['mentor_photo']) ?>" class="m-avatar">
                            <?php else: ?>
                                <div class="m-initials"><?= strtoupper(substr($post['mentor_name'], 0, 1)) ?></div>
                            <?php endif; ?>"
                                class="m-avatar">
                            <div>
                                <p class="m-name"><?= htmlspecialchars($post['mentor_name']) ?></p>
                                <span class="p-time"><?= date('M j \a\t g:i A', strtotime($post['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="p-content"><?= htmlspecialchars($post['content']) ?></div>

                        <?php if (!empty($post['image_path'])): ?>
                            <img src="../uploads/<?= $post['image_path'] ?>" class="p-image">
                        <?php endif; ?>

                        <div class="p-stats">
                            <span class="stat-item"><i class="fas fa-heart text-danger"></i> <?= $post['likes_count'] ?>
                                Likes</span>
                            <span class="stat-item"><?= $post['comments_count'] ?> Comments</span>
                        </div>

                        <div class="p-actions">
                            <a href="like_post.php?id=<?= $post['id'] ?>&redirect=learn"
                                class="action-btn <?= $post['liked_by_me'] ? 'liked' : '' ?>">
                                <i class="<?= $post['liked_by_me'] ? 'fas' : 'far' ?> fa-heart"></i> Like
                            </a>
                            <button class="action-btn" onclick="openCommentModal(<?= $post['id'] ?>)">
                                <i class="far fa-comment-dots"></i> Comment
                            </button>
                            <a href="chat.php?mentor_id=<?= $post['mentor_id'] ?>" class="action-btn">
                                <i class="far fa-paper-plane"></i> Message
                            </a>
                        </div>

                        <!-- Comments Section -->
                        <?php if (!empty($post_comments)): ?>
                            <div class="mt-3 pt-3 border-top border-light">
                                <?php foreach ($post_comments as $comment): ?>
                                    <div class="d-flex gap-2 mb-2 align-items-start">
                                        <?php if (!empty($comment['profile_photo'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($comment['profile_photo']) ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                                        <?php else: ?>
                                            <div class="c-initials"><?= strtoupper(substr($comment['name'], 0, 1)) ?></div>
                                        <?php endif; ?>
                                        <div class="bg-light p-2 rounded-3" style="font-size: 13px;">
                                            <span class="fw-bold d-block text-dark"><?= htmlspecialchars($comment['name']) ?></span>
                                            <?= htmlspecialchars($comment['content']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($post['comments_count'] > 5): ?>
                                    <div class="text-center mt-2">
                                        <small class="text-muted fst-italic">View all comments...</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-800">Add a Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="comment_post.php" method="POST">
                        <input type="hidden" name="post_id" id="modalPostId">
                        <div class="mb-3">
                            <textarea name="content" class="form-control" rows="4" placeholder="Share your thoughts..."
                                style="background: #f8fafc; border: 2px solid #f1f4ff; border-radius: 12px; font-weight: 600;"
                                required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light rounded-pill fw-700 me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill fw-700 px-4"
                                style="background: var(--primary); border: none;">Post Comment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCommentModal(postId) {
            document.getElementById('modalPostId').value = postId;
            var myModal = new bootstrap.Modal(document.getElementById('commentModal'));
            myModal.show();
        }

        // Check logic for URL param (backward compatibility if needed)
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const commentOn = urlParams.get('comment_on');
            if (commentOn) {
                openCommentModal(commentOn);
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
