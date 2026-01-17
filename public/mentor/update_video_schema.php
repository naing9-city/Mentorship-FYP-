<?php
require_once '../../includes/db.php';

try {
    // Add room_id column to appointments table
    $pdo->exec("ALTER TABLE appointments ADD COLUMN room_id VARCHAR(100) NULL");
    echo "Successfully added room_id column to appointments table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column room_id already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
