<?php
// --- MAIN CONFIGURATION ---

// Set to false to disable new user registrations
define('ENABLE_REGISTRATION', true);

// --- DATABASE SETTINGS ---

// Option 1: MySQL/MariaDB
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'my_database');
define('DB_USER', 'my_database_user');
define('DB_PASS', 'wonderfulpassword'); // Your database password
define('SERVICENAME', 'Your licencing service name'); // This is "BlaBlaLicence", the licencing service
define("SERVICECREATOR", "Your name/Company name"); // This is your company's name ie : "TommySoft" 's softwares

// Option 2: SQLite (used as a fallback if MySQL fails)
define('SQLITE_FILE', 'db/license_server.sqlite');


// --- SESSION ---
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- HELPERS ---
/**
 * Checks if a user is logged in. If not, redirects to login.php.
 */
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        if(isset($_GET["page"])){
            header("Location: login.php");
            exit;
        }
        
    }
}

/**
 * Generates a random, secure secret key.
 */
function generate_secret_key($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Escapes HTML for safe output.
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
