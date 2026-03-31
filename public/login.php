<?php
session_start();
require_once '../includes/db.php';

// Auto-redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student/index.php");
        exit;
    } elseif ($_SESSION['role'] === 'mentor') {
        header("Location: mentor/index.php");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Students & mentors only (not admin)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('student', 'mentor')");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'suspended') {
            $error = "Your account has been suspended. Please contact the administrator.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'student') {
                header("Location: student/index.php");
                exit;
            } elseif ($user['role'] === 'mentor') {
                header("Location: mentor/index.php");
                exit;
            }
        }
    } else {
        $error = "Incorrect email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MentorHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* Premium Blue Palette */
            --primary-blue: #4318FF;
            --primary-dark: #11047A;
            --accent-blue: #00D1FF;
            --light-bg: #F4F7FE;
            --white: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #707EAE;
            --shadow-premium: 0px 40px 80px rgba(67, 24, 255, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #F4F7FE 0%, #E9EDF7 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            color: var(--text-dark);
            overflow: hidden;
        }

        /* Ambient Animated Background */
        body::before {
            content: '';
            position: absolute;
            width: 150vw;
            height: 150vh;
            background: radial-gradient(circle, rgba(67, 24, 255, 0.05) 0%, rgba(0, 209, 255, 0.03) 50%, transparent 100%);
            animation: drift 20s linear infinite;
            z-index: -1;
        }

        @keyframes drift {
            from {
                transform: translate(-25%, -25%) rotate(0deg);
            }

            to {
                transform: translate(-25%, -25%) rotate(360deg);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 40px;
            box-shadow: var(--shadow-premium);
            width: 500px;
            max-width: 95%;
            padding: 50px;
            text-align: center;
            position: relative;
            animation: cardAppear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes cardAppear {
            from {
                transform: translateY(30px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        /* Branding */
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .brand-logo:hover {
            transform: scale(1.05);
        }

        .brand-logo i {
            font-size: 32px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-logo span {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        p.subtitle {
            font-size: 15px;
            color: var(--text-muted);
            margin-bottom: 35px;
            font-weight: 500;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .form-control-custom {
            width: 100%;
            padding: 16px 20px;
            background: #F8FAFC;
            border: 2px solid transparent;
            border-radius: 18px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: var(--text-dark);
        }

        .form-control-custom:focus {
            outline: none;
            background: var(--white);
            border-color: var(--primary-blue);
            box-shadow: 0 10px 25px rgba(67, 24, 255, 0.1);
            transform: translateY(-2px);
        }

        .btn-premium {
            width: 100%;
            padding: 18px;
            margin-top: 15px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            color: var(--white);
            border: none;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s;
            box-shadow: 0 15px 30px rgba(17, 4, 122, 0.2);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(17, 4, 122, 0.3);
            filter: brightness(1.1);
        }

        .btn-premium:active {
            transform: scale(0.98);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E2E8F0;
        }

        .divider span {
            padding: 0 15px;
        }

        /* Social Buttons */
        .social-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 35px;
        }

        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            background: var(--white);
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .social-btn img {
            width: 20px;
            height: 20px;
        }

        .social-btn:hover {
            border-color: var(--primary-blue);
            background: var(--light-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        }

        /* Footer Link */
        .footer-link {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .footer-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 800;
            margin-left: 5px;
            transition: 0.2s;
        }

        .footer-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Error Alert */
        .error-alert {
            background: #FEF2F2;
            color: #EF4444;
            padding: 15px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #FEE2E2;
            animation: shake 0.5s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }
    </style>
</head>

<body>

    <div class="login-card">
        <a href="index.php" class="brand-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>MentorHub</span>
        </a>

        <h1>Welcome Back</h1>
        <p class="subtitle">Enter your credentials to access your portal</p>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control-custom" placeholder="name@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-premium">Sign In</button>
        </form>

        <div class="divider">
            <span>Or continue with</span>
        </div>

        <div class="social-grid">
            <a href="#" class="social-btn">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google">
                Google
            </a>
            <a href="#" class="social-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png"
                    alt="Facebook">
                Facebook
            </a>
        </div>

        <div class="footer-link">
            Looking for administration?
            <a href="admin/login.php">Management Portal</a>
        </div>
    </div>

</body>

</html>
