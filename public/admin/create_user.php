<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin'); // ensure admin

$admin_id = $_SESSION['user_id']; // current admin ID

// handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // delete ONLY if the user belongs to this admin
    $pdo->prepare("DELETE FROM users WHERE id = ? AND created_by = ?")
        ->execute([$id, $admin_id]);

    header('Location: create_users.php');
    exit;
}

// fetch ONLY students & mentors created by THIS admin
$stmt = $pdo->prepare("
    SELECT id, name, email, role, is_mentor, created_at
    FROM users
    WHERE created_by = ?
      AND role != 'admin'
    ORDER BY created_at DESC
");
$stmt->execute([$admin_id]);
$users = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><title>Users</title></head>
<body>
<h1>Your Students & Mentors</h1>
<a href="create_user.php">Add user</a>

<table border=1>
<tr>
    <th>ID</th><th>Name</th><th>Email</th>
    <th>Role</th><th>Is Mentor</th><th>Actions</th>
</tr>

<?php foreach($users as $u): ?>
<tr>
  <td><?= htmlspecialchars($u['id']) ?></td>
  <td><?= htmlspecialchars($u['name']) ?></td>
  <td><?= htmlspecialchars($u['email']) ?></td>
  <td><?= htmlspecialchars($u['role']) ?></td>
  <td><?= $u['is_mentor'] ? 'Yes' : 'No' ?></td>
  <td>
    <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
  </td>
</tr>
<?php endforeach; ?>

</table>
</body>
</html>
