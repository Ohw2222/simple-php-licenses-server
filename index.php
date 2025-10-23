<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Require config and database
require_once 'config.php';
require_once 'db.php';

function is_in_array($needle, $haystack) {
    foreach($haystack as $k => $v){
        if($needle == $k || $needle == $v){
            return true;
        }
    }
}
// Check if user is authenticated
check_auth();

// Simple router
$page = $_GET['page'] ?? 'index';
if($page == "" || $page == NULL){
    $page = "index";
}


// Whitelist of allowed pages
$allowed_pages = [
    'index' => ["h"=>"publi_header.php","f"=>"publi_footer.php"],
    '404' => ["h"=>"publi_header.php","f"=>"publi_footer.php"],
    'dashboard' => ["h"=>"header.php","f"=>"footer.php"],
    'softwares' => ["h"=>"header.php","f"=>"footer.php"],
    'versions' => ["h"=>"header.php","f"=>"footer.php"],
    'customers' => ["h"=>"header.php","f"=>"footer.php"],
    'licences' => ["h"=>"header.php","f"=>"footer.php"]
];
// Validate the page
if (!is_in_array($page, $allowed_pages)) {
    $page = '404';
}

// Include the header
require_once 'templates/'.$allowed_pages[$page]["h"];

// Include the specific page content
$page_file = "pages/{$page}.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    echo "<p class='text-red-500'>Error: Page file not found: " . e($page_file) . "</p>";
}

// Include the footer
require_once 'templates/'.$allowed_pages[$page]["f"];

?>
