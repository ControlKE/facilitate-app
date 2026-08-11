<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/vendorAutoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/messageRoutingHelper.php';
ensureVendorAutoload();

// Enable or disable exceptions via variable
$debug = true;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST')) {
      header('Access-Control-Allow-Origin: http://localhost:5173');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
      header('Access-Control-Allow-Credentials: true');
      exit;
  } else {
      header("Access-Control-Allow-Origin: *");
      header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
      header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
      header("Access-Control-Max-Age: 3600");
      exit;
  }
}

// Define some constants
define("RECIPIENT_NAME", "Steve Mburu");
define("RECIPIENT_EMAIL", "steve@facilitatecareservices.co.uk");

// Get the form values
$data = json_decode(file_get_contents("php://input"));

// Read the form values
$userName = isset($data->username) ? preg_replace("/[^\s\S\.\-\_\@a-zA-Z0-9]/", "", $data->username) : "";
$senderEmail = isset($data->email) ? preg_replace("/[^\.\-\_\@a-zA-Z0-9]/", "", $data->email) : "";
$senderPhone = isset($data->phone) ? preg_replace("/[^\s\S\.\-\_\@a-zA-Z0-9]/", "", $data->phone) : "";
$message = isset($data->message) ? preg_replace("/(From:|To:|BCC:|CC:|Subject:|Content-Type:)/", "", $data->message) : "";

// If all values exist, send the email
if ($userName && $senderEmail && $senderPhone && $message) {
  // Create an instance; passing `true` enables exceptions
  
  try {
    $mail = new PHPMailer(true);
    // Server settings
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->CharSet = 'utf-8';
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    //$mail->SMTPSecure = 'tls';            //Enable implicit TLS encryption
    $mail->SMTPSecure = 'SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS';
    $mail->Host       = 'mailout.one.com';                     //SMTP server
    //$mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    //$mail->Port= 465;
    $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail->isHTML(true);                                  //Set email format to plain text

    $mail->Username = messageRoutingSmtpUsername();                       //SMTP username
    $mail->Password = messageRoutingSmtpPassword();                       //SMTP password

    // Recipients
    $mail->setFrom($senderEmail, $userName);
    $mail->addAddress(RECIPIENT_EMAIL, RECIPIENT_NAME);     //Add a recipient

    // Content
    
    $mail->Subject = 'Contact Form Submission';
    $mail->Body    = "Name: " . $userName . "\nEmail: " . $senderEmail . "\nPhone: " . $senderPhone . "\nMessage: " . $message;

    
    // $mail->Encoding = 'base64';

    $mail->send();
    
    echo json_encode(['message' => 'Email sent successfully.']);
  } catch (Exception $e) {
    echo "Mailer Error: ".$e->getMessage();
    echo json_encode(['message' => 'Failed to send email. Mailer Error: ' . $mail->ErrorInfo]);
    http_response_code(500);
  }
} else {
  echo json_encode(['message' => 'All fields are required.']);
  http_response_code(400);
}
?>
