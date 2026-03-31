<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Prevent duplicate submissions with a CSRF-like token
if (!isset($_SESSION['topup_token'])) {
    $_SESSION['topup_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

// Get student's admin_id
$stmt = $pdo->prepare("SELECT created_by FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin_id = $stmt->fetchColumn();

// Fetch Admin's QR Code
$stmt = $pdo->prepare("SELECT qr_code FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_qr = $stmt->fetchColumn();

// Handle Top-up Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_topup'])) {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['topup_token']) {
        $message = "Invalid session token. Please try again.";
        $message_type = "danger";
    } else {
        $amount = (float) $_POST['amount'];
        $bank_info = [
            'account_holder' => $_POST['account_holder'],
            'bank_name' => $_POST['bank_name'],
            'account_number' => $_POST['account_number']
        ];

        $proof_filename = '';
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
            $proof_filename = 'topup_' . time() . '_' . $user_id . '.' . $ext;
            move_uploaded_file($_FILES['proof']['tmp_name'], '../uploads/' . $proof_filename);
        }

        if ($amount > 0 && !empty($proof_filename)) {
            $stmt = $pdo->prepare("INSERT INTO topup_requests (student_id, admin_id, amount, proof_image, bank_info, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $admin_id, $amount, $proof_filename, json_encode($bank_info)]);

            // Regerate token to prevent resubmission
            $_SESSION['topup_token'] = bin2hex(random_bytes(32));

            header("Location: wallet.php?success=1");
            exit;
        } else {
            $message = "Amount and proof of payment are required.";
            $message_type = "danger";
        }
    }
}

if (isset($_GET['success'])) {
    $message = "Top-up request submitted successfully! Pending admin approval.";
    $message_type = "success";
}

// Fetch User Balance & Mentor Status
$stmt = $pdo->prepare("SELECT name, balance, mentor_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Top-up History
$stmt = $pdo->prepare("SELECT * FROM topup_requests WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - MentorHub</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --sidebar-width: 290px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F4F7FE 0%, #E9EDF7 100%);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            display: flex;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 15px;
            margin-bottom: 50px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .brand-logo:hover {
            transform: scale(1.02);
        }

        .brand-logo i {
            font-size: 28px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .section-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 25px 15px 10px;
            opacity: 0.8;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 700;
            border-radius: 16px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .nav-item.active {
            background: var(--light-bg);
            color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.05);
        }

        .nav-item:hover:not(.active) {
            background: #F8FAFC;
            color: var(--text-dark);
            transform: translateX(5px);
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

        /* Main Content Styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            flex: 1;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .search-box {
            background: var(--white);
            border-radius: 20px;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 400px;
            box-shadow: 0 10px 30px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .search-box input {
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
            gap: 15px;
            background: var(--white);
            padding: 10px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .icon-btn {
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: 0.2s;
        }

        .icon-btn:hover {
            color: var(--primary-blue);
            transform: scale(1.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(67, 24, 255, 0.2);
        }

        /* Wallet Specific Styles */
        .balance-card-premium {
            background: linear-gradient(135deg, #05cd99 0%, #00a38c 100%);
            border-radius: 24px;
            padding: 40px;
            color: white;
            box-shadow: 0px 20px 50px rgba(5, 205, 153, 0.15);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .balance-card-premium .lbl {
            font-size: 14px;
            font-weight: 700;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .balance-card-premium .val {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .balance-card-premium i.bg-icon {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 150px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .panel {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(112, 144, 176, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
            margin-bottom: 30px;
        }

        .panel-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control,
        .form-select {
            border-radius: 16px;
            padding: 14px 20px;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05);
            background: var(--white);
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            color: var(--white);
            border: none;
            padding: 16px;
            border-radius: 18px;
            font-weight: 800;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(67, 24, 255, 0.15);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 24, 255, 0.25);
            color: var(--white);
        }

        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .premium-table th {
            font-size: 12px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            padding: 0 20px 10px;
            letter-spacing: 1px;
        }

        .premium-table td {
            background: #F8FAFC;
            padding: 20px;
            font-size: 15px;
            font-weight: 700;
            vertical-align: middle;
            border: 1px solid rgba(226, 232, 240, 0.3);
        }

        .premium-table td:first-child {
            border-radius: 20px 0 0 20px;
            border-right: none;
        }

        .premium-table td:last-child {
            border-radius: 0 20px 20px 0;
            border-left: none;
        }

        .premium-table td:not(:first-child):not(:last-child) {
            border-left: none;
            border-right: none;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #FFF9E6;
            color: var(--warning);
        }

        .status-approved {
            background: #E6FFFB;
            color: #05CD99;
        }

        .status-rejected {
            background: #FFF1F0;
            color: var(--danger);
        }

        .qr-display {
            border: 2px dashed #E2E8F0;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            background: var(--white);
            transition: all 0.3s;
        }

        .qr-display:hover {
            border-color: var(--primary-blue);
        }

        .qr-display img {
            max-width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .btn-view-receipt {
            background: var(--light-bg);
            color: var(--primary-blue);
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            transition: all 0.2s;
        }

        .btn-view-receipt:hover {
            background: var(--primary-blue);
            color: var(--white);
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="brand-logo">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div class="brand-title">MentorHub</div>
                <div
                    style="font-size: 11px; color: var(--text-muted); font-weight: 800; margin-top: -4px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                    Student Portal</div>
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
            <a href="learn.php" class="nav-item">
                <i class="fas fa-book-open"></i> Learning Feed
            </a>

            <div class="section-label">Finance</div>
            <a href="wallet.php" class="nav-item active">
                <i class="fas fa-wallet"></i> My Wallet
            </a>

            <?php if ($user['mentor_status'] === 'approved'): ?>
                <div class="section-label">Mentor Mode</div>
                <a href="../mentor/index.php" class="nav-item" style="color: var(--primary-blue);">
                    <i class="fas fa-exchange-alt"></i> Switch to Mentor
                </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Bar -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search transactions, mentors...">
            </div>
            <div class="top-icons">
                <i class="far fa-bell icon-btn"></i>
                <i class="far fa-moon icon-btn"></i>
                <div class="user-avatar"><?= substr($_SESSION['user_id'], 0, 1) ?></div>
            </div>
        </div>

        <div class="page-header mb-4">
            <h1 class="fw-800" style="letter-spacing: -1px;">My Wallet</h1>
            <p class="text-muted fw-600">Manage your points and top-up requests.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> rounded-4 border-0 shadow-sm mb-4 p-3 fw-700">
                <i class="fas fa-info-circle me-2"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Balance Card -->
            <div class="col-lg-12">
                <div class="balance-card-premium">
                    <div class="lbl">Total Available Points</div>
                    <div class="val"><?= number_format($user['balance'], 2) ?> Points</div>
                    <i class="fas fa-wallet bg-icon"></i>
                </div>
            </div>

            <!-- Top-up Form -->
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-title"><i class="fas fa-plus-circle text-primary-blue"></i> Request Top-up</div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= $_SESSION['topup_token'] ?>">

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Top-up Amount (Points)</label>
                                <input type="number" name="amount" class="form-control" placeholder="0.00" required
                                    min="1" step="0.1">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" name="account_holder" class="form-control"
                                    placeholder="Name on account" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Select Bank</label>
                                <select name="bank_name" class="form-select" required>
                                    <option value="">Choose your bank</option>
                                    <option value="Maybank">Maybank</option>
                                    <option value="CIMB Bank">CIMB Bank</option>
                                    <option value="Public Bank">Public Bank</option>
                                    <option value="RHB Bank">RHB Bank</option>
                                    <option value="Hong Leong Bank">Hong Leong Bank</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control"
                                    placeholder="Account Number" required>
                            </div>
                            <div class="col-md-12 mb-4">
                                <label class="form-label">Upload Payment Receipt</label>
                                <input type="file" name="proof" class="form-control" required accept="image/*">
                            </div>
                        </div>

                        <button type="submit" name="request_topup" class="btn-premium w-100">Submit Request</button>
                    </form>
                </div>
            </div>

            <!-- Admin QR Display -->
            <div class="col-lg-4">
                <div class="panel">
                    <div class="panel-title"><i class="fas fa-qrcode text-primary-blue"></i> Admin QR Code</div>
                    <div class="qr-display mb-3">
                        <?php
                        $qr_path = $admin_qr ?: 'qrcodes/admin4.jpeg';
                        if (strpos($qr_path, 'qrcodes/') === false) {
                            $qr_path = 'qrcodes/' . $qr_path;
                        }
                        ?>
                        <img src="../uploads/<?= $qr_path ?>" alt="Admin QR">
                    </div>
                    <p class="small text-muted text-center fw-600">Scan this QR to make payment to the admin directly
                        via
                        your banking app.</p>
                </div>
            </div>

            <!-- History Table -->
            <div class="col-lg-12">
                <div class="panel">
                    <div class="panel-title"><i class="fas fa-history text-primary-blue"></i> Transaction History</div>
                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Bank Method</th>
                                    <th>Status</th>
                                    <th class="text-end">Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req):
                                    $details = json_decode($req['bank_info'], true); ?>
                                    <tr>
                                        <td><span
                                                class="text-muted small"><?= date('M d, Y', strtotime($req['created_at'])) ?></span>
                                        </td>
                                        <td><span class="text-dark fw-800"><?= number_format($req['amount'], 2) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-800"><?= htmlspecialchars($details['bank_name'] ?? 'Bank') ?>
                                            </div>
                                            <div class="small text-muted fw-600">
                                                <?= htmlspecialchars($details['account_number'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $req['status'] ?>">
                                                <?= $req['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="../uploads/<?= $req['proof_image'] ?>" target="_blank"
                                                class="btn-view-receipt">View Receipt</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($requests))
                                    echo "<tr><td colspan='5' class='text-center py-5 text-muted fw-700'>No transaction history yet.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
