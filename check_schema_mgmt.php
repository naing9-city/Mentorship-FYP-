<?php
require 'includes/db.php';
$tables = ['appointments', 'users', 'transactions'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("DESC $t");
        echo "\nTable: $t\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error describe table $t: " . $e->getMessage() . "\n";
    }
}
?>
