<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight request headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, user, token, issuperadmin');
    header('Access-Control-Max-Age: 1728000'); // Cache preflight response for 20 days
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die(); // Terminate the script after handling OPTIONS
}

header('Access-Control-Allow-Origin: *'); // Allow requests from any origin
header('Content-Type: application/json'); // Set response content-type to JSON
?>
