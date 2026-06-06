<?php
/**
 * Secure ID Registration Handler
 * Prevents SQL injection, file upload attacks, and implements comprehensive security
 */

header('Content-Type: application/json');

require_once 'security-config.php';
require_once 'secure-database.php';

// Rate limiting
Security::rateLimit(3, 600); // 3 registrations per 10 minutes

// Directory to save uploaded photos
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper to generate unique 4-digit ID
function generateUniqueId($db) {
    do {
        $reg_id = rand(1000, 9999);
        $existing = $db->select('registrations', ['reg_id' => $reg_id]);
    } while (!empty($existing));
    return $reg_id;
}

// Check if form data and file are present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        Security::logEvent('CSRF_TOKEN_INVALID', ['form' => 'registration']);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Please refresh the page and try again.']);
        exit;
    }
    
    // Sanitize and validate inputs
    $name = Security::sanitizeInput($_POST['regName'] ?? '');
    $mobile = Security::sanitizeInput($_POST['regMobile'] ?? '');
    $city = Security::sanitizeInput($_POST['regCity'] ?? '');
    $state = Security::sanitizeInput($_POST['regState'] ?? '');
    $joined = Security::sanitizeInput($_POST['regDate'] ?? '');
    $photo = $_FILES['regPhoto'] ?? null;

    // Validate required fields
    if (empty($name) || empty($mobile) || empty($city) || empty($state) || empty($joined) || !$photo) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate name
    if (!Security::validateName($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid name (letters only, 2-50 characters).']);
        exit;
    }

    // Validate mobile
    if (!Security::validatePhone($mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number.']);
        exit;
    }

    // Validate city and state
    if (strlen($city) < 2 || strlen($city) > 50) {
        echo json_encode(['status' => 'error', 'message' => 'City must be between 2 and 50 characters.']);
        exit;
    }

    if (strlen($state) < 2 || strlen($state) > 50) {
        echo json_encode(['status' => 'error', 'message' => 'State must be between 2 and 50 characters.']);
        exit;
    }

    // Validate date
    if (!DateTime::createFromFormat('Y-m-d', $joined)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format.']);
        exit;
    }

    // Validate photo upload
    $photoValidation = Security::validateFileUpload($photo, ['jpg', 'jpeg', 'png'], 2097152); // 2MB limit
    if (!$photoValidation['success']) {
        echo json_encode(['status' => 'error', 'message' => $photoValidation['error']]);
        exit;
    }

    // Check for duplicate mobile number
    try {
        $db = SecureDatabase::getInstance();
        $existing = $db->select('registrations', ['mobile' => $mobile]);
        if (!empty($existing)) {
            echo json_encode(['status' => 'error', 'message' => 'This mobile number is already registered.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
        exit;
    }

    // Generate secure filename and save photo
    $photoFilename = Security::generateSecureFilename($photo['name']);
    $photoPath = $uploadDir . $photoFilename;
    
    if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
        Security::logEvent('PHOTO_UPLOAD_FAILED', [
            'name' => $name,
            'mobile' => $mobile,
            'original_name' => $photo['name']
        ]);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save photo.']);
        exit;
    }

    // Set secure permissions
    chmod($photoPath, 0644);

    // Generate unique ID
    $reg_id = generateUniqueId($db);

    // Insert into database securely
    try {
        $db->beginTransaction();
        
        $db->insert('registrations', [
            'reg_id' => $reg_id,
            'name' => $name,
            'mobile' => $mobile,
            'city' => $city,
            'state' => $state,
            'joined' => $joined,
            'photo' => $photoFilename
        ]);
        
        $db->commit();
        
        // Log successful registration
        Security::logEvent('REGISTRATION_SUCCESS', [
            'reg_id' => $reg_id,
            'name' => $name,
            'mobile' => $mobile,
            'city' => $city,
            'state' => $state
        ]);
        
        // --- SMS API Integration (Optional) ---
        // Uncomment and configure with your SMS provider
        /*
        $apiKey = 'YOUR_API_KEY';
        $senderId = 'FSTSMS';
        $message = "Dear $name, your TCAM Registration is successful! Your ID: $reg_id";
        $numbers = $mobile;
        $route = 'p';
        $url = "https://www.fast2sms.com/dev/bulkV2?authorization=$apiKey&sender_id=$senderId&message=".urlencode($message)."&language=english&route=$route&numbers=$numbers";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            Security::logEvent('SMS_SEND_FAILED', [
                'reg_id' => $reg_id,
                'mobile' => $mobile,
                'response' => $response
            ]);
        }
        */
        // --- END SMS API ---

        // Respond with ID and photo URL
        $photoUrl = 'uploads/' . $photoFilename;
        echo json_encode([
            'status' => 'success',
            'id' => $reg_id,
            'photoUrl' => $photoUrl,
            'message' => 'Registration successful! Your TCAM ID: ' . $reg_id
        ]);
        exit;
        
    } catch (Exception $e) {
        // Clean up uploaded file on database error
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
        
        Security::logEvent('REGISTRATION_ERROR', [
            'name' => $name,
            'mobile' => $mobile,
            'error' => $e->getMessage()
        ]);
        
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
        exit;
    }
}

// Not a POST request
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
exit;
?>
