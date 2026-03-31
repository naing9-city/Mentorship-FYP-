<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appt_id = $_GET['appointment_id'] ?? null;

if (!$appt_id) {
    header("Location: my_appointments.php");
    exit;
}

// Fetch Appointment Details
$stmt = $pdo->prepare("
    SELECT a.*, u.name as mentor_name, u.profile_photo as mentor_photo 
    FROM appointments a 
    JOIN users u ON a.mentor_id = u.id 
    WHERE a.id = ? AND a.student_id = ? AND a.status = 'completed'
");
$stmt->execute([$appt_id, $user_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) {
    header("Location: my_appointments.php");
    exit;
}

// Check if already rated
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE appointment_id = ?");
$stmt->execute([$appt_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: my_appointments.php?status=already_rated");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = $_POST['comment'] ?? '';
    
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO ratings (appointment_id, student_id, mentor_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$appt_id, $user_id, $appt['mentor_id'], $rating, $comment]);
        
        header("Location: my_appointments.php?rated=1");
        exit;
    } else {
        $message = "Please select a rating between 1 and 5.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Session - MentorHub</title>
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
            --shadow-md: 0px 18px 40px rgba(112, 144, 176, 0.12);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .rating-card {
            background: var(--card-bg);
            border-radius: 30px;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 550px;
            padding: 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .rating-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(90deg, var(--primary), #7033FF);
        }

        .mentor-avatar {
            width: 100px; height: 100px;
            border-radius: 25px;
            object-fit: cover;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            border: 4px solid white;
        }

        .rating-header h3 { font-size: 24px; font-weight: 800; color: var(--dark); margin-bottom: 10px; }
        .rating-header p { font-size: 15px; color: var(--secondary); font-weight: 600; margin-bottom: 30px; }

        /* Star Rating System */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 12px;
            margin-bottom: 35px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 42px;
            color: #E0E5F2;
            cursor: pointer;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: var(--warning);
            transform: scale(1.1);
        }

        .form-label { font-weight: 700; color: var(--dark); font-size: 14px; margin-bottom: 12px; display: block; text-align: left; }
        .form-control {
            border-radius: 15px; padding: 18px 20px; border: 2px solid #f1f4ff;
            background: #f8fafc; font-weight: 600; color: var(--dark); transition: 0.3s;
            resize: none;
        }
        .form-control:focus {
            background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05);
        }

        .btn-premium {
            background: var(--primary); color: white; border: none; padding: 18px;
            border-radius: 20px; font-weight: 800; font-size: 16px; width: 100%;
            margin-top: 30px; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); transition: 0.3s;
            cursor: pointer;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(67, 24, 255, 0.3); opacity: 0.95; }

        .btn-cancel {
            display: inline-block; margin-top: 20px; color: var(--secondary);
            font-weight: 700; font-size: 14px; text-decoration: none; transition: 0.2s;
        }
        .btn-cancel:hover { color: var(--danger); text-decoration: underline; }

        .alert-premium { border-radius: 15px; border: none; font-weight: 700; padding: 15px 20px; font-size: 14px; }

    </style>
</head>
<body>

<div class="rating-card">
    <img src="<?= !empty($appt['mentor_photo']) ? '../uploads/'.$appt['mentor_photo'] : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2EwYWVjMCI+PHBhdGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg=='.urlencode($appt['mentor_name']).'&background=4318FF&color=fff' ?>" class="mentor-avatar">
    
    <div class="rating-header">
        <h3>Share Your Experience</h3>
        <p>How would you rate your session with <strong><?= htmlspecialchars($appt['mentor_name']) ?></strong>?</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-danger alert-premium mb-4 shadow-sm"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="star-rating">
            <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="Excellent"><i class="fas fa-star"></i></label>
            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Very Good"><i class="fas fa-star"></i></label>
            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Good"><i class="fas fa-star"></i></label>
            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Poor"><i class="fas fa-star"></i></label>
            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Very Poor"><i class="fas fa-star"></i></label>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Write a public review (Optional)</label>
            <textarea name="comment" class="form-control" rows="4" placeholder="What did you learn? What was helpful?"></textarea>
        </div>
        
        <button type="submit" class="btn-premium">Submit Review Now</button>
        <a href="my_appointments.php" class="btn-cancel">I'll do it later</a>
    </form>
</div>

</body>
</html>

