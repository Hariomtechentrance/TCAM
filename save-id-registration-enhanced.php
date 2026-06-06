<?php
/**
 * Enhanced ID Registration Handler
 * Handles registration with additional fields and duplicate prevention
 */

require_once 'cors.php';
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
        Security::logEvent('CSRF_TOKEN_INVALID', ['form' => 'enhanced_registration']);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Please refresh the page and try again.']);
        exit;
    }
    
    // Sanitize and validate inputs
    $name = Security::sanitizeInput($_POST['name'] ?? '');
    $mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $city = Security::sanitizeInput($_POST['city'] ?? '');
    $state = Security::sanitizeInput($_POST['state'] ?? '');
    $district = Security::sanitizeInput($_POST['district'] ?? '');
    $dob = Security::sanitizeInput($_POST['dob'] ?? '');
    $document_type = Security::sanitizeInput($_POST['document_type'] ?? '');
    $document_number = Security::sanitizeInput($_POST['document_number'] ?? '');
    $address = Security::sanitizeInput($_POST['address'] ?? '');
    $parent_name = Security::sanitizeInput($_POST['parent_name'] ?? '');
    $emergency_contact = Security::sanitizeInput($_POST['emergency_contact'] ?? '');
    $blood_group = Security::sanitizeInput($_POST['blood_group'] ?? '');
    $joined = Security::sanitizeInput($_POST['joined'] ?? '');
    $previous_tournaments = Security::sanitizeInput($_POST['previous_tournaments'] ?? '');

    // Validate required fields
    $requiredFields = ['name', 'mobile', 'city', 'state', 'district', 'document_type', 'document_number', 'joined'];
    foreach ($requiredFields as $field) {
        if (empty(${$field})) {
            echo json_encode(['status' => 'error', 'message' => "Field '$field' is required."]);
            exit;
        }
    }

    // Validate inputs
    if (!Security::validateName($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid name (letters only, 2-50 characters).']);
        exit;
    }

    if (!Security::validatePhone($mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number.']);
        exit;
    }

    if (!empty($email) && !Security::validateEmail($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if (!empty($emergency_contact) && !Security::validatePhone($emergency_contact)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit emergency contact number.']);
        exit;
    }

    $event_name = Security::sanitizeInput($_POST['event_name'] ?? '');
    if (strlen($event_name) < 3) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter the tournament or event name (at least 3 characters).']);
        exit;
    }

    $normDoc = Security::normalizeDocumentNumber($document_type, $document_number);
    if (strlen($normDoc) < 8 || strlen($normDoc) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Enter a valid document number (check format for Aadhar / PAN / Driving Licence).']);
        exit;
    }

    // Validate date of birth
    if (!empty($dob)) {
        $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dobDate || $dobDate > new DateTime()) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid date of birth.']);
            exit;
        }
    }

    // Validate registration date
    if (!DateTime::createFromFormat('Y-m-d', $joined)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid registration date format.']);
        exit;
    }

    try {
        $db = SecureDatabase::getInstance();

        $existingByNorm = $db->execute(
            'SELECT * FROM registrations WHERE document_number_normalized = ? LIMIT 1',
            [$normDoc]
        )->fetchAll();

        if (!empty($existingByNorm)) {
            $existingReg = $existingByNorm[0];
            if ($existingReg['mobile'] !== $mobile) {
                echo json_encode(['status' => 'error', 'message' => 'This ID document is already registered with a different mobile number.']);
                exit;
            }

            $db->insert('event_registrations', [
                'registration_id' => $existingReg['id'],
                'event_name' => $event_name
            ]);

            Security::logEvent('EVENT_REGISTRATION_ADDED', [
                'reg_id' => $existingReg['reg_id'],
                'event' => $event_name
            ]);

            echo json_encode([
                'status' => 'event_added',
                'id' => $existingReg['reg_id'],
                'message' => 'You already have TCAM ID ' . $existingReg['reg_id'] . '. This event has been added to your record — no duplicate profile was created.'
            ]);
            exit;
        }

        $existingMobile = $db->select('registrations', ['mobile' => $mobile]);
        if (!empty($existingMobile)) {
            echo json_encode(['status' => 'error', 'message' => 'This mobile number is already registered. Use the same document number as your first registration, or contact support if you changed your ID.']);
            exit;
        }

        $photo = $_FILES['photo'] ?? null;
        if (!$photo || (int) ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            echo json_encode(['status' => 'error', 'message' => 'Photo is required for new registrations.']);
            exit;
        }

        $photoValidation = Security::validateFileUpload($photo, ['jpg', 'jpeg', 'png'], 2097152);
        if (!$photoValidation['success']) {
            echo json_encode(['status' => 'error', 'message' => $photoValidation['error']]);
            exit;
        }

        $reg_id = generateUniqueId($db);
        $downloadToken = bin2hex(random_bytes(32));

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

        chmod($photoPath, 0644);

        $db->beginTransaction();

        $registrationData = [
            'reg_id' => $reg_id,
            'name' => $name,
            'mobile' => $mobile,
            'email' => $email,
            'city' => $city,
            'state' => $state,
            'district' => $district,
            'date_of_birth' => $dob,
            'document_type' => $document_type,
            'document_number' => $document_number,
            'document_number_normalized' => $normDoc,
            'download_token' => $downloadToken,
            'address' => $address,
            'parent_name' => $parent_name,
            'emergency_contact' => $emergency_contact,
            'blood_group' => $blood_group,
            'joined' => $joined,
            'previous_tournaments' => $previous_tournaments,
            'photo' => $photoFilename
        ];

        $newId = $db->insert('registrations', $registrationData);
        $db->insert('event_registrations', [
            'registration_id' => $newId,
            'event_name' => $event_name
        ]);
        $db->commit();

        Security::logEvent('ENHANCED_REGISTRATION_SUCCESS', [
            'reg_id' => $reg_id,
            'name' => $name,
            'city' => $city,
            'state' => $state
        ]);
        
        // --- SMS API Integration (Optional) ---
        // Uncomment and configure with your SMS provider
        /*
        $apiKey = 'YOUR_API_KEY';
        $senderId = 'FSTSMS';
        $message = "Dear $name, your TCAM Registration is successful! Your ID: $reg_id. Document: $document_number";
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

        // Respond with success
        $photoUrl = 'uploads/' . $photoFilename;
        echo json_encode([
            'status' => 'success',
            'id' => $reg_id,
            'photoUrl' => $photoUrl,
            'message' => "Registration successful! Your TCAM ID: $reg_id\nDocument Number: $document_number\n\nPlease save these details for future reference."
        ]);
        exit;
        
    } catch (Exception $e) {
        // Clean up uploaded file on database error
        if (isset($photoPath) && file_exists($photoPath)) {
            unlink($photoPath);
        }
        
        Security::logEvent('ENHANCED_REGISTRATION_ERROR', [
            'name' => $name,
            'mobile' => $mobile,
            'document_number' => $document_number,
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
