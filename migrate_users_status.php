<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add status column with default 'active'
    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active' AFTER role");
    echo "Column 'status' added successfully to users table.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'status' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
