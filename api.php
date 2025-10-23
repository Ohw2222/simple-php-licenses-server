<?php
// Require config and database connection
require_once 'config.php';
require_once 'db.php';

// Set header to JSON
header('Content-Type: application/json');

// 1. Determine request method and get parameters
$params = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = $_POST;
} else {
    $params = $_GET;
}

// 2. Get the key or name from the parameters
// We use 'name' as the query parameter, which maps to the 'licence_name' column
$key = $params['key'] ?? '';
$name = $params['name'] ?? '';

$sql = '';
$query_params = [];

// 3. Build the query based on provided parameters
if (!empty($key) && !empty($name)) {
    // Priority 1: Check by secret_key (guaranteed unique)
    $sql = "SELECT * FROM licences WHERE secret_key = ? AND licence_name = ? ORDER BY end_date DESC LIMIT 1";
    $query_params = [$key,$name];
} elseif (!empty($key)) {
    // Priority 2: Check by licence_name
    // Fetches the license with this name that has the latest expiry date.
    $sql = "SELECT * FROM licences WHERE secret_key = ? ORDER BY end_date DESC LIMIT 1";
    $query_params = [$name];
} else {
    // No credentials provided
    http_response_code(400); // Bad Request
    echo json_encode(['active' => false, 'message' => 'Clé et nom de licence non fournis.<br>No secret key or name provided.']);
    exit;
}

try {
    // Prepare response
    $response = ['active' => false, 'message' => 'Licence invalide ou expirée<br>Invalid or expired license.'];
    
    // 4. Execute the dynamic query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($query_params);
    $licence = $stmt->fetch();

    if ($licence) {
        // Key/Name exists, now check the date
        $today = new DateTime();
        // Create DateTime object, ensuring time is 00:00:00 for a fair comparison
        $end_date = new DateTime($licence['end_date']);
        $end_date->setTime(23, 59, 59); // License is valid for the whole day

        if ($end_date >= $today) {
            // License is valid and active
            if(isset($_GET["action"])){
                if($_GET["action"] == "end_date"){
                    $response = ['active' => true,"end_date" => $licence["end_date"]];
                }else{
                    $response = ['active' => true];
                }
            }else{
                $response = ['active' => true];
            }
            
        } else {
            // License exists but has expired
            $response['message'] = $licence['message'] ?? 'Votre licence à expiré<br>Your license has expired.';
        }
    } else {
        // Key or Name does not exist
        $response['message'] = 'Clé de licence ou nom de licence non trouvée<br>License key or name not found.';
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['active' => false, 'message' => 'API database error.']);
}


?>

