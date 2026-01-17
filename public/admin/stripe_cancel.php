<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 text-center">
    <div class="alert alert-warning">
        <h3>Payment Cancelled</h3>
        <p>The top-up process was cancelled. No charges were made.</p>
    </div>
    <a href="admin_topup.php" class="btn btn-primary">Back to Top Up</a>
</div>
</body>
</html>
