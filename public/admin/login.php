<?php
session_start();
require_once '../../includes/db.php';

$error = '';
$success = '';
$active_panel = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            // Admins are stored in the 'users' table with role 'admin'
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = $user['name'];
                header('Location: users.php');
                exit();
            } else {
                $error = 'Invalid admin credentials';
            }
        }
    } elseif ($action === 'register') {
        $active_panel = 'register';
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $secret_code = $_POST['secret_code'];

        // Correct secret for administrative registration
        $correct_secret = 'ADMIN123'; 

        if (empty($name) || empty($email) || empty($password) || empty($secret_code)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif ($secret_code !== $correct_secret) {
            $error = 'Invalid secret access code';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Insert as 'admin' role
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $success = 'Admin account created successfully! Please login.';
                    $active_panel = 'login';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - MentorHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Premium Palette */
            --primary-blue: #4318FF;
            --primary-midnight: #11047A;
            --accent-purple: #612DFF;
            --light-bg: #F4F7FE;
            --white: #FFFFFF;
            --text-dark: #1B2559;
            --text-muted: #707EAE;
            --text-on-overlay: #FFFFFF; /* High visibility */
            --shadow-premium: 0px 40px 80px rgba(112, 144, 176, 0.15);
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

        /* Animated Logo */
        .mentorhub-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            text-decoration: none;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .mentorhub-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.5s;
        }
        .mentorhub-logo span {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.8px;
            background: linear-gradient(to right, var(--primary-midnight), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .mentorhub-logo:hover {
            transform: translateY(-2px) scale(1.02);
        }
        .mentorhub-logo:hover i {
            transform: rotate(-12deg) scale(1.1);
        }

        .container {
            background-color: var(--white);
            border-radius: 36px;
            box-shadow: var(--shadow-premium);
            position: relative;
            overflow: hidden;
            width: 900px;
            max-width: 95%;
            min-height: 600px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.8s cubic-bezier(0.7, 0, 0.3, 1);
            background: var(--white);
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.right-panel-active .sign-in-container {
            transform: translateX(100%);
            opacity: 0;
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: premiumFadeIn 0.8s forwards;
        }

        @keyframes premiumFadeIn {
            0% { opacity: 0; transform: translateX(80%) scale(0.9); }
            100% { opacity: 1; transform: translateX(100%) scale(1); }
        }

        form {
            padding: 0 50px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.8px;
            color: var(--text-dark);
        }

        p {
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .overlay-panel h1 {
            color: var(--white) !important;
            margin-bottom: 12px;
        }

        .overlay-panel p {
            color: #E9EDF7 !important;
            font-weight: 500;
            opacity: 0.95;
            margin-bottom: 25px;
        }

        .form-control-custom {
            background: var(--light-bg);
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 14px 20px;
            margin: 6px 0;
            width: 100%;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 8px 20px rgba(67, 24, 255, 0.08);
            transform: translateY(-2px);
        }

        .btn-custom {
            margin-top: 15px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, var(--primary-midnight), var(--primary-blue));
            color: var(--white);
            font-size: 15px;
            font-weight: 700;
            padding: 16px 50px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 12px 25px rgba(17, 4, 122, 0.2);
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(17, 4, 122, 0.3);
            filter: brightness(1.1);
        }

        .btn-custom:active {
            transform: scale(0.96);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 2px solid var(--white);
            margin-top: 15px;
            box-shadow: none;
        }

        .btn-ghost:hover {
            background: var(--white);
            color: var(--primary-midnight);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.8s cubic-bezier(0.7, 0, 0.3, 1);
            z-index: 100;
        }

        .container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(-135deg, var(--primary-midnight) 0%, var(--primary-blue) 40%, var(--accent-purple) 100%);
            background-size: 200% 200%;
            animation: flowGradient 12s ease infinite;
            color: var(--white);
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.8s cubic-bezier(0.7, 0, 0.3, 1);
        }

        @keyframes flowGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.8s cubic-bezier(0.7, 0, 0.3, 1);
        }

        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }

        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }

        .social-container {
            margin: 15px 0;
            display: flex;
            gap: 12px;
        }

        .social-container a {
            border: 2px solid #E9EDF7;
            border-radius: 16px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            height: 44px;
            width: 48px;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 18px;
            text-decoration: none;
        }

        .social-container a:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background: var(--light-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.06);
        }

        .alert {
            padding: 14px;
            border-radius: 16px;
            width: 100%;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .alert-danger { background: #FFF5F5; color: #E53E3E; border: 1px solid #FED7D7; }
        .alert-success { background: #F0FFF4; color: #38A169; border: 1px solid #C6F6D5; }

        .info-text {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.1px;
            margin-bottom: 5px;
        }

        .forgot-link {
            color: var(--text-muted);
            font-size: 14px;
            text-decoration: none;
            font-weight: 600;
            margin: 12px 0;
            transition: 0.3s;
        }
        .forgot-link:hover {
            color: var(--primary-blue);
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container" id="container">

        <!-- Sign Up Form -->
        <div class="form-container sign-up-container">
            <form action="login.php" method="POST">
                <a href="#" class="mentorhub-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>MentorHuB</span>
                </a>
                <h1>Initialize Account</h1>
                <div class="social-container">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-google"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span class="info-text">Secure Administrative Setup</span>

                <?php if ($active_panel === 'register' && $error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <input type="hidden" name="action" value="register">

                <input type="text" name="name" placeholder="Full Name" class="form-control-custom" required />
                <input type="email" name="email" placeholder="Admin Email" class="form-control-custom" required />
                <input type="password" name="password" placeholder="Access Password" class="form-control-custom" required />
                <input type="password" name="confirm_password" placeholder="Verify Password" class="form-control-custom" required />
                <input type="password" name="secret_code" placeholder="Secret Access Key" class="form-control-custom" required />

                <button class="btn-custom" type="submit">Verify & Create</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in-container">
            <form action="login.php" method="POST">
                <a href="#" class="mentorhub-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>MentorHuB</span>
                </a>
                <h1>Management Portal</h1>
                <div class="social-container">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-google"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span class="info-text">Secure Access for Administrators</span>

                <?php if ($active_panel === 'login' && $error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>

                <input type="hidden" name="action" value="login">

                <input type="email" name="email" placeholder="Admin Email" class="form-control-custom" required />
                <input type="password" name="password" placeholder="Access Password" class="form-control-custom" required />

                <a href="#" class="forgot-link">Trouble signing in?</a>
                <button class="btn-custom" type="submit">Log In</button>
            </form>
        </div>

        <!-- Overlay / Toggle -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Ready to Login?</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="btn-custom btn-ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Admin!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="btn-custom btn-ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        <?php if ($active_panel === 'register') echo "container.classList.add('right-panel-active');"; ?>
    </script>
</body>

</html>