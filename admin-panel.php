<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
// Admin Panel for TCAM Registrations
$host = 'localhost';
$db   = 'your_database_name';
$user = 'your_db_user';
$pass = 'your_db_password';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact the administrator.');
}

$registrations = $pdo->query('SELECT * FROM registrations ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        h1 { text-align: center; margin-top: 30px; color: #7c6ee6; }
        table { border-collapse: collapse; width: 96%; margin: 30px auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.09); border-radius: 16px; overflow: hidden; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #7c6ee6; color: #fff; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        img { width: 70px; height: 85px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7ef; background: #f5f5fa; }
        .id-cell { font-weight: bold; color: #7c6ee6; }
        .mobile-cell { font-family: monospace; }
    </style>
</head>
<body>
    <h1>TCAM Registrations Admin Panel</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>City</th>
            <th>State</th>
            <th>Joined</th>
            <th>Photo</th>
            <th>Registered At</th>
        </tr>
        <?php foreach ($registrations as $reg): ?>
        <tr>
            <td class="id-cell"><?php echo htmlspecialchars($reg['reg_id']); ?></td>
            <td><?php echo htmlspecialchars($reg['name']); ?></td>
            <td class="mobile-cell"><?php echo htmlspecialchars($reg['mobile']); ?></td>
            <td><?php echo htmlspecialchars($reg['city']); ?></td>
            <td><?php echo htmlspecialchars($reg['state']); ?></td>
            <td><?php echo htmlspecialchars($reg['joined']); ?></td>
            <td>
                <?php if ($reg['photo']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($reg['photo']); ?>" alt="Photo">
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($reg['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
