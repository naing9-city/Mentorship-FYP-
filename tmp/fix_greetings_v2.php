<?php
$files = [
    'public/student/index.php' => '$user_name',
    'public/admin/layout_header.php' => '$admin_name'
];

foreach ($files as $file => $var_name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Resilient regex to find htmlspecialchars(substr($var, 0, 1)) with any spacing
        $pattern = '/htmlspecialchars\(\s*substr\(\s*' . preg_quote($var_name, '/') . '\s*,\s*0\s*,\s*1\s*\)\s*\)/';
        $replacement = 'htmlspecialchars(' . $var_name . ')';
        
        if (preg_match($pattern, $content)) {
            $new_content = preg_replace($pattern, $replacement, $content);
            file_put_contents($file, $new_content);
            echo "Updated $file\n";
        } else {
            echo "Pattern not found in $file. Content near greeting:\n";
            // Find where 'greeting' is to help debug
            if (preg_match('/\$greeting.*?<\//s', $content, $matches)) {
                echo "Found: " . htmlspecialchars($matches[0]) . "\n";
            }
        }
    } else {
        echo "File $file not found\n";
    }
}
?>
