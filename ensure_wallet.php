<?php
require_once 'includes/db.php';

try {
    echo "Checking database schema...\n";

    // 1. Ensure system_wallet table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_wallet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Checked table: system_wallet\n";

    // 2. Ensure basic system wallet entry exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_wallet WHERE id = 1");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_wallet (id, balance) VALUES (1, 0.00)");
        echo "Created initial system wallet (ID: 1)\n";
    }

    // 3. Ensure teaching_balance column in users table
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('teaching_balance', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN teaching_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER balance");
        echo "Added column: teaching_balance to users table\n";
    } else {
        echo "Column exists: teaching_balance in users table\n";
    }
    
    // 4. Ensure mentor_paid column in appointments table
    $stmt = $pdo->query("DESCRIBE appointments");
    $apptColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('mentor_paid', $apptColumns)) {
       $pdo->exec("ALTER TABLE appointments ADD COLUMN mentor_paid TINYINT(1) DEFAULT 0 AFTER status");
       echo "Added column: mentor_paid to appointments table\n";
    } else {
       echo "Column exists: mentor_paid in appointments table\n";
    }

    echo "Schema check complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
