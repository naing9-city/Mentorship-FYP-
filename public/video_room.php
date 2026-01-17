<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    die("No appointment specified.");
}

// Fetch appointment details - user must be either the student or mentor
$stmt = $pdo->prepare("
    SELECT a.*, 
           s.name as student_name, 
           m.name as mentor_name
    FROM appointments a
    JOIN users s ON a.student_id = s.id
    JOIN users m ON a.mentor_id = m.id
    WHERE a.id = ? 
      AND (a.student_id = ? OR a.mentor_id = ?)
      AND a.status = 'accepted'
");
$stmt->execute([$appointment_id, $user_id, $user_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    die("Invalid appointment or not accepted yet.");
}

// Generate room_id if not exists
if (empty($appointment['room_id'])) {
    $room_id = 'eduMent_' . $appointment_id . '_' . bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("UPDATE appointments SET room_id = ? WHERE id = ?");
    $stmt->execute([$room_id, $appointment_id]);
    $appointment['room_id'] = $room_id;
}

$room_id = $appointment['room_id'];
$is_mentor = ($user_id == $appointment['mentor_id']);
$user_name = $is_mentor ? $appointment['mentor_name'] : $appointment['student_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Call - EduMent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #1a1a2e; 
            height: 100vh; 
            display: flex; 
            flex-direction: column;
        }
        .call-header {
            background: #16213e;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .call-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .call-info .badge {
            background: #4f46e5;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .call-with {
            font-size: 14px;
            color: #94a3b8;
        }
        .call-with strong {
            color: white;
        }
        .call-actions a {
            color: #ef4444;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid #ef4444;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .call-actions a:hover {
            background: #ef4444;
            color: white;
        }
        #jitsi-container {
            flex: 1;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="call-header">
    <div class="call-info">
        <span class="badge"><i class="fas fa-video me-2"></i> Live Session</span>
        <div class="call-with">
            <?php if($is_mentor): ?>
                Teaching: <strong><?= htmlspecialchars($appointment['student_name']) ?></strong>
            <?php else: ?>
                Learning from: <strong><?= htmlspecialchars($appointment['mentor_name']) ?></strong>
            <?php endif; ?>
        </div>
    </div>
    <div class="call-actions">
        <?php if($is_mentor): ?>
            <a href="mentor/index.php"><i class="fas fa-sign-out-alt me-2"></i> Leave Call</a>
        <?php else: ?>
            <a href="student/my_appointments.php"><i class="fas fa-sign-out-alt me-2"></i> Leave Call</a>
        <?php endif; ?>
    </div>
</div>

<div id="jitsi-container"></div>

<!-- Jitsi Meet API -->
<script src="https://meet.jit.si/external_api.js"></script>
<script>
    const domain = 'meet.jit.si';
    const options = {
        roomName: '<?= htmlspecialchars($room_id) ?>',
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#jitsi-container'),
        userInfo: {
            displayName: '<?= htmlspecialchars($user_name) ?>'
        },
        configOverwrite: {
            startWithAudioMuted: false,
            startWithVideoMuted: false,
            prejoinPageEnabled: false
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'chat', 'settings',
                'raisehand', 'videoquality', 'filmstrip', 'tileview'
            ],
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
            DEFAULT_BACKGROUND: '#1a1a2e'
        }
    };
    
    const api = new JitsiMeetExternalAPI(domain, options);
    
    // Handle call end
    api.addEventListener('readyToClose', () => {
        <?php if($is_mentor): ?>
            window.location.href = 'mentor/index.php';
        <?php else: ?>
            window.location.href = 'student/my_appointments.php';
        <?php endif; ?>
    });
</script>

</body>
</html>
