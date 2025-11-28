<?php
/**
 * ArcGIS Form Submission Email Notification Handler
 * Uses PHP mail() function - no SMTP configuration needed
 */

// ============= CONFIGURATION =============
// IMPORTANT: Change these settings!

// Your InfinityFree domain (e.g., yoursite.rf.gd or yoursite.epizy.com)
define('FROM_EMAIL', 'noreply@yoursite.rf.gd');          // ← Change to YOUR domain
define('FROM_NAME', 'ArcGIS Form System');               // ← Change organization name

// Admin email - where YOU will receive notifications
define('ADMIN_EMAIL', 'ddayal434@gmail.com');            // ← Your email to receive notifications

// Reply-to email (where users can reply)
define('REPLY_TO_EMAIL', 'ddayal434@gmail.com');         // ← Your email for replies

// Send confirmation to users who submit the form?
define('SEND_USER_CONFIRMATION', true);                  // true or false

// InfinityFree specific settings
ini_set('sendmail_from', FROM_EMAIL);

// Enable logging for debugging
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFile = __DIR__ . '/webhook_log.txt';

// ============= FUNCTIONS =============

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function sendEmail($toEmail, $subject, $htmlBody) {
    // Email headers
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . REPLY_TO_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'X-Priority: 3';
    
    // Additional parameters for better delivery
    $additionalParams = '-f' . FROM_EMAIL;
    
    // Send email using PHP mail() function
    $success = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers), $additionalParams);
    
    if ($success) {
        logMessage("✅ Email sent successfully to: $toEmail");
        return true;
    } else {
        logMessage("❌ Email send failed to: $toEmail");
        return false;
    }
}

function formatDateTime($timestamp) {
    if (empty($timestamp)) return 'Not recorded';
    
    // ArcGIS timestamps are in milliseconds
    if (is_numeric($timestamp) && $timestamp > 1000000000000) {
        return date('F j, Y, g:i A', $timestamp / 1000);
    } elseif (is_numeric($timestamp)) {
        return date('F j, Y, g:i A', $timestamp);
    } else {
        $time = strtotime($timestamp);
        return $time ? date('F j, Y, g:i A', $time) : $timestamp;
    }
}

