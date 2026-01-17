<?php
require_once '../../includes/db.php';
try {
    $columns = $pdo->query("DESCRIBE ratings")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
