<?php
$files_to_fix = [
    'public/student/index.php' => '$user_name',
    'public/admin/layout_header.php' => '$admin_name',
    'public/mentor/index.php' => '$mentor[\'name\']', // Note the structure here
    'public/super_admin/system_dashboard.php' => '$super_admin_name'
];

foreach ($files_to_fix as $file => $var) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Pattern 1: htmlspecialchars(substr($var, 0, 1))
    $content = preg_replace('/htmlspecialchars\(\s*substr\(\s*' . preg_quote($var, '/') . '\s*,\s*0\s*,\s*1\s*\)\s*\)/', 'htmlspecialchars(' . $var . ')', $content);
    
    // Pattern 2: substr($var, 0, 1) (without htmlspecialchars)
    // Only apply if it looks like it's in a greeting context
    $content = preg_replace('/(\$greeting.*?),\s*<span.*?>\s*<\?=\s*substr\(\s*' . preg_quote($var, '/') . '\s*,\s*0\s*,\s*1\s*\)\s*\?>/s', '$1, <span style="background: linear-gradient(135deg, var(--dark), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?= htmlspecialchars(' . $var . ') ?>', $content);

    // Specific fix for mentor/index.php if it was explode based
    if ($file === 'public/mentor/index.php') {
        $content = str_replace("explode(' ', \$mentor['name'])[0]", "\$mentor['name']", $content);
    }

    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "Successfully updated $file\n";
    } else {
        echo "No changes needed for $file (already correct or pattern not found)\n";
    }
}
?>
