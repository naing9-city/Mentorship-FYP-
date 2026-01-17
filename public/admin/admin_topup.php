<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Fetch current admin balance
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin_balance = $stmt->fetchColumn();

$topup_error = '';
$topup_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $topup_error = "Amount must be greater than 0.";
    } else {
        require_once __DIR__ . '/../../includes/config.php';
        require_once __DIR__ . '/../../vendor/autoload.php';

        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Balance Top-up',
                        ],
                        'unit_amount' => $amount * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => SITE_URL . '/public/admin/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => SITE_URL . '/public/admin/stripe_cancel.php',
                'metadata' => [
                    'admin_id' => $admin_id,
                    'amount' => $amount
                ]
            ]);

            header("HTTP/1.1 303 See Other");
            header("Location: " . $checkout_session->url);
            exit;
        } catch (Exception $e) {
            $topup_error = "Stripe Error: " . $e->getMessage();
        }
    }
}

// --- LAYOUT INCLUSION ---
$page_title = "Refill Balance - Admin";
$extra_css = '
<style>
    .refill-card {
        max-width: 500px; margin: 0 auto; background: white; border-radius: 24px; padding: 40px; box-shadow: var(--shadow-md); border: none;
    }
    .balance-badge {
        background: var(--primary-light); color: var(--primary); padding: 15px 25px; border-radius: 16px; font-weight: 800; font-size: 24px;
        display: inline-flex; align-items: baseline; gap: 8px; margin-bottom: 30px;
    }
</style>
';
include 'layout_header.php';
?>

    <div class="mb-5">
        <a href="topup.php" class="btn-premium btn-p-secondary text-decoration-none">
            <i class="fas fa-arrow-left me-2"></i> Back to Management
        </a>
    </div>

    <div class="text-center mb-5">
        <h1 class="fw-800 fs-3 mb-2">Refill Admin Balance</h1>
        <p class="text-secondary fw-500">Add funds to your account via Stripe Secure Checkout</p>
    </div>

    <div class="refill-card text-center">
        <div class="small fw-700 text-secondary mb-2 text-uppercase">Current Balance</div>
        <div class="balance-badge shadow-sm">
            <span class="fs-5 opacity-75">$</span>
            <span><?= number_format($admin_balance, 2) ?></span>
        </div>

        <?php if ($topup_error): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 text-start">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $topup_error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="text-start mb-4">
                <label class="form-label fw-700 small mb-2">Top-up Amount (USD)</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:var(--bg); border-radius:12px 0 0 12px; padding: 12px 20px;">$</span>
                    <input type="number" step="0.01" name="amount" class="form-control border-0" style="background:var(--bg); border-radius:0 12px 12px 0; padding: 12px 20px;" placeholder="0.00" required>
                </div>
            </div>

            <button type="submit" class="btn-premium btn-p-primary w-100 justify-content-center py-3 fs-6">
                <i class="fab fa-stripe me-2"></i> Proceed to Stripe
            </button>
        </form>

        <p class="text-muted small mt-4 mb-0">
            <i class="fas fa-lock me-1"></i> Payments are processed securely via Stripe. EduNexus does not store your card information.
        </p>
    </div>

<?php include 'layout_footer.php'; ?>
