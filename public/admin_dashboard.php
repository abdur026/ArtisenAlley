<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch all users from the database
$query = "SELECT id, name, email, role FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a> | 
        <a href="logout.php">Logout</a>
    </header>
    <h1>Admin Dashboard</h1>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<p style='color:green'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    ?>
    <h2>Manage Users</h2>
    <table border="1" cellspacing="0" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <!-- Enable/Disable Actions; for example, a link to an action page (e.g., toggle_user.php) -->
                            <?php if ($user['role'] !== 'admin'): ?>
                                <a href="toggle_user.php?id=<?php echo $user['id']; ?>">
                                    <?php echo ($user['role'] === 'disabled' ? 'Enable' : 'Disable'); ?>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
