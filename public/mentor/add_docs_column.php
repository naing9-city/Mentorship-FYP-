<?php
require_once '../../includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN mentor_documents TEXT AFTER bio");
    echo "Added mentor_documents column to users table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
