<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student's info
$stmt = $pdo->prepare("SELECT name, profile_photo, mentor_status FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);
$mentor_status = $student_data['mentor_status'];

// Fetch Mentors who have had a conversation with this student
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.role, u.last_seen, u.profile_photo,
           (SELECT COUNT(*) FROM messages m WHERE m.receiver_id = ? AND m.sender_id = u.id AND m.is_read = 0) as unread,
           (SELECT MAX(created_at) FROM messages m WHERE (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)) as latest_msg
    FROM users u
    WHERE u.id IN (
        SELECT sender_id FROM messages WHERE receiver_id = ?
        UNION
        SELECT receiver_id FROM messages WHERE sender_id = ?
    )
    ORDER BY latest_msg DESC
");
$stmt->execute([$student_id, $student_id, $student_id, $student_id, $student_id]);
$mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If mentor_id is passed in URL, ensure they are in the list or pre-selected
$selected = isset($_GET['mentor_id']) ? (int) $_GET['mentor_id'] : ($mentors[0]['id'] ?? 0);

// Fetch selected mentor info
$selMentor = null;
if ($selected) {
    $stmt = $pdo->prepare("SELECT id, name, role, expertise, education, profile_photo, last_seen FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$selected]);
    $selMentor = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check for active appointment
$activeAppt = null;
if ($selected) {
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE student_id = ? AND mentor_id = ? AND status = 'accepted' LIMIT 1");
    $stmt->execute([$student_id, $selected]);
    $activeAppt = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update last seen
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$student_id]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MentorHub</title>
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
            height: 100vh;
            margin: 0;
            display: flex;
            overflow: hidden;
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

        /* Main Content Container */
        .app-container {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: transparent;
            flex-shrink: 0;
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

        .user-avatar-top {
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

        /* Chat Layout */
        .chat-layout {
            flex: 1;
            display: flex;
            overflow: hidden;
            background: var(--card-bg);
            margin: 0 40px 40px;
            border-radius: 25px;
            box-shadow: var(--shadow-md);
        }

        /* User List Sidebar */
        .user-sidebar {
            width: 350px;
            border-right: 1px solid #f1f4ff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid #f1f4ff;
        }

        .sidebar-header h5 {
            font-weight: 800;
            color: var(--dark);
            margin: 0;
        }

        .user-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-radius: 18px;
            margin-bottom: 5px;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: 0.3s;
        }

        .user-item:hover {
            background: #f8fafc;
        }

        .user-item.active {
            background: var(--primary-light);
        }

        .u-avatar {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            margin-right: 15px;
            background-size: cover;
            position: relative;
            background-color: var(--bg);
        }

        .online-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success);
            border: 2px solid white;
        }

        .u-info {
            flex: 1;
            min-width: 0;
        }

        .u-name {
            font-weight: 800;
            color: var(--dark);
            font-size: 14px;
            margin-bottom: 2px;
        }

        .u-preview {
            font-size: 12px;
            color: var(--secondary);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .u-badge {
            background: var(--primary);
            color: white;
            border-radius: 8px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 800;
        }

        /* Message Area */
        .message-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            padding: 20px 30px;
            border-bottom: 1px solid #f1f4ff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-name {
            font-weight: 800;
            color: var(--dark);
            font-size: 16px;
            margin: 0;
        }

        .header-status {
            font-size: 12px;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .messages-box {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #fafbfc;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .msg {
            max-width: 70%;
            display: flex;
            flex-direction: column;
        }

        .msg.me {
            align-self: flex-end;
            align-items: flex-end;
        }

        .msg.them {
            align-self: flex-start;
            align-items: flex-start;
        }

        .bubble {
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.6;
        }

        .me .bubble {
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 10px 20px rgba(67, 24, 255, 0.15);
        }

        .them .bubble {
            background: #fff;
            color: var(--dark);
            border-bottom-left-radius: 4px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f4ff;
        }

        .m-time {
            font-size: 10px;
            color: var(--secondary);
            font-weight: 700;
            margin-top: 6px;
        }

        /* Input Panel */
        .input-panel {
            padding: 20px 30px;
            border-top: 1px solid #f1f4ff;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .input-group-custom {
            flex: 1;
            background: #f4f7fe;
            border-radius: 15px;
            padding: 5px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
            transition: 0.3s;
        }

        .input-group-custom:focus-within {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05);
        }

        .input-group-custom input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 0;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            color: var(--dark);
        }

        .btn-action {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: 0.3s;
            cursor: pointer;
        }

        .btn-plus {
            background: #fff;
            color: var(--secondary);
            border: 1px solid #e2e8f0;
        }

        .btn-plus:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .btn-send {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2);
        }

        .btn-send:hover {
            transform: scale(1.05);
        }

        /* Right Sidebar (Mentor Info) */
        .mentor-details {
            width: 300px;
            border-left: 1px solid #f1f4ff;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }

        .m-pic {
            width: 100px;
            height: 100px;
            border-radius: 30px;
            margin-bottom: 20px;
            object-fit: cover;
            box-shadow: var(--shadow-md);
        }

        .m-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .m-role {
            font-size: 13px;
            font-weight: 700;
            color: var(--secondary);
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        .m-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-p-sm {
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: 0.3s;
        }

        .btn-p-primary {
            background: var(--primary);
            color: white;
        }

        .btn-p-light {
            background: var(--primary-light);
            color: var(--primary);
        }

        .btn-p-sm:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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
            <a href="chat.php" class="nav-item active">
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
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="app-container">

        <!-- Top Bar -->
        <div class="topbar">
            <div class="search-box-top">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search conversations...">
            </div>
            <div class="top-icons">
                <i class="far fa-bell icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
                <i class="far fa-moon icon-btn" style="color: var(--secondary); font-size: 18px; cursor: pointer;"></i>
                <div class="user-avatar-top"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
            </div>
        </div>

        <div class="chat-layout">
            <!-- Sidebar: Conversations -->
            <div class="user-sidebar">
                <div class="sidebar-header">
                    <h5>Messages</h5>
                </div>
                <div class="user-list">
                    <?php foreach ($mentors as $m): ?>
                        <?php
                        $isOnline = (strtotime($m['last_seen']) > time() - 300);
                        $photo = !empty($m['profile_photo']) ? '../../uploads/' . $m['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['name']) . '&background=random&color=fff';
                        ?>
                        <a class="user-item <?= $m['id'] == $selected ? 'active' : '' ?>" href="?mentor_id=<?= $m['id'] ?>">
                            <div class="u-avatar" style="background-image: url('<?= $photo ?>')">
                                <?php if ($isOnline): ?>
                                    <div class="online-dot"></div><?php endif; ?>
                            </div>
                            <div class="u-info">
                                <div class="u-name"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="u-preview">
                                    <?= $m['unread'] > 0 ? 'New message recived' : 'Click to open chat' ?></div>
                            </div>
                            <?php if ($m['unread'] > 0): ?><span class="u-badge"><?= $m['unread'] ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="message-area">
                <?php if ($selMentor): ?>
                    <?php $selPhoto = !empty($selMentor['profile_photo']) ? '../../uploads/' . $selMentor['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($selMentor['name']) . '&background=random&color=fff'; ?>
                    <div class="chat-header">
                        <div class="header-info">
                            <div class="u-avatar"
                                style="width: 40px; height: 40px; background-image: url('<?= $selPhoto ?>')"></div>
                            <div>
                                <h6 class="header-name"><?= htmlspecialchars($selMentor['name']) ?></h6>
                                <div class="header-status">
                                    <i class="fas fa-circle" style="font-size: 8px; color: var(--success);"></i>
                                    <span>Expert Mentor</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <?php if ($activeAppt): ?>
                                <a href="../video_room.php?id=<?= $activeAppt['id'] ?>" class="btn-p-sm btn-p-primary px-4">
                                    <i class="fas fa-video me-1"></i> Join Call
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="messages-box" id="chat-box">
                        <!-- JS loaded messages here -->
                    </div>

                    <div class="input-panel">
                        <label class="btn-action btn-plus" for="fileInput"><i class="fas fa-plus"></i></label>
                        <input type="file" id="fileInput" hidden>
                        <div class="input-group-custom">
                            <input type="text" id="message" placeholder="Write your message here...">
                        </div>
                        <button class="btn-action btn-send" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                <?php else: ?>
                    <div
                        class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary opacity-50">
                        <i class="fas fa-comments fa-4x mb-4"></i>
                        <h5 class="fw-800">Select a conversation to start chatting</h5>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Side: Mentor Profile -->
            <?php if ($selMentor): ?>
                <div class="mentor-details">
                    <img src="<?= $selPhoto ?>" class="m-pic" alt="Profile">
                    <div class="m-name"><?= htmlspecialchars($selMentor['name']) ?></div>
                    <div class="m-role">Mentor</div>

                    <div class="m-actions">
                        <a href="make_appointment.php?mentor_id=<?= $selMentor['id'] ?>" class="btn-p-sm btn-p-primary"><i
                                class="fas fa-calendar-plus me-2"></i> Book Session</a>
                        <a href="../mentor/mentor_profile.php?id=<?= $selMentor['id'] ?>&return_to=student"
                            class="btn-p-sm btn-p-light"><i class="fas fa-user-circle me-2"></i> View Profile</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        const me = <?= json_encode($student_id) ?>;
        let other = <?= json_encode($selected) ?>;
        const chatBox = document.getElementById('chat-box');

        function renderMessages(messages) {
            if (!chatBox) return;
            const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 80;
            chatBox.innerHTML = '';
            messages.forEach(m => {
                const div = document.createElement('div');
                div.className = 'msg ' + (m.sender_id == me ? 'me' : 'them');
                let content = `<div class="bubble">${m.message ? m.message : ''}`;
                if (m.attachment_path) {
                    const ext = m.attachment_path.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) content += `<div><img src="../../uploads/${m.attachment_path}" style="max-width:200px; border-radius:10px; margin-top:10px;"></div>`;
                    else content += `<div class="mt-2"><a href="../../uploads/${m.attachment_path}" target="_blank" class="small text-decoration-none"><i class="fas fa-file"></i> View File</a></div>`;
                }
                content += `</div><div class="m-time">${m.created_at}</div>`;
                div.innerHTML = content;
                chatBox.appendChild(div);
            });
            if (isAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function load() {
            if (!other) return;
            try {
                const res = await fetch('../chat_fetch.php?other=' + encodeURIComponent(other));
                const data = await res.json();
                renderMessages(data);
            } catch (e) { console.error(e); }
        }

        document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
        document.getElementById('message')?.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });

        async function sendMessage() {
            const input = document.getElementById('message');
            const file = document.getElementById('fileInput');
            if (!input.value.trim() && file.files.length === 0) return;

            const fd = new FormData();
            fd.append('receiver_id', other);
            fd.append('message', input.value.trim());
            if (file.files.length > 0) fd.append('file', file.files[0]);

            input.value = '';
            file.value = '';
            await fetch('../chat_send.php', { method: 'POST', body: fd });
            load();
        }

        if (other) {
            load();
            setInterval(load, 2000);
        }
    </script>
</body>

</html>