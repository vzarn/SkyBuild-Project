<?php
date_default_timezone_set('Asia/Manila');
/* SkyBuild – Hardened DB Connection */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "skybuild";

try {
    $conn = new mysqli($servername, $username, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Log securely – never expose DB credentials to the browser
    error_log("DB Connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}
?>