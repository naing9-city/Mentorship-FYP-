<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$session_id = $_GET['session_id'] ?? '';

if (!$session_id) {
    header("Location: admin_topup.php");
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    if ($session->payment_status === 'paid') {
        $admin_id = $session->metadata->admin_id;
        $amount = $session->metadata->amount;

        // Check if this session has already been processed to prevent double top-up
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE stripe_session_id = ?");
        $stmt->execute([$session_id]);
        if (!$stmt->fetch()) {
            // Update admin balance
            $update = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update->execute([$amount, $admin_id]);

            // Log transaction
            $log = $pdo->prepare("
                INSERT INTO transactions (admin_id, type, amount, stripe_session_id)
                VALUES (?, 'topup', ?, ?)
            ");
            $log->execute([$admin_id, $amount, $session_id]);

            $success_msg = "Successfully topped up " . number_format($amount, 2) . " points via Stripe.";
        } else {
            $success_msg = "This payment has already been processed.";
        }
    } else {
        $error_msg = "Payment was not successful.";
    }
} catch (Exception $e) {
    $error_msg = "Error verifying payment: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 text-center">
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <h3>Success!</h3>
            <p><?= $success_msg ?></p>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <h3>Error</h3>
            <p><?= $error_msg ?></p>
        </div>
    <?php endif; ?>
    <a href="admin_topup.php" class="btn btn-primary">Back to Top Up</a>
</div>
</body>
</html>
