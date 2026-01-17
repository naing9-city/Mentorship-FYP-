<?php
session_start();
require_once '../../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch super admin from the separate table
    $stmt = $pdo->prepare("SELECT * FROM super_admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $admin['password'] === md5($password)) {
        $_SESSION['super_admin_id'] = $admin['id'];
        $_SESSION['name'] = 'Super Admin'; // Required for dashboard
        $_SESSION['role'] = 'super_admin';

        session_write_close(); // Ensure session is written before redirect
        header("Location: system_dashboard.php");
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Portal - MentorHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* Premium Green Palette */
            --primary-green: #05CD99;
            --primary-emerald: #065F46;
            --accent-mint: #4FD1C5;
            --light-bg: #F0FDF4;
            --white: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #707EAE;
            --shadow-premium: 0px 40px 80px rgba(5, 205, 153, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #F0FDF4 0%, #E6FFFA 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            color: var(--text-dark);
            overflow: hidden;
        }

        .container {
            background-color: var(--white);
            border-radius: 40px;
            box-shadow: var(--shadow-premium);
            position: relative;
            overflow: hidden;
            width: 500px;
            max-width: 90%;
            padding: 60px 50px;
            text-align: center;
            animation: slideUp 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Premium Logo Styling */
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 42px;
            background: linear-gradient(135deg, var(--primary-green), var(--accent-mint));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 5px 10px rgba(5, 205, 153, 0.2));
        }

        .logo-text {
            text-align: left;
        }

        .logo-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
            letter-spacing: -1px;
            margin-bottom: 2px;
        }

        .logo-subtitle {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-green);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 15px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 35px;
        }

        .form-group {
            width: 100%;
            margin-bottom: 18px;
            text-align: left;
        }

        .form-control-custom {
            background: #F4F7FE;
            border: 2px solid transparent;
            border-radius: 20px;
            padding: 18px 24px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-green);
            background: var(--white);
            box-shadow: 0 10px 30px rgba(5, 205, 153, 0.08);
            transform: translateY(-2px);
        }

        .btn-custom {
            width: 100%;
            margin-top: 15px;
            border-radius: 20px;
            border: none;
            background: linear-gradient(135deg, var(--primary-emerald) 0%, var(--primary-green) 100%);
            color: var(--white);
            font-size: 16px;
            font-weight: 700;
            padding: 18px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 35px rgba(5, 205, 153, 0.25);
        }

        .btn-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(5, 205, 153, 0.35);
            filter: brightness(1.1);
        }

        .btn-custom:active {
            transform: scale(0.96);
        }

        .alert {
            padding: 16px;
            border-radius: 20px;
            width: 100%;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            background: #FFF5F5;
            color: #E53E3E;
            border: 1px solid #FED7D7;
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }

        .footer-links {
            margin-top: 30px;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .footer-links a {
            color: var(--primary-green);
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- New Unique Premium Logo -->
        <a href="#" class="brand-logo">
            <i class="fas fa-graduation-cap logo-icon"></i>
            <div class="logo-text">
                <div class="logo-title">MentorHub</div>
                <div class="logo-subtitle">Super Admin</div>
            </div>
        </a>

        <h1>System Access</h1>
        <p class="subtitle">Secure administrative control panel</p>

        <?php if ($message): ?>
            <div class="alert"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="System Email" class="form-control-custom" required />
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Root Password" class="form-control-custom"
                    required />
            </div>

            <button class="btn-custom" type="submit">Unlock Portal</button>
        </form>

        <div class="footer-links">
            <a href="../admin/login.php"><i class="fas fa-lock me-1"></i> Switch to Admin Login</a>
        </div>
    </div>

</body>

</html>