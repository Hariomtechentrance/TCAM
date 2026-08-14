<?php
/**
 * Shared coach self-registration logic: username is the coach's name,
 * password is the coach's mobile number. Both entry points (the public
 * modal on index.html and the coach-login.php register tab) use this so
 * the credential rule stays identical everywhere a coach can sign up.
 */

function createCoachAccount(PDO $db, string $name, string $mobile, string $district): array
{
    $name = trim($name);
    $mobile = trim($mobile);
    $district = trim($district);

    if ($name === '' || $mobile === '' || $district === '') {
        return ['ok' => false, 'error' => 'Name, mobile and district are required.'];
    }
    if (strlen($mobile) < 6) {
        return ['ok' => false, 'error' => 'Mobile number looks too short to use as a password.'];
    }

    $db->exec("CREATE TABLE IF NOT EXISTS coach_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        district TEXT NOT NULL,
        mobile TEXT DEFAULT '',
        name TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Username is the coach's name; if that name is already taken, append
    // a numeric suffix so the account can still be created.
    $username = $name;
    $stmt = $db->prepare('SELECT COUNT(*) FROM coach_users WHERE username = ?');
    $suffix = 1;
    while (true) {
        $stmt->execute([$username]);
        if ((int)$stmt->fetchColumn() === 0) {
            break;
        }
        $suffix++;
        $username = $name . ' ' . $suffix;
    }

    $hash = password_hash($mobile, PASSWORD_DEFAULT);
    $ins = $db->prepare('INSERT INTO coach_users (username, password_hash, district, mobile, name) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$username, $hash, $district, $mobile, $name]);

    return ['ok' => true, 'username' => $username];
}
