<?php

/**
 * Application Status Checker
 *
 * Include this file at the top of your PHP application.
 * It will check the application's status against the central server.
 * If the status is not 'active', it will stop all further execution
 * and display an HTML error page.
 */

// --- Configuration ---

// Set this to the full URL of your api.php file
$api_url = 'https://YOUR URL/api.php'; // <-- !!! UPDATE THIS URL !!!

// Set the unique name for this application (must match the 'name' in your server)
$app_name_to_check = ''; 
$app_key = "";


// ===================================================================
//  Function to call the API using GET (Simpler, good for names)
// ===================================================================
/**
 * Calls the remote API using file_get_contents with the GET method.
 *
 * @param string $url The API endpoint URL.
 * @param array $data The data to send (e.g., ['name' => '...']).
 * @return array The decoded JSON response.
 */
function check_status_get($url, $data) {
    // Build the query string
    $query_string = http_build_query($data);
    $full_url = $url . '?' . $query_string;
    
    // Set up a stream context to handle errors
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true // Allows us to read the response on 4xx/5xx errors
        ]
    ]);
    
    $response_json = @file_get_contents($full_url, false, $context);

    if ($response_json === false) {
        return ['active' => false, 'message' => 'API call failed (file_get_contents).'];
    }
    
    // Decode the JSON response
    $response_data = json_decode($response_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
         return ['active' => false, 'message' => 'API response was not valid JSON.'];
    }
    
    return $response_data;
}


// ===================================================================
//  Function to display the failure page and stop execution
// ===================================================================
/**
 * Displays a sleek HTML error page and terminates the script.
 *
 * @param string|null $message The message from the API.
 */
function display_failure_page($message) {
    
    $default_message = <<<HTML
    Autorisation expirée, contactez (YOUR NAME) au <a class="text-indigo-600 hover:text-indigo-800" href="tel:(YOUR PHONE)">(YOUR PHONE)</a> ou via <a class="text-indigo-600 hover:text-indigo-800" href="mailto:(YOUR EMAIL)">(YOUR EMAIL)</a> ou via <a class="text-indigo-600 hover:text-indigo-800" href="mailto:(YOUR EMAIL 2)">(YOUR EMAIL 2)</a>
    <br><br>
    Expired authorization, please contact (YOUR NAME) at <a class="text-indigo-600 hover:text-indigo-800" href="tel:(YOUR PHONE)">(YOUR PHONE)</a> or at <a class="text-indigo-600 hover:text-indigo-800" href="mailto:(YOUR EMAIL)">(YOUR EMAIL)</a> or at <a class="text-indigo-600 hover:text-indigo-800" href="mailto:(YOUR EMAIL 2)">(YOUR EMAIL 2)</a>
HTML;

    // Use the default message if the provided one is empty or null
    $message_html = (!empty($message)) ? ($message) : $default_message;
    
    // Send 403 Forbidden header
    header('HTTP/1.1 403 Forbidden');

    // Output the HTML page
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès non autorisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-2xl p-8 bg-white rounded-lg shadow-2xl text-center">
        <svg class="mx-auto h-16 w-16 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
        <h1 class="text-3xl font-bold text-gray-800 mt-4">Utilisation impossible</h1>
        <h2 class="text-2xl font-medium text-gray-700 mt-1">Impossible to use</h2>
        <div class="mt-6 border-t border-gray-200 pt-6">
            <div class="text-base text-gray-600 leading-relaxed">
                $message_html
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    // Stop all script execution
    die();
}

function display_info_page($message) {
    
    $default_message = <<<HTML
    Erreur dans l'execution de l'info-page
    <br><br>
    Error in the execution of the info-page
HTML;

    // Use the default message if the provided one is empty or null
    $message_html = (!empty($message)) ? ($message) : $default_message;
    
    // Send 403 Forbidden header
    header('HTTP/1.1 303 Information');

    // Output the HTML page
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Information</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-2xl p-8 bg-white rounded-lg shadow-2xl text-center">
        <svg class="mx-auto h-16 w-16 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 8v5m0 3h.01" />
        </svg>
        <h1 class="text-3xl font-bold text-gray-800 mt-4">Information :</h1>
        <div class="mt-6 border-t border-gray-200 pt-6">
            <div class="text-base text-gray-600 leading-relaxed">
                $message_html
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    // Stop all script execution
    die();
}


// ===================================================================
//  --- SCRIPT EXECUTION ---
//  This runs when the file is included.
// ===================================================================

if (empty($app_name_to_check)) {
    display_failure_page("Erreur de configuration locale : nom d'application non défini.");
}

// 1. Prepare data for the check
$data_to_check = ['name' => $app_name_to_check, 'key' => $app_key];

// 2. Call the API
$result = check_status_get($api_url, $data_to_check);

// 3. Act on the result
if (!isset($result['active']) || $result['active'] !== true) {
    // Call was unsuccessful or status is inactive
    $message = $result['message'] ?? null;
    display_failure_page($message);
}

// 4. If we are here, the status is 'active' and the script
//    will finish, allowing the parent application to load.
if(isset($_GET["licences_server_fr_end_date"])){
    $data_to_check = ['name' => $app_name_to_check, 'key' => $app_key, "action" => "end_date"];
    $result = check_status_get($api_url, $data_to_check);

    if (isset($result['active']) && $result['active'] === true) {
        if(isset($result["end_date"])){
            // Call was unsuccessful or status is inactive
            $message = "Votre licence expirera le ". date("d/m/Y",strtotime($result["end_date"]))."<br>Your licence will expire on (day/month/year) ". date("d/m/Y", strtotime($result["end_date"]));
            display_info_page($message);
        }else{
            $message = "Impossible de récupérer la date d'expiration de votre licence<br>Impossible to get the expiration date of your licence.";
            display_failure_page($message);
        }   
    }else{
        $message = $result['message'] ?? null;
        display_failure_page($message);
    }
}
?>
