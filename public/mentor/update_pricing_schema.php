<?php
require_once '../../includes/db.php';

try {
    // Add hourly_rate column (default to 10.00 as per previous hardcoded value)
    $pdo->exec("ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10, 2) DEFAULT 10.00 AFTER mentor_status");
    echo "Added hourly_rate column.\n";
    
    // Add is_volunteer column (default to 0 / false)
    $pdo->exec("ALTER TABLE users ADD COLUMN is_volunteer TINYINT(1) DEFAULT 0 AFTER hourly_rate");
    echo "Added is_volunteer column.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
