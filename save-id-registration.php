<?php
header('Content-Type: application/json');

// MySQL DB connection (FILL IN YOUR CREDENTIALS BELOW)
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
    echo json_encode(['status' => 'error', 'message' => 'DB Connection failed: ' . $e->getMessage()]);
    exit;
}

// Directory to save uploaded photos
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper to generate unique 4-digit ID
function generateUniqueId() {
    return rand(1000, 9999);
}

// Check if form data and file are present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['regName'] ?? '';
    $mobile = $_POST['regMobile'] ?? '';
    $city = $_POST['regCity'] ?? '';
    $state = $_POST['regState'] ?? '';
    $joined = $_POST['regDate'] ?? '';
    $photo = $_FILES['regPhoto'] ?? null;

    if (!$name || !$mobile || !$city || !$state || !$joined || !$photo) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    // Save photo — validate extension, MIME type, and size
    $photoExt = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
    $allowedPhotoMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    if (!array_key_exists($photoExt, $allowedPhotoMimes)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid photo file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }
    if ($photo['size'] > 2 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Photo must be under 2MB.']);
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMime = finfo_file($finfo, $photo['tmp_name']);
    finfo_close($finfo);
    if ($actualMime !== $allowedPhotoMimes[$photoExt]) {
        echo json_encode(['status' => 'error', 'message' => 'File content does not match its extension.']);
        exit;
    }
    $photoFilename = bin2hex(random_bytes(16)) . '.' . $photoExt;
    $photoPath = $uploadDir . $photoFilename;
    if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save photo.']);
        exit;
    }

    // Generate unique ID
    $reg_id = generateUniqueId();
    // Insert into MySQL
    $stmt = $pdo->prepare('INSERT INTO registrations (reg_id, name, mobile, city, state, joined, photo) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$reg_id, $name, $mobile, $city, $state, $joined, $photoFilename]);

    // --- SMS API Integration ---
    // EXAMPLE: Fast2SMS (replace with your provider and credentials)
    // $apiKey = 'YOUR_API_KEY';
    // $senderId = 'FSTSMS';
    // $message = "Dear $name, your TCAM Registration is successful! Your ID: $reg_id";
    // $numbers = $mobile;
    // $route = 'p';
    // $url = "https://www.fast2sms.com/dev/bulkV2?authorization=$apiKey&sender_id=$senderId&message=".urlencode($message)."&language=english&route=$route&numbers=$numbers";
    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // $response = curl_exec($ch);
    // curl_close($ch);
    // --- END SMS API ---

    // Respond with ID and photo URL
    $photoUrl = 'uploads/' . $photoFilename;
    echo json_encode([
        'status' => 'success',
        'id' => $reg_id,
        'photoUrl' => $photoUrl
    ]);
    exit;
}

// Not a POST request
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
exit;
