<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');

// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST')) {
//         header('Access-Control-Allow-Origin: http://localhost:5173');
//         header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
//         header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
//         header('Access-Control-Allow-Credentials: true');
//         exit;
//     } else {
//         header("Access-Control-Allow-Origin: *");
//         header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
//         header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
//         header("Access-Control-Max-Age: 3600");
//         exit;
//     }
// }

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/messageRoutingHelper.php';
$conn = createDatabaseConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $data = json_decode(file_get_contents("php://input"));
    
    if ($action == "addpost") {
        $Title = $data->Title; 
        $FirstName = $data->FirstName;
        $SecondName = $data->SecondName;
        $Email = $data->Email;
        $Phonenumber = $data->Phonenumber;
        $Message = $data->Message;
        $Date = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO `complaints`(`Title`, `FirstName`, `SecondName`, `Email`, `PhoneNumber`, `Message`, `Date`) 
        VALUES (?,?,?,?,?,?,?)");

        if ($stmt) {
            $stmt->bind_param("sssssss", $Title, $FirstName, $SecondName, $Email, $Phonenumber, $Message, $Date);

            if ($stmt->execute()) {
                $notification = safeDispatchCategoryNotification($conn, MESSAGE_CATEGORY_COMPLAINTS, [
                    'submittedAt' => $Date,
                    'title' => $Title,
                    'firstName' => $FirstName,
                    'lastName' => $SecondName,
                    'email' => $Email,
                    'phone' => $Phonenumber,
                    'message' => $Message,
                ]);

                $response = array(
                    "success" => true,
                    "message" => $notification['sent']
                        ? "Complaint sent successfully."
                        : "Complaint saved, but the email notification could not be sent.",
                    "notification" => $notification,
                );
                echo json_encode($response);
            } else {
                $response = array("success" => false, "error" => $stmt->error);
                echo json_encode($response);
            }

            $stmt->close();
        } else {
            $response = array("success" => false, "error" => "Failed to prepare the SQL statement");
            echo json_encode($response);
        }
        
        $conn->close();
        
    } 
} 

?>
