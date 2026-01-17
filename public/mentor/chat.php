<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

// Get mentor ID from session
$mentor_id = $_SESSION['user_id'] ?? 0;

if (!$mentor_id || $_SESSION['role'] !== 'mentor') {
    header("Location: ../index.php");
    exit;
}

// Get this mentor's info
$stmt = $pdo->prepare("SELECT name, profile_photo, created_by FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor_data = $stmt->fetch(PDO::FETCH_ASSOC);
$mentor_name = $mentor_data['name'];

// Fetch users who have had a conversation with this mentor
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
$stmt->execute([$mentor_id, $mentor_id, $mentor_id, $mentor_id, $mentor_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update my own last_seen
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$mentor_id]);

// Choose selected user
$selected = isset($_GET['student_id']) ? (int)$_GET['student_id'] : ($students[0]['id'] ?? 0);

// Fetch selected user info
$selUser = null;
if ($selected) {
    $stmt = $pdo->prepare("SELECT id, name, role, education, expertise, profile_photo FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$selected]);
    $selUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check for active appointment
$activeAppointment = null;
if ($selected) {
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE mentor_id = ? AND student_id = ? AND status = 'accepted' LIMIT 1");
    $stmt->execute([$mentor_id, $selected]);
    $activeAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
            --chat-sidebar-width: 350px;
            --details-width: 300px;
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

        /* Chat Layout */
        .chat-container {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            height: 100vh;
            background: white;
        }

        /* Chat Sidebar (User List) */
        .user-list-pane {
            width: var(--chat-sidebar-width);
            border-right: 1px solid #F4F7FE;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .pane-header { padding: 30px; }
        .pane-title { font-size: 24px; font-weight: 800; color: var(--dark); margin: 0; }
        
        .search-box {
            background: var(--primary-light); border-radius: 15px; padding: 12px 20px;
            display: flex; align-items: center; gap: 12px; margin: 0 30px 20px;
        }
        .search-box i { color: var(--secondary); }
        .search-box input { border: none; background: transparent; font-weight: 600; font-size: 14px; color: var(--dark); outline: none; width: 100%; }

        .user-items { flex: 1; overflow-y: auto; padding: 0 15px; }
        .user-item {
            display: flex; align-items: center; gap: 15px; padding: 15px;
            border-radius: 20px; cursor: pointer; text-decoration: none; color: inherit;
            transition: 0.3s; margin-bottom: 5px;
        }
        .user-item:hover { background: #f8fafc; }
        .user-item.active { background: var(--primary-light); }
        
        .avatar-wrapper { position: relative; }
        .user-avatar-img { width: 50px; height: 50px; border-radius: 16px; object-fit: cover; }
        .status-dot {
            position: absolute; bottom: -2px; right: -2px; width: 14px; height: 14px;
            border-radius: 50%; border: 3px solid white; background: #CBD5E0;
        }
        .status-dot.online { background: var(--success); }

        .user-details-mini { flex: 1; min-width: 0; }
        .user-name-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px; }
        .user-name-row h6 { margin: 0; font-weight: 800; font-size: 15px; color: var(--dark); }
        .user-item-time { font-size: 11px; font-weight: 700; color: var(--secondary); }
        .user-item-preview { font-size: 12px; color: var(--secondary); font-weight: 600; }
        
        .unread-indicator {
            background: var(--primary); color: white; min-width: 18px; height: 18px;
            border-radius: 9px; font-size: 10px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; padding: 0 5px;
        }

        /* Chat View (Messages) */
        .chat-view-pane { flex: 1; display: flex; flex-direction: column; background: #fafbfc; position: relative; }
        .chat-view-header {
            padding: 20px 30px; background: white; border-bottom: 1px solid #F4F7FE;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-user-info { display: flex; align-items: center; gap: 15px; }
        .header-avatar { width: 44px; height: 44px; border-radius: 14px; object-fit: cover; }
        .header-text h6 { margin: 0; font-weight: 800; color: var(--dark); font-size: 16px; }
        .header-text p { margin: 0; font-size: 12px; font-weight: 700; color: var(--success); }

        .messages-container {
            flex: 1; overflow-y: auto; padding: 30px;
            display: flex; flex-direction: column; gap: 15px;
        }

        .msg-row { display: flex; flex-direction: column; max-width: 70%; }
        .msg-row.me { align-self: flex-end; align-items: flex-end; }
        .msg-row.them { align-self: flex-start; align-items: flex-start; }

        .msg-bubble {
            padding: 14px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; line-height: 1.5;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .me .msg-bubble { background: var(--primary); color: white; border-bottom-right-radius: 4px; }
        .them .msg-bubble { background: white; color: var(--dark); border-bottom-left-radius: 4px; border: 1px solid #f1f4ff; }
        
        .msg-meta { font-size: 10px; font-weight: 700; color: var(--secondary); margin-top: 6px; }
        .attachment-img { max-width: 250px; border-radius: 15px; margin-top: 10px; border: 2px solid white; box-shadow: var(--shadow-md); }

        /* Chat Input */
        .chat-input-area { padding: 25px 30px; background: white; border-top: 1px solid #F4F7FE; }
        .input-group-premium {
            background: var(--primary-light); border-radius: 20px; padding: 8px 10px;
            display: flex; align-items: center; gap: 10px;
        }
        .input-group-premium input {
            flex: 1; border: none; background: transparent; padding: 10px 15px;
            font-weight: 600; color: var(--dark); outline: none; font-size: 14px;
        }
        .action-icon {
            width: 44px; height: 44px; border-radius: 15px; border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.3s; font-size: 18px;
        }
        .btn-attach { background: white; color: var(--secondary); }
        .btn-attach:hover { background: #f8fafc; color: var(--primary); }
        .btn-send { background: var(--primary); color: white; box-shadow: 0 8px 16px rgba(67, 24, 255, 0.2); }
        .btn-send:hover { transform: scale(1.05); }

        /* Details Pane */
        .info-pane {
            width: var(--details-width); border-left: 1px solid #F4F7FE;
            display: flex; flex-direction: column; overflow-y: auto; background: white;
        }
        .info-content { padding: 40px 30px; text-align: center; }
        .info-avatar { width: 100px; height: 100px; border-radius: 30px; margin-bottom: 20px; object-fit: cover; box-shadow: var(--shadow-md); }
        .info-name { font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 5px; }
        .info-role { font-size: 13px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; }

        .info-section { margin-top: 40px; text-align: left; }
        .info-section-label { font-size: 11px; font-weight: 800; color: var(--secondary); text-transform: uppercase; margin-bottom: 15px; display: block; }
        
        .info-stat-card {
            background: var(--primary-light); border-radius: 20px; padding: 15px;
            display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
            text-decoration: none; transition: 0.3s;
        }
        .info-stat-card:hover { transform: translateY(-2px); background: #f1f4ff; }
        .info-stat-card i { font-size: 18px; color: var(--primary); }
        .info-stat-text div:first-child { font-weight: 800; color: var(--dark); font-size: 14px; }
        .info-stat-text div:last-child { font-size: 11px; color: var(--secondary); font-weight: 700; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #CBD5E0; }

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
        <a href="chat.php" class="nav-item active">
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

<div class="chat-container">
    <!-- User List -->
    <div class="user-list-pane">
        <div class="pane-header">
            <h4 class="pane-title">Messages</h4>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search students...">
        </div>
        <div class="user-items">
            <?php if ($students): ?>
                <?php foreach($students as $s): ?>
                    <?php 
                        $isOnline = (strtotime($s['last_seen']) > time() - 300);
                        $photo = !empty($s['profile_photo']) ? $s['profile_photo'] : "https://ui-avatars.com/api/?name=".urlencode($s['name'])."&background=4318FF&color=fff";
                    ?>
                    <a class="user-item <?= $s['id'] == $selected ? 'active' : '' ?>" href="?student_id=<?= $s['id'] ?>">
                        <div class="avatar-wrapper">
                            <img src="<?= $photo ?>" class="user-avatar-img">
                            <div class="status-dot <?= $isOnline ? 'online' : '' ?>"></div>
                        </div>
                        <div class="user-details-mini">
                            <div class="user-name-row">
                                <h6><?= htmlspecialchars($s['name']) ?></h6>
                                <span class="user-item-time"><?= $s['latest_msg'] ? date('h:i A', strtotime($s['latest_msg'])) : '' ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="user-item-preview text-truncate">Student • <?= $s['unread'] > 0 ? 'New message' : 'Active' ?></span>
                                <?php if($s['unread'] > 0): ?>
                                    <span class="unread-indicator"><?= $s['unread'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-5 text-center text-secondary opacity-50">
                    <i class="far fa-comments fa-3x mb-3"></i>
                    <p class="small fw-800">No conversations found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message View -->
    <div class="chat-view-pane">
        <?php if ($selUser): ?>
            <?php $selPhoto = !empty($selUser['profile_photo']) ? $selUser['profile_photo'] : "https://ui-avatars.com/api/?name=".urlencode($selUser['name'])."&background=4318FF&color=fff"; ?>
            <div class="chat-view-header">
                <div class="header-user-info">
                    <img src="<?= $selPhoto ?>" class="header-avatar">
                    <div class="header-text">
                        <h6><?= htmlspecialchars($selUser['name']) ?></h6>
                        <p><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Student</p>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if($activeAppointment): ?>
                        <a href="../video_room.php?id=<?= $activeAppointment['id'] ?>" class="btn-action-sm btn-action-primary" style="padding: 10px 20px;">
                            <i class="fas fa-video"></i> Start Call
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="messages-container" id="chat-box">
                <!-- AJAX Loads Messages -->
            </div>

            <div class="chat-input-area">
                <div class="input-group-premium">
                    <label class="action-icon btn-attach" for="fileInput">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="fileInput" hidden>
                    </label>
                    <input type="text" id="message" placeholder="Type a message...">
                    <button class="action-icon btn-send" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center px-4">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-chat-3682570-3075841.png" style="max-width: 250px; opacity: 0.7;" class="mb-4">
                <h4 class="fw-800 text-dark">Your Conversations</h4>
                <p class="text-secondary fw-600 px-5">Select a student from the list on the left to start a conversation or view shared files.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Pane -->
    <?php if ($selUser): ?>
        <div class="info-pane">
            <div class="info-content">
                <img src="<?= $selPhoto ?>" class="info-avatar">
                <h5 class="info-name"><?= htmlspecialchars($selUser['name']) ?></h5>
                <span class="info-role">MentorHub Student</span>

                <div class="info-section">
                    <span class="info-section-label">Quick Actions</span>
                    <a href="mentor_profile.php?id=<?= $selUser['id'] ?>" class="info-stat-card">
                        <i class="far fa-user-circle"></i>
                        <div class="info-stat-text">
                            <div>Public Profile</div>
                            <div>View student bio & stats</div>
                        </div>
                    </a>
                    <?php if($activeAppointment): ?>
                        <a href="../video_room.php?id=<?= $activeAppointment['id'] ?>" class="info-stat-card">
                            <i class="fas fa-phone-alt"></i>
                            <div class="info-stat-text">
                                <div>Launch Session</div>
                                <div>Join active video call</div>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <span class="info-section-label">Shared Media</span>
                    <div class="text-center py-4 bg-light rounded-4 opacity-50">
                        <i class="far fa-images fa-2x mb-2"></i>
                        <p class="small fw-700 m-0">No media found</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const me = <?= json_encode($mentor_id) ?>;
let other = <?= json_encode($selected) ?>;
const chatBox = document.getElementById('chat-box');

function sanitize(text){
    if(!text) return '';
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function renderMessages(messages){
    if(!chatBox) return;
    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
    chatBox.innerHTML = '';
    
    messages.forEach(m => {
        const row = document.createElement('div');
        row.className = 'msg-row ' + (m.sender_id == me ? 'me' : 'them');

        let content = `<div class="msg-bubble">${sanitize(m.message)}`;
        
        if(m.attachment_path) {
            const ext = m.attachment_path.split('.').pop().toLowerCase();
            if(['jpg','jpeg','png','gif','webp'].includes(ext)){
                content += `<div><img src="../../uploads/${m.attachment_path}" class="attachment-img"></div>`;
            } else {
                content += `<div class="mt-2 small"><a href="../../uploads/${m.attachment_path}" target="_blank" class="text-white fw-bold"><i class="fas fa-file-alt"></i> View Reference File</a></div>`;
            }
        }
        content += `</div><div class="msg-meta">${m.created_at}</div>`;
        
        row.innerHTML = content;
        chatBox.appendChild(row);
    });

    if(isAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
}

async function load(){
    if(!other) return;
    try {
        const res = await fetch('../chat_fetch.php?other=' + encodeURIComponent(other));
        const data = await res.json();
        renderMessages(data);
    } catch(e) { console.error("Fetch Error:", e); }
}

document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
document.getElementById('message')?.addEventListener('keypress', e => {
    if(e.key === 'Enter') sendMessage();
});

async function sendMessage(){
    const msgInput = document.getElementById('message');
    const fileInput = document.getElementById('fileInput');
    const message = msgInput.value.trim();
    
    if(!message && fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('receiver_id', other);
    formData.append('message', message);
    if(fileInput.files.length > 0) formData.append('file', fileInput.files[0]);

    msgInput.value = '';
    fileInput.value = '';
    
    await fetch('../chat_send.php', { method:'POST', body: formData });
    load();
}

if(other){
    load();
    setInterval(load, 2000);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

