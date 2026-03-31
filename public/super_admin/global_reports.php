<?php
session_start();
require_once '../../includes/db.php';

// Super admin session check
if (!isset($_SESSION['super_admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

$super_admin_name = $_SESSION['name'] ?? 'Super Admin';

// --- FINANCIAL OVERVIEW ---

// Total System Revenue (from system_wallet)
$stmt = $pdo->query("SELECT balance FROM system_wallet WHERE id = 1");
$system_revenue = $stmt->fetchColumn() ?? 0;

// Total Payouts (Approved Withdrawals)
$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawal_requests WHERE status = 'approved'");
$total_payouts = $stmt->fetchColumn() ?? 0;

// Pending Withdrawals
$stmt = $pdo->query("SELECT SUM(amount) FROM withdrawal_requests WHERE status = 'pending'");
$pending_withdrawals = $stmt->fetchColumn() ?? 0;


// --- CHARTS DATA ---

// 1. User Growth (Last 6 Months)
$months = [];
$student_growth = [];
$mentor_growth = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $months[] = $month_label;

    // Students
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$date]);
    $student_growth[] = $stmt->fetchColumn();

    // Mentors
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'mentor' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$date]);
    $mentor_growth[] = $stmt->fetchColumn();
}


// --- LEADERBOARDS ---

// Top Mentors by Balance (Wealthiest)
$stmt = $pdo->query("SELECT name, email, balance, created_at FROM users WHERE role = 'mentor' ORDER BY balance DESC LIMIT 5");
$top_mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Large Transactions (Top-ups > 100)
$stmt = $pdo->query("SELECT t.*, u.name as student_name FROM topup_requests t JOIN users u ON t.student_id = u.id WHERE t.amount >= 100 ORDER BY t.created_at DESC LIMIT 5");
$large_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Global Reports - MentorHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #05CD99;
            --primary-light: #F0FDF4;
            --secondary: #707EAE;
            --dark: #1B2559;
            --success: #05CD99;
            --danger: #EE5D50;
            --warning: #FFB81C;
            --bg: #F0FDF4;
            --card-bg: #FFFFFF;
            --sidebar-width: 290px;
            --shadow-sm: 0px 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0px 18px 40px rgba(5, 205, 153, 0.12);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--card-bg);
            padding: 40px 20px;
            z-index: 1000;
            border-right: 1px solid rgba(5, 205, 153, 0.1);
        }

        .brand {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 50px;
            padding-left: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand i {
            color: var(--primary);
            font-size: 28px;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            padding: 16px 22px;
            margin-bottom: 10px;
            color: var(--secondary);
            border-radius: 12px;
            text-decoration: none;
            transition: 0.2s;
            font-weight: 600;
        }

        .nav-item-custom i {
            font-size: 18px;
            margin-right: 15px;
            width: 25px;
            text-align: center;
        }

        .nav-item-custom:hover,
        .nav-item-custom.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .top-icons {
            display: flex;
            gap: 20px;
            align-items: center;
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: var(--shadow-sm);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: var(--dark);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Report Cards */
        .report-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            border: none;
            box-shadow: var(--shadow-sm);
            height: 100%;
            transition: 0.3s;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-label {
            color: var(--secondary);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-val {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Tables */
        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .premium-table th {
            color: var(--secondary);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px 20px;
        }

        .premium-table td {
            background: var(--card-bg);
            padding: 15px 20px;
            font-weight: 600;
            font-size: 14px;
            vertical-align: middle;
        }

        .premium-table td:first-child {
            border-radius: 12px 0 0 12px;
        }

        .premium-table td:last-child {
            border-radius: 0 12px 12px 0;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="system_dashboard.php" class="brand">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <div>MentorHub</div>
                <div style="font-size: 11px; color: var(--secondary); font-weight: 700; margin-top: -4px;">Super Admin
                </div>
            </div>
        </a>
        <nav>
            <a href="system_dashboard.php" class="nav-item-custom">
                <i class="fas fa-th-large"></i> System Overview
            </a>
            <a href="manage_admins.php" class="nav-item-custom">
                <i class="fas fa-users-cog"></i> Manage Admins
            </a>
            <a href="global_reports.php" class="nav-item-custom active">
                <i class="fas fa-chart-line"></i> Global Reports
            </a>
            <a href="logout.php" class="nav-item-custom text-danger mt-5">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div>
                <div class="text-secondary fw-bold small">Super Admin / <span class="text-dark">Analytics</span></div>
                <h3 class="fw-800 m-0">Global Reports</h3>
            </div>
            <div class="top-icons">
                <span class="fw-bold text-dark me-2"><?= htmlspecialchars($super_admin_name) ?></span>
                <div class="admin-avatar">SA</div>
            </div>
        </div>

        <!-- Financial Cards -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4">
                <div class="report-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-val text-primary">$<?= number_format($system_revenue, 2) ?></div>
                    </div>
                    <div class="stat-icon bg-primary-light text-primary"><i class="fas fa-coins"></i></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Total Withdrawn</div>
                        <div class="stat-val" style="color: #065F46;">$<?= number_format($total_payouts, 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background: #e1fdf0; color: #065F46;"><i
                            class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Pending Req.</div>
                        <div class="stat-val text-warning">$<?= number_format($pending_withdrawals, 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background: #FFF9E6; color: #FFB81C;"><i
                            class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="report-card">
                    <h5 class="fw-800 mb-4">User Growth Analytics (6 Months)</h5>
                    <div class="chart-container">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card">
                    <h5 class="fw-800 mb-4">Top Mentors (Wealth)</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($top_mentors as $tm): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3"
                                style="background: var(--bg);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="admin-avatar"
                                        style="width: 35px; height: 35px; background:white; color:var(--dark); font-size: 12px; border: 2px solid var(--primary-light);">
                                        <?= strtoupper(substr($tm['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold small"><?= htmlspecialchars($tm['name']) ?></div>
                                        <div class="xtra-small text-muted" style="font-size: 11px;">Joined
                                            <?= date('M Y', strtotime($tm['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="fw-800 text-success small">$<?= number_format($tm['balance'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Large Transactions -->
        <div class="report-card">
            <h5 class="fw-800 mb-4">Recent Large Top-ups (>$100)</h5>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Bank Details</th>
                            <th class="text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($large_transactions as $lt):
                            $bank_info = json_decode($lt['bank_info'], true);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($lt['student_name']) ?></div>
                                </td>
                                <td class="fw-800 text-primary">$<?= number_format($lt['amount'], 2) ?></td>
                                <td>
                                    <span
                                        class="badge bg-<?= $lt['status'] === 'approved' ? 'success' : ($lt['status'] === 'pending' ? 'warning' : 'danger') ?> bg-opacity-10 text-<?= $lt['status'] === 'approved' ? 'success' : ($lt['status'] === 'pending' ? 'warning' : 'danger') ?> px-2 py-1 rounded-2 small fw-800 text-uppercase">
                                        <?= $lt['status'] ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($bank_info['bank_name'] ?? 'N/A') ?>
                                    (<?= htmlspecialchars($bank_info['account_holder'] ?? '-') ?>)
                                </td>
                                <td class="text-end text-muted small">
                                    <?= date('M d, Y H:i', strtotime($lt['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($large_transactions))
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No large transactions found recently.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'New Students',
                        data: <?= json_encode($student_growth) ?>,
                        backgroundColor: '#05CD99',
                        borderRadius: 6
                    },
                    {
                        label: 'New Mentors',
                        data: <?= json_encode($mentor_growth) ?>,
                        backgroundColor: '#065F46',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
