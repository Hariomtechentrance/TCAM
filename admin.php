<?php
// TCAM Admin Panel - Production Ready
require_once 'config.php';

// Simple authentication check
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === ADMIN_USERNAME && password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $error = 'Invalid credentials';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        // Show login form
        echo '<!DOCTYPE html><html><head><title>TCAM Admin Login</title><style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;background:#f5f5f5;margin:0}.login-form{background:white;padding:2rem;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.1);width:300px}input{width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px}button{width:100%;padding:12px;background:#764ba2;color:white;border:none;border-radius:5px;cursor:pointer}button:hover{background:#5a3a7a}.error{color:red;margin:10px 0}</style></head><body><div class="login-form"><h2>TCAM Admin Login</h2>';
        if (isset($error)) echo '<div class="error">' . $error . '</div>';
        echo '<form method="post"><input type="text" name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" required><button type="submit">Login</button></form></div></body></html>';
        exit;
    }
}

$db = new SQLite3(DB_PATH);
$results = $db->query('SELECT * FROM bookings ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.08);}
        h2 { color: #764ba2; text-align: center; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border: 1px solid #e0e0e0; padding: 10px 8px; text-align: left; }
        th { background: #f3f3f3; }
        tr:nth-child(even) { background: #fafafa; }
        .photo-thumb { width: 60px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .download-link { color: #007bff; text-decoration: underline; cursor: pointer; }
        .download-link:hover { color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>TCAM Registrations Admin Panel</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>District</th>
                    <th>Proof Type</th>
                    <th>Proof Doc</th>
                    <th>Photo</th>
                    <th>Message</th>
                    <th>Registered At</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['bookingId']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['district']); ?></td>
                    <td><?php echo htmlspecialchars($row['proof']); ?></td>
                    <td>
                        <?php if ($row['proof_file']): ?>
                        <a href="<?php echo htmlspecialchars($row['proof_file']); ?>" target="_blank" class="download-link">View</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['photo']): ?>
                        <img src="<?php echo htmlspecialchars($row['photo']); ?>" class="photo-thumb" alt="Photo">
                        <?php endif; ?>
                    </td>
                    <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="registration-success.html?bookingId=<?php echo urlencode($row['bookingId']); ?>&name=<?php echo urlencode($row['name']); ?>&district=<?php echo urlencode($row['district']); ?>&phone=<?php echo urlencode($row['phone']); ?>&photo=<?php echo urlencode($row['photo']); ?>" target="_blank" class="download-link">ID Card</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
