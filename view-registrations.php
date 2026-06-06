<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}
// Simple viewer for all registrations in the SQLite database
$db = new SQLite3('tcam_bookings.db');
$res = $db->query('SELECT * FROM bookings ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html>
<head>
    <title>TCAM Registrations</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; color: #222; }
        table { border-collapse: collapse; width: 98%; margin: 30px auto; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; }
        th { background: #764ba2; color: #fff; }
        tr:nth-child(even) { background: #f3f3f3; }
        h1 { text-align: center; color: #764ba2; }
    </style>
</head>
<body>
    <h1>TCAM Registration List</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>TCAM ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>DOB</th>
            <th>District</th>
            <th>Date</th>
            <th>Time</th>
            <th>Players</th>
            <th>Venue</th>
            <th>Message</th>
            <th>Created At</th>
        </tr>
        <?php while($row = $res->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['bookingId']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo htmlspecialchars($row['dob']); ?></td>
            <td><?php echo htmlspecialchars($row['district']); ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td><?php echo htmlspecialchars($row['time']); ?></td>
            <td><?php echo htmlspecialchars($row['players']); ?></td>
            <td><?php echo htmlspecialchars($row['venue']); ?></td>
            <td><?php echo htmlspecialchars($row['message']); ?></td>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
