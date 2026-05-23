<?php
date_default_timezone_set('Asia/Manila');
/* SkyBuild – Hardened DB Connection */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username   = "root";
$password   = ""; // XAMPP default: no password
$database   = "skybuild";

try {
    $conn = new mysqli($servername, $username, $password, $database);
    $conn->set_charset("utf8mb4");

    // Ensure pre_quotations table exists
    $conn->query("CREATE TABLE IF NOT EXISTS pre_quotations (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        project_type VARCHAR(100) NOT NULL,
        building_type VARCHAR(100) NOT NULL,
        sqm DECIMAL(10,2) NOT NULL,
        floors INT(11) NOT NULL,
        material_level VARCHAR(50) NOT NULL,
        estimated_total DECIMAL(10,2) NOT NULL,
        items_json TEXT DEFAULT NULL,
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_viewed TINYINT(1) NOT NULL DEFAULT 0
    )");

    // Ensure inquiries columns are up to date (handling standard SQL dump differences)
    $res = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'contact_name'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE inquiries ADD COLUMN contact_name VARCHAR(255) NOT NULL DEFAULT '' AFTER phone");
        $conn->query("ALTER TABLE inquiries ADD COLUMN contact_number VARCHAR(50) NOT NULL DEFAULT '' AFTER contact_name");
        $conn->query("ALTER TABLE inquiries ADD COLUMN source VARCHAR(50) NOT NULL DEFAULT 'contact' AFTER contact_number");
    }

    $res2 = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'is_read'");
    if ($res2 && $res2->num_rows == 0) {
        $conn->query("ALTER TABLE inquiries ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message");
    }

    // Add status column to pre_quotations if not exists
    $res_pq_status = $conn->query("SHOW COLUMNS FROM pre_quotations LIKE 'status'");
    if ($res_pq_status && $res_pq_status->num_rows == 0) {
        $conn->query("ALTER TABLE pre_quotations ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft' AFTER estimated_total");
    }

    // Add status column to inquiries if not exists
    $res_inq_status = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'status'");
    if ($res_inq_status && $res_inq_status->num_rows == 0) {
        $conn->query("ALTER TABLE inquiries ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'unresolved' AFTER is_read");
    }

} catch (mysqli_sql_exception $e) {
    // Log securely – never expose DB credentials to the browser
    error_log("DB Connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}
?>