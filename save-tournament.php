<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament = $_POST['tournamentName'] ?? '';
    $name = $_POST['applicantName'] ?? '';
    $email = $_POST['applicantEmail'] ?? '';
    $phone = $_POST['applicantPhone'] ?? '';
    $district = $_POST['applicantDistrict'] ?? '';
    $message = $_POST['applicantMessage'] ?? '';
    $date = date('Y-m-d H:i:s');
    $entry = "Tournament: $tournament | Name: $name | Email: $email | Phone: $phone | District: $district | Message: $message | Date: $date" . PHP_EOL;
    $file = __DIR__ . '/tournament-entries.txt';
    if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) !== false) {
        echo '<script>alert("Application submitted successfully!");window.location.href="tournament.html";</script>';
    } else {
        echo '<script>alert("Failed to save data.");window.location.href="tournament.html";</script>';
    }
    exit;
}
echo 'Invalid request';
