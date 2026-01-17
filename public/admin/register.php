<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $secret_code = trim($_POST['secret_code'] ?? '');

    // 1. Fetch token record to check state (Strict 5-minute window + 1 min grace)
    $stmt = $pdo->prepare("SELECT *, (created_at > NOW() - INTERVAL 6 MINUTE) as is_recent FROM admin_keys WHERE token = ?");
    $stmt->execute([$secret_code]);
    $key_record = $stmt->fetch();

    if (!$key_record) {
        $error = "The secret code does not exist. Check for typos.";
    } elseif ($key_record['is_used'] == 1) {
        $error = "This secret code has already been used and is now inactive.";
    } elseif (!$key_record['is_recent']) {
        $error = "This secret code has expired (valid for 5 mins only). Request a new one.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            $error = "This email is already registered.";
        } else {
            $pdo->beginTransaction();
            try {
                // 2. Hash password
                $hash = password_hash($password, PASSWORD_BCRYPT);

                // 3. Insert new admin
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$name, $email, $hash]);

                // 4. Mark key as used
                $stmt = $pdo->prepare("UPDATE admin_keys SET is_used = 1 WHERE id = ?");
                $stmt->execute([$key_record['id']]);

                $pdo->commit();
                $success = "Admin account created successfully! Redirecting to login...";
                header("Refresh:2; url=../login.php");
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration - EduNexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4318FF;
            --primary-light: #F4F7FE;
            --secondary: #707EAE;
            --dark: #1B2559;
            --bg: #F4F7FE;
            --card-bg: #FFFFFF;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .auth-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0px 18px 40px rgba(112, 144, 176, 0.12);
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary);
        }

        .form-label {
            font-weight: 700;
            font-size: 14px;
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .form-control {
            border-radius: 16px;
            padding: 12px 20px;
            background: #F4F7FE;
            border: 1px solid transparent;
            font-weight: 600;
            transition: 0.3s;
        }

        .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1);
            outline: none;
        }

        .btn-auth {
            background: var(--primary);
            color: white;
            border-radius: 16px;
            padding: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-auth:hover {
            background: #3311db;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 24, 255, 0.4);
        }

        .alert { border-radius: 16px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand">
        <i class="fas fa-graduation-cap me-2"></i> EduNexus
    </div>
    
    <h4 class="fw-800 mb-2">Admin Registration</h4>
    <p class="text-secondary small mb-4">Secured management portal entry</p>

    <?php if($error) echo "<div class='alert alert-danger border-0 mb-3'><i class='fas fa-exclamation-circle me-2'></i> $error</div>"; ?>
    <?php if($success) echo "<div class='alert alert-success border-0 mb-3'><i class='fas fa-check-circle me-2'></i> $success</div>"; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="admin@edunexus.com" required>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••" required>
            </div>
            <div class="col-6">
                <label class="form-label">Confirm</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Invitation Code</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0" style="border-radius: 16px 0 0 16px;"><i class="fas fa-key text-primary"></i></span>
                <input type="text" name="secret_code" class="form-control" placeholder="8-digit code" style="border-radius: 0 16px 16px 0;" required>
            </div>
            <div class="xtra-small text-muted mt-2">Request this from the Super Admin.</div>
        </div>

        <button type="submit" class="btn-auth">Register Portal</button>
    </form>

    <div class="text-center mt-4">
        <a href="../login.php" class="text-secondary small fw-700 text-decoration-none">Already have access? Login</a>
    </div>
</div>

</body>
</html>
