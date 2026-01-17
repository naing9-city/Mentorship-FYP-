<?php
require_once '../../includes/db.php';

try {
    // Drop existing to rebuild cleanly (dev mode) or ALTER
    $pdo->exec("DROP TABLE IF EXISTS mentor_posts");
    
    $sql = "CREATE TABLE mentor_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        content TEXT,
        image_path VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        posts_count INT DEFAULT 0,
        FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'mentor_posts' recreated with image support.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
