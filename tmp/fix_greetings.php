<?php
$files = [
    'public/student/index.php',
    'public/admin/layout_header.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $name_var = (strpos($file, 'student') !== false) ? '$user_name' : '$admin_name';
        
        $old = '<?= htmlspecialchars(substr(' . $name_var . ', 0, 1)) ?>';
        $new = '<?= htmlspecialchars(' . $name_var . ') ?>';
        
        if (strpos($content, $old) !== false) {
            $new_content = str_replace($old, $new, $content);
            file_put_contents($file, $new_content);
            echo "Updated $file\n";
        } else {
            echo "Snippet not found in $file\n";
        }
    } else {
        echo "File $file not found\n";
    }
}
?>
