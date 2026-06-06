<?php
$file = __DIR__ . '/tournament-entries.txt';
echo '<h2>All Tournament Applications</h2>';
echo '<table border="1" cellpadding="6" style="border-collapse:collapse;">';
echo '<tr><th>Tournament</th><th>Name</th><th>Email</th><th>Phone</th><th>District</th><th>Message</th><th>Date</th></tr>';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        preg_match('/Tournament: (.*?) \| Name: (.*?) \| Email: (.*?) \| Phone: (.*?) \| District: (.*?) \| Message: (.*?) \| Date: (.*)/', $line, $matches);
        if ($matches) {
            echo '<tr>';
            for ($i=1; $i<=7; $i++) echo '<td>' . htmlspecialchars($matches[$i]) . '</td>';
            echo '</tr>';
        }
    }
} else {
    echo '<tr><td colspan="7">No entries yet.</td></tr>';
}
echo '</table>';