function buildAdminEmailHTML($attr) {
    $name = $attr['name'] ?? 'Not provided';
    $email = $attr['email_contact'] ?? 'Not provided';
    $height = $attr['height_user'] ?? 'Not provided';
    $comments = $attr['Comments'] ?? 'No comments';
    $dateSpotted = formatDateTime($attr['Date_spotted'] ?? null);
    $latitude = $attr['latitude_y_camera'] ?? 'N/A';
    $longitude = $attr['longitude_x_camera'] ?? 'N/A';
    $accuracy = $attr['show_accuracy_2'] ?? 'N/A';
    $direction = $attr['show_direction_2'] ?? 'N/A';
    $altitude = $attr['show_altitude'] ?? 'N/A';
    $globalId = $attr['globalid'] ?? 'N/A';
    $objectId = $attr['objectid'] ?? 'N/A';
    $creator = $attr['Creator'] ?? 'Unknown';
    $creationDate = formatDateTime($attr['CreationDate'] ?? null);
    
    return "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 700px; margin: 0 auto; padding: 0; }
        .header { background-color: #d32f2f; color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px 20px; }
        .alert-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; }
        .section { margin-bottom: 25px; }
        .section-header { background-color: #f5f5f5; padding: 12px 15px; margin-bottom: 15px; font-weight: bold; color: #d32f2f; border-left: 4px solid #d32f2f; font-size: 16px; }
        .field { padding: 8px 15px; border-bottom: 1px solid #eee; }
        .field-label { display: inline-block; width: 180px; font-weight: bold; color: #555; }
        .field-value { color: #333; }
        .footer { background-color: #f5f5f5; text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .map-link { display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #0079c1; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🚨 NEW FORM SUBMISSION RECEIVED</h1>
        </div>
        <div class='content'>
            <div class='alert-box'>
                <strong>⚠️ Action Required:</strong> A new form has been submitted and requires your attention.
            </div>
            
            <div class='section'>
                <div class='section-header'>👤 USER INFORMATION</div>
                <div class='field'>
                    <span class='field-label'>Name:</span>
                    <span class='field-value'><strong>$name</strong></span>
                </div>
                <div class='field'>
                    <span class='field-label'>Email:</span>
                    <span class='field-value'>$email</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Height:</span>
                    <span class='field-value'>$height m</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Comments:</span>
                    <span class='field-value'>$comments</span>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-header'>📍 LOCATION INFORMATION</div>
                <div class='field'>
                    <span class='field-label'>Date/Time Captured:</span>
                    <span class='field-value'>$dateSpotted</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Latitude (Northing):</span>
                    <span class='field-value'>$latitude</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Longitude (Easting):</span>
                    <span class='field-value'>$longitude</span>
                </div>
                <div class='field'>
                    <span class='field-label'>GPS Accuracy:</span>
                    <span class='field-value'>$accuracy m</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Direction:</span>
                    <span class='field-value'>$direction°</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Elevation:</span>
                    <span class='field-value'>$altitude m</span>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-header'>📝 SUBMISSION DETAILS</div>
                <div class='field'>
                    <span class='field-label'>Reference ID:</span>
                    <span class='field-value'>$globalId</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Object ID:</span>
                    <span class='field-value'>$objectId</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Submitted By:</span>
                    <span class='field-value'>$creator</span>
                </div>
                <div class='field'>
                    <span class='field-label'>Submission Time:</span>
                    <span class='field-value'>$creationDate</span>
                </div>
            </div>
            
            " . (($latitude != 'N/A' && $longitude != 'N/A') ? 
            "<div style='text-align: center; margin-top: 20px;'>
                <a href='https://www.google.com/maps?q=$latitude,$longitude' class='map-link' target='_blank'>
                    📍 View Location on Google Maps
                </a>
            </div>" : "") . "
            
        </div>
        <div class='footer'>
            <p>This is an automated notification from your ArcGIS Form System.</p>
            <p>Received: " . date('F j, Y \a\t g:i:s A') . "</p>
        </div>
    </div>
</body>
</html>
";
}

function buildUserConfirmationHTML($attr) {
    $name = $attr['name'] ?? 'User';
    $email = $attr['email_contact'] ?? 'Not provided';
    $comments = $attr['Comments'] ?? 'No comments';
    $dateSpotted = formatDateTime($attr['Date_spotted'] ?? null);
    $latitude = $attr['latitude_y_camera'] ?? 'N/A';
    $longitude = $attr['longitude_x_camera'] ?? 'N/A';
    $globalId = $attr['globalid'] ?? 'N/A';
    
    return "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .header { background-color: #4CAF50; color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px 20px; }
        .success-box { background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-bottom: 20px; color: #155724; }
        .info-box { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .footer { background-color: #f5f5f5; text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .field { padding: 8px 0; }
        .field-label { font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>✅ Submission Confirmed</h1>
        </div>
        <div class='content'>
            <div class='success-box'>
                <strong>✓ Success!</strong> Your form submission has been received successfully.
            </div>
            
            <p>Dear <strong>$name</strong>,</p>
            <p>Thank you for your submission! We have successfully received and recorded your information.</p>
            
            <div class='info-box'>
                <div class='field'>
                    <span class='field-label'>Submission Time:</span> $dateSpotted
                </div>
                <div class='field'>
                    <span class='field-label'>Location:</span> $latitude, $longitude
                </div>
                <div class='field'>
                    <span class='field-label'>Reference ID:</span> $globalId
                </div>
            </div>
            
            " . (!empty($comments) && $comments != 'No comments' ? 
            "<div style='margin: 20px 0;'>
                <strong>Your Comments:</strong><br>
                <em>$comments</em>
            </div>" : "") . "
            
            <p>If you have any questions or need to make changes, please reply to this email.</p>
            
            <p>Best regards,<br>
            <strong>" . FROM_NAME . "</strong></p>
        </div>
        <div class='footer'>
            <p>This is an automated confirmation email.</p>
            <p>For assistance, reply to this email or contact " . REPLY_TO_EMAIL . "</p>
        </div>
    </div>
</body>
</html>
";
}

// ============= MAIN PROCESSING =============

// Set response header
header('Content-Type: application/json');

logMessage("================================================");
logMessage("🔔 Webhook received from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    // Get webhook payload
    $rawPayload = file_get_contents('php://input');
    
    if (empty($rawPayload)) {
        throw new Exception("No data received in request body");
    }
    
    logMessage("📦 Raw payload length: " . strlen($rawPayload) . " bytes");
    
    // Parse JSON
    $webhookData = json_decode($rawPayload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }
    
    logMessage("📋 Webhook data structure: " . json_encode($webhookData, JSON_PRETTY_PRINT));
    
    // Extract attributes from ArcGIS webhook structure
    // ArcGIS webhooks send data in this format: {"features": [{"attributes": {...}}]}
    $attributes = null;
    
    if (isset($webhookData['features']) && is_array($webhookData['features']) && count($webhookData['features']) > 0) {
        // Standard ArcGIS webhook format
        $attributes = $webhookData['features'][0]['attributes'] ?? null;
    } elseif (isset($webhookData['attributes'])) {
        // Alternative format
        $attributes = $webhookData['attributes'];
    } elseif (isset($webhookData['feature']['attributes'])) {
        // Another alternative format
        $attributes = $webhookData['feature']['attributes'];
    } else {
        // Assume the entire payload is attributes
        $attributes = $webhookData;
    }
    
    if (empty($attributes)) {
        throw new Exception("No attributes found in webhook data");
    }
    
    logMessage("✅ Attributes extracted successfully");
    
    // Extract user information
    $userName = $attributes['name'] ?? 'Unknown User';
    $userEmail = $attributes['email_contact'] ?? '';
    
    // Validate user email
    $hasValidUserEmail = !empty($userEmail) && filter_var($userEmail, FILTER_VALIDATE_EMAIL);
    
    if (!$hasValidUserEmail) {
        logMessage("⚠️  No valid user email provided. Only sending admin notification.");
    }
    
    $emailsSent = 0;
    $emailsFailed = 0;
    
    // 1. ALWAYS send admin notification
    logMessage("📧 Sending admin notification to: " . ADMIN_EMAIL);
    $adminSubject = "🔔 New Form Submission - " . $userName;
    $adminBody = buildAdminEmailHTML($attributes);
    
    if (sendEmail(ADMIN_EMAIL, $adminSubject, $adminBody)) {
        $emailsSent++;
        logMessage("✅ Admin notification sent successfully");
    } else {
        $emailsFailed++;
        logMessage("❌ Failed to send admin notification");
    }
    
    // 2. Send user confirmation (if enabled and valid email provided)
    if (SEND_USER_CONFIRMATION && $hasValidUserEmail) {
        logMessage("📧 Sending user confirmation to: " . $userEmail);
        $userSubject = "✅ Form Submission Confirmation - Thank You!";
        $userBody = buildUserConfirmationHTML($attributes);
        
        if (sendEmail($userEmail, $userSubject, $userBody)) {
            $emailsSent++;
            logMessage("✅ User confirmation sent successfully");
        } else {
            $emailsFailed++;
            logMessage("❌ Failed to send user confirmation");
        }
    }
    
    // Success response
    $response = [
        'status' => 'success',
        'message' => 'Webhook processed successfully',
        'emails_sent' => $emailsSent,
        'emails_failed' => $emailsFailed,
        'admin_notified' => ($emailsSent > 0),
        'user_notified' => ($hasValidUserEmail && SEND_USER_CONFIRMATION && $emailsSent > 1),
        'timestamp' => date('c')
    ];
    
    logMessage("✅ Processing complete: {$emailsSent} emails sent, {$emailsFailed} failed");
    logMessage("================================================");
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Error handling
    $errorMsg = $e->getMessage();
    logMessage("❌ ERROR: " . $errorMsg);
    logMessage("================================================");
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $errorMsg,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}

exit;
?>