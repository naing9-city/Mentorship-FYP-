<?php
$fixes = [
    'public/student/index.php' => [
        'old' => '<?= htmlspecialchars(substr($user_name, 0, 1)) ?>',
        'new' => '<?= htmlspecialchars($user_name) ?>'
    ],
    'public/admin/layout_header.php' => [
        'old' => '<?= htmlspecialchars(substr($admin_name, 0, 1)) ?>',
        'new' => '<?= htmlspecialchars($admin_name) ?>'
    ]
];

foreach ($fixes as $file => $pair) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $pair['old']) !== false) {
            $new_content = str_replace($pair['old'], $pair['new'], $content);
            file_put_contents($file, $new_content);
            echo "Updated $file\n";
        } else {
            echo "Old snippet not found in $file. Checking for variations...\n";
            // Check for variations with different spacing
            $escaped_old = preg_quote($pair['old'], '/');
            $pattern = str_replace(' ', '\s*', $escaped_old);
            $pattern = str_replace('\(', '\(\s*', $pattern);
            $pattern = str_replace('\)', '\s*\)', $pattern);
            $pattern = str_replace(',', '\s*,\s*', $pattern);
            
            if (preg_match('/' . $pattern . '/', $content, $matches)) {
                echo "Found variation: " . $matches[0] . "\n";
                $new_content = preg_replace('/' . $pattern . '/', $pair['new'], $content);
                file_put_contents($file, $new_content);
                echo "Updated $file using regex\n";
            } else {
                echo "No variation found in $file either.\n";
            }
        }
    }
}
?>
