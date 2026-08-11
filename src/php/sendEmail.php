<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include Composer's autoloader
require_once __DIR__ . '/vendorAutoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/messageRoutingHelper.php';
ensureVendorAutoload();

// Enable or disable exceptions via variable
$debug = true;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define constants for recipient details
define("RECIPIENT_NAME", "Steve Mburu");
define("RECIPIENT_EMAIL", "steve@facilitatecareservices.co.uk");
define("FROM_EMAIL", "contact@facilitatecareservices.co.uk"); // Website's contact email
define("FROM_NAME", "Facilitate Care Services Contact Form"); // Name to appear in the email

// Get the form values
$data = json_decode(file_get_contents("php://input"));

// Read the form values and sanitize them
$userName = isset($data->username) ? preg_replace("/[^\s\S\.\-\_\@a-zA-Z0-9]/", "", $data->username) : "";
$senderEmail = isset($data->email) ? preg_replace("/[^\.\-\_\@a-zA-Z0-9]/", "", $data->email) : "";
$senderPhone = isset($data->phone) ? preg_replace("/[^\s\S\.\-\_\@a-zA-Z0-9]/", "", $data->phone) : "";
$message = isset($data->message) ? preg_replace("/(From:|To:|BCC:|CC:|Subject:|Content-Type:)/", "", $data->message) : "";

// Validate that all fields are filled
if ($userName && $senderEmail && $senderPhone && $message) {
    try {
        $mail = new PHPMailer(true);

        // SMTP server configuration
        $mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF; // Enable detailed debug output
        $mail->isSMTP();
        $mail->Host       = messageRoutingSmtpHost();
        $mail->SMTPAuth   = true;
        $mail->Username   = messageRoutingSmtpUsername();
        $mail->Password   = messageRoutingSmtpPassword();
        $mail->CharSet    = 'utf-8';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL/TLS
        $mail->Port       = messageRoutingSmtpPort(); // SSL/TLS port

        // Optional: Disable SSL certificate verification (for troubleshooting only)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Set the From address to the website's contact email
        $mail->setFrom(FROM_EMAIL, FROM_NAME);

        // Set the Reply-To header to the user's email
        $mail->addReplyTo($senderEmail, $userName);

        // Add the recipient address
        $mail->addAddress(RECIPIENT_EMAIL, RECIPIENT_NAME);

        // Email content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = 'Contact Form Submission';
        $mail->Body    = "You have received a new message from your website contact form.\n\n" .
                         "Here are the details:\n" .
                         "Name: " . $userName . "\n" .
                         "Email: " . $senderEmail . "\n" .
                         "Phone: " . $senderPhone . "\n" .
                         "Message: " . $message;

        // Send the email
        $mail->send();
        echo json_encode(['message' => 'Email sent successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to send email. Mailer Error: ' . $mail->ErrorInfo]);
    }
} else {
    http_response_code(400);
    echo json_encode(['message' => 'All fields are required.']);
}
