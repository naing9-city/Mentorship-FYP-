<?php
ob_start();
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../index.php");
    exit;
}

$mentor_id = $_SESSION['user_id'];

// Fetch mentor balance and their admin
$stmt = $pdo->prepare("SELECT balance, created_by FROM users WHERE id = ?");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch();
$balance = $mentor['balance'];
$admin_id = $mentor['created_by'];

// List of Malaysian Banks
$malaysian_banks = [
    "Affin Bank",
    "Alliance Bank",
    "AmBank",
    "Bank Islam Malaysia",
    "Bank Muamalat Malaysia",
    "Bank Rakyat",
    "CIMB Bank",
    "Citibank Malaysia",
    "Hong Leong Bank",
    "HSBC Bank Malaysia",
    "Maybank (Malayan Banking)",
    "MBSB Bank",
    "OCBC Bank Malaysia",
    "Public Bank",
    "RHB Bank",
    "Standard Chartered Malaysia",
    "UOB Malaysia",
    "Agrobank"
];
sort($malaysian_banks);

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    
    $acc_holder = trim($_POST['acc_holder'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $acc_number = trim($_POST['acc_number'] ?? '');
    
    if ($amount <= 0) {
        $message = "Please enter a valid amount.";
    } elseif ($amount > $balance) {
        $message = "Insufficient balance. You only have $balance points.";
    } elseif (empty($acc_holder) || empty($bank_name) || empty($acc_number)) {
        $message = "Please fill in all bank details.";
    } else {
        // Structure the details as JSON for cleaner processing
        $details_json = json_encode([
            'account_holder' => $acc_holder,
            'bank_name' => $bank_name,
            'account_number' => $acc_number
        ]);

        $pdo->beginTransaction();
        try {
            // Check if this token was already used (prevent double-submit)
            if (isset($_POST['submit_token']) && isset($_SESSION['last_submit_token']) && $_POST['submit_token'] === $_SESSION['last_submit_token']) {
                header("Location: withdraw.php?success=submitted");
                exit;
            }

            // Deduct balance immediately
            $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $mentor_id]);
            
            // Create request
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (mentor_id, admin_id, amount, payment_details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$mentor_id, $admin_id, $amount, $details_json]);
            
            // Save token to session
            if (isset($_POST['submit_token'])) {
                $_SESSION['last_submit_token'] = $_POST['submit_token'];
            }
            
            $pdo->commit();
            header("Location: withdraw.php?success=submitted");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Request failed: " . $e->getMessage();
        }
    }
}

// Success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'submitted') {
    $message = "Withdrawal request submitted! Admin will process it soon.";
    $success = true;
}

