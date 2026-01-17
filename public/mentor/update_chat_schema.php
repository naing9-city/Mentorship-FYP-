<?php
require_once '../../includes/db.php';

try {
    // Add columns to messages table
    $columns = $pdo->query("DESCRIBE messages")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_read', $columns)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        echo "Added is_read to messages.<br>";
    }
    
    if (!in_array('attachment_path', $columns)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL");
        echo "Added attachment_path to messages.<br>";
    }

    // Add column to users table
    $userColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('last_seen', $userColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_seen DATETIME DEFAULT NULL");
        echo "Added last_seen to users.<br>";
    }

    echo "Schema updated successfully.";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