// Fetch history
$stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE mentor_id = ? ORDER BY created_at DESC");
$stmt->execute([$mentor_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Points - MentorHub</title>
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

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 30px 40px; flex: 1; }

        /* Top Bar */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .top-icons {
            display: flex; align-items: center; gap: 20px; background: var(--card-bg);
            padding: 8px 15px; border-radius: 30px; box-shadow: var(--shadow-md);
        }
        .icon-btn { color: var(--secondary); font-size: 18px; cursor: pointer; }
        .user-avatar { width: 38px; height: 38px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; text-transform: uppercase; }

        /* Panels */
        .panel { background: var(--card-bg); border-radius: 25px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px; }
        .panel-title { font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 25px; }

        /* Withdrawal Grid */
        .withdraw-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }

        /* Balance Card */
        .balance-card {
            background: linear-gradient(135deg, var(--primary), #7033FF);
            border-radius: 25px; padding: 40px 30px; color: white; text-align: center;
            position: relative; overflow: hidden;
        }
        .balance-card::after {
            content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px;
            background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .balance-card .label { font-size: 14px; font-weight: 700; opacity: 0.8; margin-bottom: 10px; text-transform: uppercase; }
        .balance-card .amount { font-size: 36px; font-weight: 800; margin-bottom: 5px; }
        .balance-card .sub { font-size: 12px; font-weight: 600; opacity: 0.7; }

        /* Form Styling */
        .form-label { font-weight: 700; color: var(--dark); font-size: 14px; margin-bottom: 10px; }
        .form-control, .form-select {
            border-radius: 15px; padding: 15px 20px; border: 2px solid #f1f4ff;
            background: #f8fafc; font-weight: 600; color: var(--dark); transition: 0.3s;
        }
        .form-control:focus, .form-select:focus {
            background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.05);
        }

        .btn-premium {
            background: var(--primary); color: white; border: none; padding: 15px;
            border-radius: 18px; font-weight: 800; font-size: 16px; width: 100%;
            margin-top: 20px; box-shadow: 0 10px 20px rgba(67, 24, 255, 0.2); transition: 0.3s;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(67, 24, 255, 0.3); }

        /* Table Styling */
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .premium-table th { color: var(--secondary); font-weight: 700; font-size: 13px; text-transform: uppercase; padding: 10px 20px; border: none; }
        .premium-table tr { background: #f8fafc; border-radius: 12px; transition: 0.3s; }
        .premium-table tr:hover { background: #f1f4ff; }
        .premium-table td { padding: 20px; vertical-align: middle; border: none; color: var(--dark); font-weight: 600; font-size: 14px; }
        .premium-table td:first-child { border-radius: 15px 0 0 15px; }
        .premium-table td:last-child { border-radius: 0 15px 15px 0; }

        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending { background: #FFF4E6; color: #FFB81C; }
        .status-approved { background: #E6FAF5; color: #05CD99; }
        .status-rejected { background: #FDEEEE; color: #EE5D50; }

        .btn-proof {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
            background: white; border: 2px solid #f1f4ff; border-radius: 12px;
            color: var(--primary); font-weight: 700; font-size: 12px; text-decoration: none; transition: 0.2s;
        }
        .btn-proof:hover { background: var(--primary); color: white; border-color: var(--primary); }

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
        <a href="withdraw.php" class="nav-item active">
            <i class="fas fa-hand-holding-usd"></i> Withdrawals
        </a>
        <a href="schedule.php" class="nav-item">
            <i class="far fa-calendar-alt"></i> Schedule
        </a>
        <a href="chat.php" class="nav-item">
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

<div class="main-content">
    <div class="topbar">
        <h4>Points <span class="fw-800">Withdrawal</span></h4>
        <div class="top-icons">
            <i class="far fa-bell icon-btn"></i>
            <div class="user-avatar"><?= substr($mentor['name'], 0, 1) ?></div>
        </div>
    </div>

    <div class="withdraw-grid">
        <div class="withdraw-sidebar">
            <!-- Balance Card -->
            <div class="balance-card mb-4">
                <div class="label">Current Balance</div>
                <div class="amount"><?= number_format($balance, 2) ?></div>
                <div class="sub">Available points for payout</div>
            </div>

            <div class="panel p-4" style="background: var(--primary-light);">
                <h6 class="fw-800 text-primary mb-3"><i class="fas fa-info-circle me-2"></i> Payout Policy</h6>
                <ul class="text-secondary small fw-600 ps-3 mb-0">
                    <li>Withdrawals are processed within 3-5 business days.</li>
                    <li>Ensure bank details are exactly as per IC.</li>
                    <li>Minimum withdrawal: $10.00</li>
                </ul>
            </div>
        </div>

        <div class="withdraw-main">
            <div class="panel">
                <h5 class="panel-title">Request New Payout</h5>
                
                <?php if ($message): ?>
                    <div class="alert border-0 rounded-4 shadow-sm py-3 fw-800 mb-4" style="background: <?= $success ? '#E6FAF5; color:#05CD99;' : '#FDEEEE; color:#EE5D50;' ?>">
                        <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="submit_token" value="<?= uniqid() ?>">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Withdrawal Amount ($)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Holder Name</label>
                            <input type="text" name="acc_holder" class="form-control" placeholder="Full name as per IC" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <select name="bank_name" class="form-select" required>
                                <option value="" selected disabled>Select Bank</option>
                                <?php foreach($malaysian_banks as $bank): ?>
                                    <option value="<?= $bank ?>"><?= $bank ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="acc_number" class="form-control" placeholder="e.g. 1234567890" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-premium">Submit Withdrawal Request</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History Panel -->
    <div class="panel mt-4">
        <h5 class="panel-title">Withdrawal History</h5>
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Date Requested</th>
                        <th>Amount</th>
                        <th>Bank Details</th>
                        <th>Status</th>
                        <th>Payment Proof</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $r): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                        <td class="text-primary fw-800">$<?= number_format($r['amount'], 2) ?></td>
                        <td>
                            <?php 
                                $details = json_decode($r['payment_details'], true);
                                if ($details): ?>
                                    <div style="line-height: 1.3;">
                                        <div class="fw-800" style="font-size: 14px;"><?= htmlspecialchars($details['bank_name']) ?></div>
                                        <div class="text-secondary fw-700" style="font-size: 11px;">
                                            <?= htmlspecialchars($details['account_number']) ?><br>
                                            <span class="opacity-70"><?= htmlspecialchars($details['account_holder']) ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary"><?= htmlspecialchars($r['payment_details']) ?></span>
                                <?php endif; 
                            ?>
                        </td>
                        <td>
                            <?php if($r['status'] == 'pending'): ?>
                                <span class="status-badge status-pending"><i class="fas fa-hourglass-half"></i> Pending</span>
                            <?php elseif($r['status'] == 'approved'): ?>
                                <span class="status-badge status-approved"><i class="fas fa-check-circle"></i> Paid</span>
                            <?php else: ?>
                                <span class="status-badge status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>
                                <?php if (!empty($r['rejection_reason'])): ?>
                                    <div class="small fw-700 text-danger mt-1" style="font-size: 10px;">
                                        <?= htmlspecialchars($r['rejection_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['admin_proof']): ?>
                                <a href="../../uploads/<?= $r['admin_proof'] ?>" target="_blank" class="btn-proof">
                                    <i class="fas fa-file-invoice"></i> View Receipt
                                </a>
                            <?php else: ?>
                                <span class="text-secondary fw-700 opacity-50 small">Not Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($requests)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-secondary fw-700">No withdrawal history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


