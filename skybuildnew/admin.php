<?php
session_start();
date_default_timezone_set('Asia/Manila');

require 'db.php';

// --- API Endpoint for Live Inquiries ---
if (isset($_GET['fetch_live_inquiries'])) {
    header('Content-Type: application/json');
    $inquiries = [];
    $filter_status = $_GET['status_filter'] ?? 'all';
    $query = "SELECT * FROM inquiries WHERE deleted_at IS NULL";
    if ($filter_status === 'resolved') {
        $query .= " AND status = 'resolved'";
    } elseif ($filter_status === 'unresolved') {
        $query .= " AND status = 'unresolved'";
    }
    $query .= " ORDER BY created_at DESC";
    $res = $conn->query($query);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['formatted_date'] = date('M d, Y', strtotime($row['created_at']));
            $inquiries[] = $row;
        }
    }
    $unread_res = $conn->query("SELECT COUNT(*) AS unread FROM inquiries WHERE is_read = 0 AND deleted_at IS NULL");
    $unread_count = $unread_res ? $unread_res->fetch_assoc()['unread'] : 0;
    
    echo json_encode(['inquiries' => $inquiries, 'unread_count' => $unread_count]);
    $conn->close();
    exit;
}

// Create inquiries table
$conn->query("CREATE TABLE IF NOT EXISTS inquiries (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    project_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    contact_name VARCHAR(255) NOT NULL DEFAULT '',
    contact_number VARCHAR(50) NOT NULL DEFAULT '',
    source VARCHAR(50) NOT NULL DEFAULT 'contact',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add is_read to inquiries if not exists
$res = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'is_read'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE inquiries ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

// Add contact_name column to inquiries if not exists
$res = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'contact_name'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE inquiries ADD COLUMN contact_name VARCHAR(255) NOT NULL DEFAULT '' AFTER phone");
    $conn->query("ALTER TABLE inquiries ADD COLUMN contact_number VARCHAR(50) NOT NULL DEFAULT '' AFTER contact_name");
    $conn->query("ALTER TABLE inquiries ADD COLUMN source VARCHAR(50) NOT NULL DEFAULT 'contact' AFTER contact_number");
}

// Create inventory table
$conn->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL
)");

// Add unit_price to inventory if not exists
$res = $conn->query("SHOW COLUMNS FROM inventory LIKE 'unit_price'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE inventory ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity");
}

// Add size, unit to inventory if not exists
$res = $conn->query("SHOW COLUMNS FROM inventory LIKE 'size'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE inventory ADD COLUMN size VARCHAR(100) NOT NULL DEFAULT '' AFTER item_name");
    $conn->query("ALTER TABLE inventory ADD COLUMN unit VARCHAR(50) NOT NULL DEFAULT '' AFTER quantity");
}

// Pre-fill inventory if empty or only has placeholder data
$res = $conn->query("SELECT COUNT(*) AS cnt FROM inventory WHERE deleted_at IS NULL");
$inv_cnt = ($res) ? $res->fetch_assoc()['cnt'] : 0;
if ($inv_cnt < 5) {
    // Clear old placeholder data first
    $conn->query("DELETE FROM inventory WHERE item_name IN ('Pipe (PVC 2 inch)', 'Steel Rebar (10mm)', 'Screwdriver Set')");
    $conn->query("INSERT INTO inventory (item_name, size, quantity, unit, unit_price) VALUES 
        ('Portland Cement (Type I)', '40 kg', 500, 'bag', 285.00),
        ('CHB / Hollow Block', '4 inch', 2000, 'pc', 18.00),
        ('CHB / Hollow Block', '6 inch', 1500, 'pc', 24.00),
        ('Deformed Bar (Rebar)', '10mm x 6m', 400, 'pc', 195.00),
        ('Deformed Bar (Rebar)', '12mm x 6m', 300, 'pc', 280.00),
        ('Washed Sand', '', 50, 'cu.m', 1400.00),
        ('Crushed Gravel (3/4 inch)', '', 40, 'cu.m', 1800.00),
        ('Marine Plywood', '3/4 x 4 x 8 ft', 100, 'sheet', 1350.00),
        ('Good Lumber', '2 x 3 x 10 ft', 200, 'pc', 185.00),
        ('G.I. Corrugated Roofing Sheet', '0.5mm x 10 ft', 300, 'pc', 620.00),
        ('Tekscrew (Self-drilling)', '1 inch', 80, 'box(500)', 320.00),
        ('THHN/THWN Wire', '2.0mm x 150m roll', 50, 'roll', 2850.00),
        ('THHN/THWN Wire', '3.5mm x 150m roll', 30, 'roll', 4200.00),
        ('PVC Electrical Conduit', '3/4 inch dia', 200, 'pc', 85.00),
        ('Convenience Outlet (Universal)', '', 150, 'pc', 125.00),
        ('Circuit Breaker', '20A', 60, 'pc', 480.00),
        ('Lighting Panel Board (8-branch)', '', 15, 'pc', 3200.00),
        ('G.I. Pipe (Schedule 40)', '1/2 x 6m', 80, 'pc', 380.00),
        ('PVC Pipe (Orange, Pressure)', '1/2 x 6m', 120, 'pc', 95.00),
        ('Ball Valve', '1/2 inch', 60, 'pc', 220.00),
        ('Ceramic Floor Tile', '60x60 cm', 500, 'sq.m', 680.00),
        ('G.I. Ridgecap', '', 100, 'pc', 210.00),
        ('G.I. C-Purlin', '2x4x6m', 80, 'pc', 1100.00),
        ('Flat Latex Paint', '4L', 60, 'can', 680.00),
        ('PVC Elbow (90 deg)', '3/4 inch', 200, 'pc', 35.00)");
}

// Create quote folders table
$conn->query("CREATE TABLE IF NOT EXISTS quote_folders (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT(11) UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES quote_folders(id) ON DELETE CASCADE
)");

// Create quotations table
$conn->query("CREATE TABLE IF NOT EXISTS quotations (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folder_id INT(11) UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (folder_id) REFERENCES quote_folders(id) ON DELETE CASCADE
)");

// Create quotation items table
$conn->query("CREATE TABLE IF NOT EXISTS quotation_items (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT(11) UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
)");

// Add po_number to quotations if not exists
$res = $conn->query("SHOW COLUMNS FROM quotations LIKE 'po_number'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE quotations ADD COLUMN po_number VARCHAR(100) NOT NULL DEFAULT ''");
}

// Add signee_name to quotations if not exists
$res = $conn->query("SHOW COLUMNS FROM quotations LIKE 'signee_name'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE quotations ADD COLUMN signee_name VARCHAR(255) NOT NULL DEFAULT ''");
}

// Create events table
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    event_time VARCHAR(50) DEFAULT '',
    title VARCHAR(255) NOT NULL,
    client_name VARCHAR(255) DEFAULT '',
    description TEXT,
    color VARCHAR(20) DEFAULT '#64b5f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create showcase table
$conn->query("CREATE TABLE IF NOT EXISTS showcase (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Pre-fill showcase if empty
$res = $conn->query("SELECT COUNT(*) AS cnt FROM showcase");
if ($res && $res->fetch_assoc()['cnt'] == 0) {
    $conn->query("INSERT INTO showcase (title, description, image_path) VALUES 
        ('The Vineyard Manor - Twin Lakes', 'Located in Laurel, Batangas, this multi-building resort complex features a beautiful vineyard aesthetic, expansive balconies, and elegant hillside architecture designed to harmonize with the natural landscape.', 'twinlakes.png'),
        ('Three-Storey Residential House', 'A modern three-storey residential home featuring striking red vertical architectural accents, a spacious balcony, and secure perimeter fencing, built with high-quality materials for lasting durability.', 'three-storey.jpg')");
}

// Create admins table
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Pre-fill admins if empty
$res = $conn->query("SELECT COUNT(*) AS cnt FROM admins");
if ($res && $res->fetch_assoc()['cnt'] == 0) {
    $default_hash = password_hash('Skyisthelimit2026!', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)");
    $uname = 'skybuild_admin';
    $uemail = 'skybuildadmin@gmail.com';
    $stmt->bind_param("sss", $uname, $default_hash, $uemail);
    $stmt->execute();
}

// Guarantee active admin has Skyisthelimit2026! password
$target_hash = password_hash('Skyisthelimit2026!', PASSWORD_DEFAULT);
$stmt_update = $conn->prepare("UPDATE admins SET password_hash = ?, username = 'skybuild_admin' WHERE id = 1");
$stmt_update->bind_param("s", $target_hash);
$stmt_update->execute();

// One-time migration: update old admin@skybuild.com to skybuildadmin@gmail.com
$conn->query("UPDATE admins SET email = 'skybuildadmin@gmail.com' WHERE email = 'admin@skybuild.com'");

// Create password_resets table (legacy token-based)
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create OTP codes table for email-based forgot password
$conn->query("CREATE TABLE IF NOT EXISTS otp_codes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create purchase_orders table
$conn->query("CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT(11) UNSIGNED NOT NULL,
    po_number VARCHAR(100) NOT NULL DEFAULT '',
    po_date DATE NOT NULL,
    status ENUM('pending','approved','received') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create sales_invoices table
$conn->query("CREATE TABLE IF NOT EXISTS sales_invoices (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT(11) UNSIGNED NULL,
    quotation_id INT(11) UNSIGNED NOT NULL,
    invoice_number VARCHAR(100) NOT NULL DEFAULT '',
    invoice_date DATE NOT NULL,
    status ENUM('draft','issued','paid') NOT NULL DEFAULT 'draft',
    inventory_deducted TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create activity_logs table
$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add security answer columns to admins if not exists
$res = $conn->query("SHOW COLUMNS FROM admins LIKE 'security_maiden'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE admins ADD COLUMN security_maiden VARCHAR(255) DEFAULT 'Cruz'");
    $conn->query("ALTER TABLE admins ADD COLUMN security_color VARCHAR(255) DEFAULT 'purple'");
    $conn->query("ALTER TABLE admins ADD COLUMN security_dog VARCHAR(255) DEFAULT 'Gerrie'");
}

// Add deleted_at columns to all main tables for soft delete
$tables = ['inquiries', 'inventory', 'quote_folders', 'quotations', 'events', 'showcase'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW COLUMNS FROM $t LIKE 'deleted_at'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE $t ADD COLUMN deleted_at DATETIME DEFAULT NULL");
    }
}

// Seed sample consultations if fewer than 5 exist
$res = $conn->query("SELECT COUNT(*) AS cnt FROM inquiries WHERE deleted_at IS NULL");
if ($res && $res->fetch_assoc()['cnt'] < 5) {
    $conn->query("INSERT IGNORE INTO inquiries (fullname, email, phone, project_type, message, contact_name, contact_number, source, created_at) VALUES
        ('Juan dela Cruz', 'juan.delacruz@email.com', '+639171234567', 'full_construction', 'We are planning to build a two-storey residential house in Imus, Cavite. The lot area is about 120 sqm. Looking for a complete contractor package including labor and materials.', 'Juan dela Cruz', '+639171234567', 'contact', '2026-04-15 09:30:00'),
        ('Maria Santos', 'maria.santos@gmail.com', '+639281234567', 'renovation', 'Our kitchen and bathroom needs full renovation. The house is about 10 years old. We want modern fixtures and new tiling throughout. Budget is around 350,000 pesos.', 'Maria Santos', '+639281234567', 'contact', '2026-04-22 14:15:00'),
        ('Roberto Reyes', 'r.reyes@company.ph', '+639391234567', 'electrical', 'We need complete electrical rewiring for our commercial space in Bacoor. The space is 200 sqm and currently has old wiring. Need new panel board and outlets throughout.', 'Roberto Reyes', '+639391234567', 'estimator', '2026-05-03 10:00:00'),
        ('Ana Mendoza', 'ana.mendoza@outlook.com', '+639501234567', 'roofing', 'Our roof is leaking in several areas. The house is a bungalow with approximately 80 sqm roof area. We want to replace the entire roof with new corrugated G.I. sheets.', 'Ana Mendoza', '+639501234567', 'contact', '2026-05-10 16:45:00'),
        ('Carlos Villanueva', 'cvillanueva@bizmail.com', '+639611234567', 'full_construction', 'We are looking to build a small commercial building — a three-door apartment unit in General Trias. Total floor area is approximately 300 sqm. Please provide a detailed quotation.', 'Carlos Villanueva', '+639611234567', 'estimator', '2026-05-18 08:30:00')");
}

function get_real_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Helper function for logging
function log_activity($conn, $action, $details = '') {
    $ip = get_real_ip();
    $stmt = $conn->prepare("INSERT INTO activity_logs (action, details, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $action, $details, $ip);
    $stmt->execute();
}

function find_inventory_item_for_quote_item($conn, $item_name) {
    $stmt = $conn->prepare("
        SELECT id, quantity
        FROM inventory
        WHERE deleted_at IS NULL
          AND (
            item_name = ?
            OR item_name LIKE CONCAT('%', ?, '%')
            OR ? LIKE CONCAT('%', item_name, '%')
          )
        ORDER BY
          CASE
            WHEN item_name = ? THEN 0
            WHEN ? LIKE CONCAT('%', item_name, '%') THEN 1
            ELSE 2
          END,
          unit_price ASC,
          id ASC
        LIMIT 1
    ");
    $stmt->bind_param("sssss", $item_name, $item_name, $item_name, $item_name, $item_name);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// --- 2. Authentication ---
$error = '';
$action_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $admin_user = $_POST['username'] ?? '';
    $admin_pass = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password_hash, email FROM admins WHERE username = ?");
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($admin_pass, $row['password_hash'])) {
            // Password OK — start 2FA flow instead of logging in directly
            $_SESSION['admin_2fa_pending'] = true;
            $_SESSION['admin_2fa_id']      = $row['id'];
            $_SESSION['admin_2fa_email']   = $row['email']; // pre-fill for display
            log_activity($conn, "Login Step 1", "Password verified for '$admin_user'; awaiting 2FA");
            header('Location: admin.php?verify_2fa=1');
            exit;
        } else {
            log_activity($conn, "Login Failed", "Attempted login with username: $admin_user");
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Invalid credentials';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_2fa_otp'])) {
    // 2FA Step 1: admin submits email/contact to receive OTP
    if (!($_SESSION['admin_2fa_pending'] ?? false)) {
        header('Location: admin.php');
        exit;
    }
    $contact_input = trim($_POST['contact_input'] ?? '');

    // Look up admin by email
    $stmt = $conn->prepare("SELECT id, email FROM admins WHERE email = ? AND id = ?");
    $pending_id = $_SESSION['admin_2fa_id'] ?? 0;
    $stmt->bind_param("si", $contact_input, $pending_id);
    $stmt->execute();
    $fa_res = $stmt->get_result();

    if ($fa_res && $fa_res->num_rows > 0) {
        $fa_row  = $fa_res->fetch_assoc();
        require_once 'otp_helper.php';
        $otp_code = generateOTP(15);

        // Send via PHPMailer
        $mail_sent = false;
        try {
            require_once 'phpmailer/PHPMailer.php';
            require_once 'phpmailer/SMTP.php';
            require_once 'phpmailer/Exception.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'skybuildadmin@gmail.com';
            $mail->Password   = 'fvtmlonuiebnmrxe'; // replace with Gmail App Password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('skybuildadmin@gmail.com', 'SkyBuild Admin');
            $mail->addAddress($contact_input);
            $mail->Subject = 'SkyBuild Admin – Your Login OTP';
            $mail->Body    = "Your 2FA login code is: $otp_code\n\nThis code expires in 15 minutes.\n\nIf you did not attempt to log in, please change your password immediately.";
            $mail->send();
            $mail_sent = true;
        } catch (Exception $e) {
            error_log("2FA mail error: " . $e->getMessage());
        }

        $_SESSION['admin_2fa_contact'] = $contact_input;
        log_activity($conn, "2FA OTP Sent", "OTP sent to $contact_input");

        if ($mail_sent) {
            $action_msg = "OTP sent to {$contact_input}. Please check your inbox.";
            header('Location: admin.php?verify_2fa=1&step=2&msg=' . urlencode($action_msg));
            exit;
        } else {
            $_SESSION['admin_2fa_error'] = "Failed to send verification email. Please check your SMTP settings in admin.php.";
            header('Location: admin.php?verify_2fa=1');
            exit;
        }
    } else {
        $_SESSION['admin_2fa_error'] = "Email not found or does not match this account.";
        header('Location: admin.php?verify_2fa=1');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa_otp'])) {
    // 2FA Step 2: verify the entered OTP
    if (!($_SESSION['admin_2fa_pending'] ?? false)) {
        header('Location: admin.php');
        exit;
    }
    $entered  = trim($_POST['otp_code'] ?? '');
    $fa_email = $_SESSION['admin_2fa_contact'] ?? '';

    require_once 'otp_helper.php';
    $result = verifyOTP($entered);

    if ($result['status'] === true) {
        // 2FA passed — grant full access
        $_SESSION['admin_logged_in'] = true;
        unset($_SESSION['admin_2fa_pending'], $_SESSION['admin_2fa_id'],
              $_SESSION['admin_2fa_email'],   $_SESSION['admin_2fa_contact'],
              $_SESSION['admin_2fa_error']);
        log_activity($conn, "Login Success", "2FA verified for $fa_email; dashboard access granted");
        header('Location: admin.php');
        exit;
    } else {
        $_SESSION['admin_2fa_error'] = $result['message'];
        header('Location: admin.php?verify_2fa=1&step=2');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_security_questions'])) {
    $maiden = trim($_POST['maiden_name'] ?? '');
    $color = trim($_POST['fav_color'] ?? '');
    $dog = trim($_POST['dog_name'] ?? '');
    
    // Check against DB
    $stmt = $conn->prepare("SELECT security_maiden, security_color, security_dog FROM admins WHERE id = 1");
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if (strtolower($maiden) === strtolower($res['security_maiden']) && 
        strtolower($color) === strtolower($res['security_color']) && 
        strtolower($dog) === strtolower($res['security_dog'])) {
        
        $_SESSION['security_passed'] = true;
        log_activity($conn, "Security Verification Passed", "Correct answers provided");
        header('Location: admin.php?reset_mode=1');
        exit;
    } else {
        log_activity($conn, "Security Verification Failed", "Incorrect answers attempted");
        $error = "Incorrect answers to security questions.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    // OTP: generate and send via PHPMailer
    $otp_email = trim($_POST['otp_email'] ?? '');
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->bind_param("s", $otp_email);
    $stmt->execute();
    $otp_res = $stmt->get_result();
    if ($otp_res && $otp_res->num_rows > 0) {
        require_once 'otp_helper.php';
        $otp_code = generateOTP(15);
        
        // Send via PHPMailer
        $mail_sent = false;
        try {
            require_once 'phpmailer/PHPMailer.php';
            require_once 'phpmailer/SMTP.php';
            require_once 'phpmailer/Exception.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'skybuildadmin@gmail.com';
            $mail->Password = 'fvtmlonuiebnmrxe'; // Set your Gmail app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('skybuildadmin@gmail.com', 'SkyBuild Admin');
            $mail->addAddress($otp_email);
            $mail->Subject = 'Your SkyBuild OTP Code';
            $mail->Body = "Your one-time password (OTP) is: $otp_code\n\nThis code expires in 15 minutes.\n\nIf you did not request this, please ignore this email.";
            $mail->send();
            $mail_sent = true;
        } catch (Exception $e) {
            error_log("OTP mail error: " . $e->getMessage());
        }
        
        $_SESSION['otp_email'] = $otp_email;
        log_activity($conn, "OTP Sent", "OTP sent to $otp_email");
        if ($mail_sent) {
            $action_msg = "OTP sent to your email address. Please check your inbox.";
            header('Location: admin.php?forgot=1&otp_step=2&msg=' . urlencode($action_msg));
            exit;
        } else {
            $error = "Failed to send verification email. Please check your SMTP settings in admin.php.";
        }
    } else {
        $error = "Email address not found in our system.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp_code'] ?? '');
    $otp_email = $_SESSION['otp_email'] ?? '';
    
    require_once 'otp_helper.php';
    $result = verifyOTP($entered_otp);
    
    if ($result['status'] === true) {
        $_SESSION['security_passed'] = true;
        log_activity($conn, "OTP Verified", "OTP verified for $otp_email");
        header('Location: admin.php?reset_mode=1');
        exit;
    } else {
        $error = $result['message'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_security'])) {
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    if (($_SESSION['security_passed'] ?? false) !== true) {
        $error = "Security verification required.";
    } elseif (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^[A-Z0-9!@#$%^&*_\-\.]{8,}$/', $new_pass)) {
        $error = "Password must be all uppercase letters, numbers, or symbols (no lowercase).";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        // Update the main admin (id=1)
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = 1");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        
        log_activity($conn, "Password Reset", "Admin password successfully updated");
        unset($_SESSION['security_passed'], $_SESSION['otp_email']);
        $action_msg = "Password has been successfully reset. You can now log in.";
    }
}

if (isset($_GET['logout'])) {
    log_activity($conn, "Logout", "Admin logged out");
    session_destroy();
    header('Location: admin.php');
    exit;
}

$is_logged_in = $_SESSION['admin_logged_in'] ?? false;

// --- 3. Handle Actions (If Logged In) ---
$action_msg = $action_msg ?? '';
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_inventory'])) {
        $item_id = intval($_POST['item_id']);
        $item_name = trim($_POST['item_name']);
        $size = trim($_POST['size'] ?? '');
        $quantity = intval($_POST['quantity']);
        $unit = trim($_POST['unit'] ?? '');
        $price = floatval($_POST['unit_price'] ?? 0);
        $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, size = ?, quantity = ?, unit = ?, unit_price = ? WHERE id = ?");
        $stmt->bind_param("ssisdi", $item_name, $size, $quantity, $unit, $price, $item_id);
        $stmt->execute();
        log_activity($conn, "Update Inventory", "Item ID $item_id updated: name '$item_name'");
        $action_msg = "Inventory updated successfully.";
    } elseif (isset($_POST['add_inventory'])) {
        $item_name = trim($_POST['item_name']);
        $size = trim($_POST['size'] ?? '');
        $quantity = intval($_POST['quantity']);
        $unit = trim($_POST['unit'] ?? '');
        $price = floatval($_POST['unit_price'] ?? 0);
        if ($item_name) {
            $stmt = $conn->prepare("INSERT INTO inventory (item_name, size, quantity, unit, unit_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisd", $item_name, $size, $quantity, $unit, $price);
            $stmt->execute();
            log_activity($conn, "Add Inventory", "Added $item_name");
            $action_msg = "Item added to inventory.";
        }
    } elseif (isset($_POST['delete_inquiry'])) {
        $inq_id = intval($_POST['inquiry_id']);
        $stmt = $conn->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $inq_id);
        $stmt->execute();
        log_activity($conn, "Soft Delete Consultation", "Consultation ID $inq_id moved to trash");
        $action_msg = "Consultation moved to trash.";
    } elseif (isset($_POST['toggle_inquiry_status'])) {
        $inq_id = intval($_POST['inquiry_id']);
        $new_status = trim($_POST['status'] ?? 'unresolved');
        if (in_array($new_status, ['resolved', 'unresolved'])) {
            $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $inq_id);
            $stmt->execute();
            log_activity($conn, "Toggle Consultation Status", "Consultation ID $inq_id status updated to $new_status");
            $action_msg = "Consultation status updated to " . ucfirst($new_status) . ".";
        }
    } elseif (isset($_POST['bulk_delete_inquiries'])) {
        $ids = $_POST['inquiry_ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $ids_str = implode(',', $ids);
            $conn->query("UPDATE inquiries SET deleted_at = NOW() WHERE id IN ($ids_str)");
            log_activity($conn, "Bulk Soft Delete", count($ids) . " consultations moved to trash");
            $action_msg = count($ids) . " consultations moved to trash successfully.";
        }
    } elseif (isset($_POST['add_folder'])) {
        $folder_name = trim($_POST['folder_name']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        if ($folder_name) {
            $stmt = $conn->prepare("INSERT INTO quote_folders (parent_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $parent_id, $folder_name);
            $stmt->execute();
            log_activity($conn, "Add Folder", "Created folder '$folder_name'");
            $action_msg = "Folder created.";
        }
    } elseif (isset($_POST['add_quotation'])) {
        $folder_id = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
        $title = trim($_POST['title']);
        $po_number = trim($_POST['po_number'] ?? '');
        $signee_name = trim($_POST['signee_name'] ?? '');
        $grand_total = floatval($_POST['grand_total']);
        
        $stmt = $conn->prepare("INSERT INTO quotations (folder_id, title, po_number, signee_name, grand_total) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $folder_id, $title, $po_number, $signee_name, $grand_total);
        $stmt->execute();
        $quote_id = $conn->insert_id;

        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt_item = $conn->prepare("INSERT INTO quotation_items (quotation_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['items'] as $item) {
                $item_name = trim($item['name']);
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $total = floatval($item['total']);
                if ($item_name && $qty > 0) {
                    $stmt_item->bind_param("isidd", $quote_id, $item_name, $qty, $price, $total);
                    $stmt_item->execute();
                }
            }
        }
        log_activity($conn, "Add Quotation", "Created quotation '$title'");
        $action_msg = "Quotation saved successfully.";
    } elseif (isset($_POST['delete_folder'])) {
        $folder_id = intval($_POST['folder_id']);
        $stmt = $conn->prepare("UPDATE quote_folders SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $folder_id);
        $stmt->execute();
        log_activity($conn, "Soft Delete Folder", "Folder ID $folder_id moved to trash");
        $action_msg = "Folder moved to trash.";
    } elseif (isset($_POST['delete_quotation'])) {
        $quote_id = intval($_POST['quotation_id']);
        $stmt = $conn->prepare("UPDATE quotations SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $quote_id);
        $stmt->execute();
        log_activity($conn, "Soft Delete Quotation", "Quotation ID $quote_id moved to trash");
        $action_msg = "Quotation moved to trash.";
    } elseif (isset($_POST['add_event'])) {
        $event_date = $_POST['event_date'];
        $event_time = trim($_POST['event_time']);
        $title = trim($_POST['title']);
        $client_name = trim($_POST['client_name']);
        $description = trim($_POST['description']);
        $color = $_POST['color'];
        
        $stmt = $conn->prepare("INSERT INTO events (event_date, event_time, title, client_name, description, color) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $event_date, $event_time, $title, $client_name, $description, $color);
        $stmt->execute();
        log_activity($conn, "Add Event", "Added event '$title' for $event_date");
        $action_msg = "Event added to calendar.";
    } elseif (isset($_POST['delete_event'])) {
        $event_id = intval($_POST['event_id']);
        $stmt = $conn->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        log_activity($conn, "Soft Delete Event", "Event ID $event_id moved to trash");
        $action_msg = "Event moved to trash.";
    } elseif (isset($_POST['edit_event'])) {
        $event_id = intval($_POST['event_id']);
        $event_date = $_POST['event_date'];
        $event_time = trim($_POST['event_time']);
        $title = trim($_POST['title']);
        $client_name = trim($_POST['client_name']);
        $description = trim($_POST['description']);
        $color = $_POST['color'];
        
        $stmt = $conn->prepare("UPDATE events SET event_date=?, event_time=?, title=?, client_name=?, description=?, color=? WHERE id=?");
        $stmt->bind_param("ssssssi", $event_date, $event_time, $title, $client_name, $description, $color, $event_id);
        $stmt->execute();
        log_activity($conn, "Update Event", "Updated event '$title'");
        $action_msg = "Event updated.";
    } elseif (isset($_POST['add_showcase'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $image_path = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_name = 'project_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $new_name)) {
                $image_path = 'uploads/' . $new_name;
            }
        }

        if ($title && $image_path) {
            $stmt = $conn->prepare("INSERT INTO showcase (title, description, image_path) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $description, $image_path);
            $stmt->execute();
            $action_msg = "Project added to showcase.";
        } else {
            $action_msg = "Error: Title and Image are required.";
        }
    } elseif (isset($_POST['edit_showcase'])) {
        $proj_id = intval($_POST['project_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $image_path = $_POST['existing_image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_name = 'project_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $new_name)) {
                $image_path = 'uploads/' . $new_name;
                // Optional: delete old image if it was in uploads/
                if (strpos($_POST['existing_image'], 'uploads/') === 0 && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            }
        }

        $stmt = $conn->prepare("UPDATE showcase SET title=?, description=?, image_path=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $description, $image_path, $proj_id);
        $stmt->execute();
        log_activity($conn, "Update Showcase", "Updated project '$title'");
        $action_msg = "Project updated.";
    } elseif (isset($_POST['delete_showcase'])) {
        $proj_id = intval($_POST['project_id']);
        $stmt = $conn->prepare("UPDATE showcase SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $proj_id);
        $stmt->execute();
        log_activity($conn, "Soft Delete Showcase", "Project ID $proj_id moved to trash");
        $action_msg = "Project moved to trash.";
    } elseif (isset($_POST['move_item'])) {
        $item_id = intval($_POST['item_id']);
        $item_type = $_POST['item_type'];
        $target_folder_id = $_POST['target_folder_id'] === 'root' ? null : intval($_POST['target_folder_id']);
        
        if ($item_type === 'folder') {
            if ($item_id !== $target_folder_id) {
                $stmt = $conn->prepare("UPDATE quote_folders SET parent_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $target_folder_id, $item_id);
                $stmt->execute();
                $action_msg = "Folder moved.";
            }
        } else {
            $stmt = $conn->prepare("UPDATE quotations SET folder_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $target_folder_id, $item_id);
            $stmt->execute();
            $action_msg = "Quotation moved.";
        }
    } elseif (isset($_POST['restore_item'])) {
        $type = $_POST['item_type'];
        $id = intval($_POST['item_id']);
        $table = '';
        if ($type === 'inquiry') $table = 'inquiries';
        elseif ($type === 'inventory') $table = 'inventory';
        elseif ($type === 'folder') $table = 'quote_folders';
        elseif ($type === 'quotation') $table = 'quotations';
        elseif ($type === 'event') $table = 'events';
        elseif ($type === 'showcase') $table = 'showcase';
        
        if ($table) {
            $stmt = $conn->prepare("UPDATE $table SET deleted_at = NULL WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            log_activity($conn, "Restore Item", "Restored $type ID $id from trash");
            $action_msg = "Item restored successfully.";
        }
    } elseif (isset($_POST['permanent_delete'])) {
        $type = $_POST['item_type'];
        $id = intval($_POST['item_id']);
        $table = '';
        if ($type === 'inquiry') $table = 'inquiries';
        elseif ($type === 'inventory') $table = 'inventory';
        elseif ($type === 'folder') $table = 'quote_folders';
        elseif ($type === 'quotation') $table = 'quotations';
        elseif ($type === 'event') $table = 'events';
        elseif ($type === 'showcase') $table = 'showcase';
        
        if ($table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            log_activity($conn, "Permanent Delete", "Permanently deleted $type ID $id");
            $action_msg = "Item permanently deleted.";
        }
    } elseif (isset($_POST['bulk_trash_restore'])) {
        $items = $_POST['trash_items'] ?? [];
        $count = 0;
        foreach ($items as $item_val) {
            list($type, $id) = explode(':', $item_val);
            $id = intval($id);
            $table = '';
            if ($type === 'inquiry') $table = 'inquiries';
            elseif ($type === 'inventory') $table = 'inventory';
            elseif ($type === 'folder') $table = 'quote_folders';
            elseif ($type === 'quotation') $table = 'quotations';
            elseif ($type === 'event') $table = 'events';
            elseif ($type === 'showcase') $table = 'showcase';
            
            if ($table) {
                $conn->query("UPDATE $table SET deleted_at = NULL WHERE id = $id");
                $count++;
            }
        }
        log_activity($conn, "Bulk Restore", "$count items restored from trash");
        $action_msg = "$count items restored successfully.";
    } elseif (isset($_POST['bulk_trash_delete'])) {
        $items = $_POST['trash_items'] ?? [];
        $count = 0;
        foreach ($items as $item_val) {
            list($type, $id) = explode(':', $item_val);
            $id = intval($id);
            $table = '';
            if ($type === 'inquiry') $table = 'inquiries';
            elseif ($type === 'inventory') $table = 'inventory';
            elseif ($type === 'folder') $table = 'quote_folders';
            elseif ($type === 'quotation') $table = 'quotations';
            elseif ($type === 'event') $table = 'events';
            elseif ($type === 'showcase') $table = 'showcase';
            
            if ($table) {
                $conn->query("DELETE FROM $table WHERE id = $id");
                $count++;
            }
        }
        log_activity($conn, "Bulk Permanent Delete", "$count items permanently deleted");
        $action_msg = "$count items permanently deleted.";
    } elseif (isset($_POST['generate_from_estimate'])) {
        // Auto-generate a quotation from estimator parameters
        $est_type  = $_POST['est_type']  ?? 'electrical';
        $est_area  = floatval($_POST['est_area']  ?? 0);
        $est_title = trim($_POST['est_title'] ?? 'Auto-Generated Quotation');
        $est_folder = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;

        // Material templates per project type (item_name, qty_per_sqm)
        $templates = [
            'electrical' => [
                ['name' => 'THHN/THWN Wire',                  'per_sqm' => 0.50],
                ['name' => 'PVC Electrical Conduit',           'per_sqm' => 0.35],
                ['name' => 'Convenience Outlet (Universal)',   'per_sqm' => 0.12],
                ['name' => 'Circuit Breaker',                  'per_sqm' => 0.03],
                ['name' => 'Lighting Panel Board (8-branch)', 'per_sqm' => 0.005],
            ],
            'roofing' => [
                ['name' => 'G.I. Corrugated Roofing Sheet',   'per_sqm' => 0.13],
                ['name' => 'G.I. C-Purlin',                   'per_sqm' => 0.06],
                ['name' => 'G.I. Ridgecap',                   'per_sqm' => 0.04],
                ['name' => 'Tekscrew (Self-drilling)',         'per_sqm' => 0.30],
            ],
            'renovation' => [
                ['name' => 'Portland Cement (Type I)',         'per_sqm' => 0.30],
                ['name' => 'Ceramic Floor Tile',               'per_sqm' => 1.10],
                ['name' => 'Marine Plywood',                   'per_sqm' => 0.04],
                ['name' => 'Good Lumber',                      'per_sqm' => 0.15],
                ['name' => 'Flat Latex Paint',                 'per_sqm' => 0.05],
                ['name' => 'PVC Pipe (Orange, Pressure)',      'per_sqm' => 0.08],
            ],
            'full_construction' => [
                ['name' => 'Portland Cement (Type I)',         'per_sqm' => 0.40],
                ['name' => 'CHB / Hollow Block',               'per_sqm' => 12.00],
                ['name' => 'Deformed Bar (Rebar)',             'per_sqm' => 0.06],
                ['name' => 'Washed Sand',                      'per_sqm' => 0.08],
                ['name' => 'Crushed Gravel (3/4 inch)',        'per_sqm' => 0.06],
                ['name' => 'Marine Plywood',                   'per_sqm' => 0.02],
                ['name' => 'Good Lumber',                      'per_sqm' => 0.08],
                ['name' => 'G.I. Corrugated Roofing Sheet',   'per_sqm' => 0.05],
                ['name' => 'THHN/THWN Wire',                   'per_sqm' => 0.10],
                ['name' => 'PVC Pipe (Orange, Pressure)',      'per_sqm' => 0.06],
                ['name' => 'Ceramic Floor Tile',               'per_sqm' => 1.00],
            ],
        ];

        if ($est_area > 0 && isset($templates[$est_type])) {
            // Fetch prices from inventory
            $res_inv2 = $conn->query("SELECT item_name, unit_price FROM inventory WHERE deleted_at IS NULL ORDER BY item_name ASC");
            $inv_prices = [];
            while ($rr = $res_inv2->fetch_assoc()) {
                $inv_prices[$rr['item_name']] = floatval($rr['unit_price']);
            }

            $grand_total = 0;
            $line_items  = [];
            foreach ($templates[$est_type] as $tpl) {
                $qty   = max(1, intval(ceil($est_area * $tpl['per_sqm'])));
                $price = 0;
                if (isset($inv_prices[$tpl['name']])) {
                    $price = $inv_prices[$tpl['name']];
                } else {
                    foreach ($inv_prices as $iname => $iprice) {
                        if (stripos($iname, $tpl['name']) !== false) {
                            $price = $iprice;
                            break;
                        }
                    }
                }
                $total        = $qty * $price;
                $grand_total += $total;
                $line_items[] = ['name' => $tpl['name'], 'qty' => $qty, 'price' => $price, 'total' => $total];
            }

            $po_num  = 'AUTO-' . strtoupper(substr($est_type, 0, 4)) . '-' . date('Ymd');
            $signee  = 'NATH Hardware and Construction Supplies';
            $stmt = $conn->prepare("INSERT INTO quotations (folder_id, title, po_number, signee_name, grand_total) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $est_folder, $est_title, $po_num, $signee, $grand_total);
            $stmt->execute();
            $new_quote_id = $conn->insert_id;

            $stmt_item = $conn->prepare("INSERT INTO quotation_items (quotation_id, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($line_items as $li) {
                $stmt_item->bind_param("isidd", $new_quote_id, $li['name'], $li['qty'], $li['price'], $li['total']);
                $stmt_item->execute();
            }

            log_activity($conn, "Auto-Generate Quotation", "Generated '$est_title' ({$est_type}, {$est_area}sqm)");
            header('Location: admin.php?tab=quotations&view_quote=' . $new_quote_id . '&gen_success=1');
            exit;
        } else {
            $action_msg = "Invalid estimate parameters.";
        }
    } elseif (isset($_POST['create_purchase_order'])) {
        $quote_id = intval($_POST['quotation_id']);
        $po_notes = trim($_POST['po_notes'] ?? '');
        $po_num   = 'PO-' . date('Ymd') . '-' . str_pad($quote_id, 4, '0', STR_PAD_LEFT);
        $po_date  = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO purchase_orders (quotation_id, po_number, po_date, status, notes) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param("isss", $quote_id, $po_num, $po_date, $po_notes);
        $stmt->execute();
        log_activity($conn, "Create Purchase Order", "PO $po_num created for Quotation ID $quote_id");
        header('Location: admin.php?tab=quotations&view_quote=' . $quote_id . '&po_created=1');
        exit;
    } elseif (isset($_POST['generate_invoice'])) {
        $quote_id = intval($_POST['quotation_id']);
        $po_id    = !empty($_POST['po_id']) ? intval($_POST['po_id']) : null;
        $inv_notes = trim($_POST['inv_notes'] ?? '');
        $inv_num  = 'INV-' . date('Ymd') . '-' . str_pad($quote_id, 4, '0', STR_PAD_LEFT);
        $inv_date = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO sales_invoices (po_id, quotation_id, invoice_number, invoice_date, status, inventory_deducted, notes) VALUES (?, ?, ?, ?, 'issued', 0, ?)");
        $stmt->bind_param("iisss", $po_id, $quote_id, $inv_num, $inv_date, $inv_notes);
        $stmt->execute();
        $new_inv_id = $conn->insert_id;

        // Deduct inventory quantities
        $res_qi = $conn->prepare("SELECT item_name, quantity FROM quotation_items WHERE quotation_id = ?");
        $res_qi->bind_param("i", $quote_id);
        $res_qi->execute();
        $q_items = $res_qi->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($q_items as $qi) {
            $inv = find_inventory_item_for_quote_item($conn, $qi['item_name']);
            if ($inv) {
                $new_qty = max(0, $inv['quantity'] - intval($qi['quantity']));
                $stmt_upd = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $inv_id = intval($inv['id']);
                $stmt_upd->bind_param("ii", $new_qty, $inv_id);
                $stmt_upd->execute();
            }
        }
        $conn->query("UPDATE sales_invoices SET inventory_deducted = 1 WHERE id = $new_inv_id");
        log_activity($conn, "Generate Invoice", "Invoice $inv_num for Quotation ID $quote_id; inventory deducted");
        header('Location: admin.php?tab=quotations&view_quote=' . $quote_id . '&inv_id=' . $new_inv_id);
        exit;
    } elseif (isset($_POST['retract_invoice'])) {
        $quote_id = intval($_POST['quotation_id']);
        $invoice_id = intval($_POST['invoice_id']);

        $stmt_inv = $conn->prepare("SELECT id, invoice_number, inventory_deducted FROM sales_invoices WHERE id = ? AND quotation_id = ? LIMIT 1");
        $stmt_inv->bind_param("ii", $invoice_id, $quote_id);
        $stmt_inv->execute();
        $invoice = $stmt_inv->get_result()->fetch_assoc();

        if ($invoice) {
            $conn->begin_transaction();
            try {
                if (intval($invoice['inventory_deducted']) === 1) {
                    $res_qi = $conn->prepare("SELECT item_name, quantity FROM quotation_items WHERE quotation_id = ?");
                    $res_qi->bind_param("i", $quote_id);
                    $res_qi->execute();
                    $q_items = $res_qi->get_result()->fetch_all(MYSQLI_ASSOC);

                    foreach ($q_items as $qi) {
                        $inv = find_inventory_item_for_quote_item($conn, $qi['item_name']);
                        if ($inv) {
                            $restored_qty = intval($inv['quantity']) + intval($qi['quantity']);
                            $stmt_upd = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                            $inv_id = intval($inv['id']);
                            $stmt_upd->bind_param("ii", $restored_qty, $inv_id);
                            $stmt_upd->execute();
                        }
                    }
                }

                $stmt_del = $conn->prepare("DELETE FROM sales_invoices WHERE id = ? AND quotation_id = ?");
                $stmt_del->bind_param("ii", $invoice_id, $quote_id);
                $stmt_del->execute();

                $conn->commit();
                log_activity($conn, "Retract Invoice", "Invoice {$invoice['invoice_number']} for Quotation ID $quote_id retracted; inventory restored");
                header('Location: admin.php?tab=quotations&view_quote=' . $quote_id . '&inv_retracted=1');
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $action_msg = "Invoice retraction failed. Please try again.";
            }
        } else {
            $action_msg = "Invoice was not found.";
        }
    } elseif (isset($_POST['delete_pre_quotation'])) {
        $pre_id = intval($_POST['pre_quote_id']);
        $stmt = $conn->prepare("DELETE FROM pre_quotations WHERE id = ?");
        $stmt->bind_param("i", $pre_id);
        $stmt->execute();
        log_activity($conn, "Delete Pre-Quotation", "Deleted customer pre-quotation ID $pre_id");
        $action_msg = "Pre-quotation deleted successfully.";
    }
}


// Fetch Data
$inquiries = [];
$inventory = [];
$unread_count = 0;
$unviewed_pre_count = 0;
$active_tab = $_GET['tab'] ?? 'consultations';

if ($is_logged_in) {
    if ($active_tab === 'consultations') {
        $conn->query("UPDATE inquiries SET is_read = 1 WHERE is_read = 0 AND deleted_at IS NULL");
    }

    $res = $conn->query("SELECT COUNT(*) AS unread FROM inquiries WHERE is_read = 0 AND deleted_at IS NULL");
    if ($res) {
        $unread_count = $res->fetch_assoc()['unread'];
    }

    // Get count of unviewed customer pre-quotations (both drafts and submitted)
    $unviewed_pre_count = 0;
    $unviewed_draft_count = 0;
    
    $res_unviewed_pre = $conn->query("SELECT COUNT(*) AS cnt FROM pre_quotations WHERE is_viewed = 0 AND status = 'submitted'");
    if ($res_unviewed_pre) {
        $unviewed_pre_count = $res_unviewed_pre->fetch_assoc()['cnt'];
    }
    
    $res_unviewed_draft = $conn->query("SELECT COUNT(*) AS cnt FROM pre_quotations WHERE is_viewed = 0 AND status = 'draft'");
    if ($res_unviewed_draft) {
        $unviewed_draft_count = $res_unviewed_draft->fetch_assoc()['cnt'];
    }
    
    $total_unviewed_quotations = $unviewed_pre_count + $unviewed_draft_count;

    if ($active_tab === 'quotations') {
        // Mark all as viewed when admin visits quotations tab
        $conn->query("UPDATE pre_quotations SET is_viewed = 1 WHERE is_viewed = 0");
    }

    // Tab-specific data fetching
    if ($active_tab === 'consultations') {
        // Get consultation counts
        $cnt_unresolved = 0;
        $cnt_resolved = 0;
        
        $res_unresolved = $conn->query("SELECT COUNT(*) AS cnt FROM inquiries WHERE status = 'unresolved' AND deleted_at IS NULL");
        if ($res_unresolved) $cnt_unresolved = $res_unresolved->fetch_assoc()['cnt'];
        
        $res_resolved = $conn->query("SELECT COUNT(*) AS cnt FROM inquiries WHERE status = 'resolved' AND deleted_at IS NULL");
        if ($res_resolved) $cnt_resolved = $res_resolved->fetch_assoc()['cnt'];
        
        $filter_status = $_GET['status_filter'] ?? 'all';
        $query = "SELECT * FROM inquiries WHERE deleted_at IS NULL";
        if ($filter_status === 'resolved') {
            $query .= " AND status = 'resolved'";
        } elseif ($filter_status === 'unresolved') {
            $query .= " AND status = 'unresolved'";
        }
        $query .= " ORDER BY created_at DESC";
        
        $res = $conn->query($query);
        $inquiries = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        
    } elseif ($active_tab === 'inventory') {
        $sort = $_GET['sort'] ?? 'item_name';
        $dir = $_GET['dir'] ?? 'ASC';
        
        $allowed_sort = ['item_name', 'updated_at', 'quantity', 'unit_price'];
        if (!in_array($sort, $allowed_sort)) $sort = 'item_name';
        $allowed_dir = ['ASC', 'DESC'];
        if (!in_array($dir, $allowed_dir)) $dir = 'ASC';

        $res = $conn->query("SELECT * FROM inventory WHERE deleted_at IS NULL ORDER BY $sort $dir");
        $inventory = $res->fetch_all(MYSQLI_ASSOC);
    } elseif ($active_tab === 'showcase') {
        $res = $conn->query("SELECT * FROM showcase WHERE deleted_at IS NULL ORDER BY created_at DESC");
        $showcase = $res->fetch_all(MYSQLI_ASSOC);
    } elseif ($active_tab === 'quotations') {
        // Fetch customer pre-quotations (submitted)
        $res_pre = $conn->query("SELECT * FROM pre_quotations WHERE status = 'submitted' ORDER BY generated_at DESC");
        $pre_quotations = $res_pre ? $res_pre->fetch_all(MYSQLI_ASSOC) : [];
        
        // Fetch customer drafts
        $res_drafts = $conn->query("SELECT * FROM pre_quotations WHERE status = 'draft' ORDER BY generated_at DESC");
        $draft_quotations = $res_drafts ? $res_drafts->fetch_all(MYSQLI_ASSOC) : [];

        // Fetch inventory for autocomplete
        $res_inv = $conn->query("SELECT item_name, size, unit_price FROM inventory WHERE deleted_at IS NULL ORDER BY item_name ASC");
        $inventory = $res_inv->fetch_all(MYSQLI_ASSOC);
        
        $folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : null;
        
        $folder = null;
        if ($folder_id) {
            $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $folder_id);
            $stmt->execute();
            $folder = $stmt->get_result()->fetch_assoc();
        }

        $breadcrumbs = [];
        $curr = $folder;
        while ($curr && $curr['parent_id']) {
            $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $curr['parent_id']);
            $stmt->execute();
            $curr = $stmt->get_result()->fetch_assoc();
            if ($curr) array_unshift($breadcrumbs, $curr);
        }

        if ($folder_id) {
            $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE parent_id = ? AND deleted_at IS NULL ORDER BY name ASC");
            $stmt->bind_param("i", $folder_id);
            $stmt->execute();
            $folders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $stmt = $conn->prepare("SELECT * FROM quotations WHERE folder_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
            $stmt->bind_param("i", $folder_id);
            $stmt->execute();
            $quotations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $res = $conn->query("SELECT * FROM quote_folders WHERE parent_id IS NULL AND deleted_at IS NULL ORDER BY name ASC");
            $folders = $res->fetch_all(MYSQLI_ASSOC);
            
            $res = $conn->query("SELECT * FROM quotations WHERE folder_id IS NULL AND deleted_at IS NULL ORDER BY created_at DESC");
            $quotations = $res->fetch_all(MYSQLI_ASSOC);
        }
        
        // Fetch a specific quote if requested
        $view_quote = null;
        $view_quote_items = [];
        $view_quote_po = null;
        $view_quote_invoice = null;
        if (isset($_GET['view_quote'])) {
            $quote_id = intval($_GET['view_quote']);
            $stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $quote_id);
            $stmt->execute();
            $view_quote = $stmt->get_result()->fetch_assoc();
            
            if ($view_quote) {
                $stmt = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
                $stmt->bind_param("i", $quote_id);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $view_quote_items[] = $row;

                // Fetch linked PO
                $stmt_po = $conn->prepare("SELECT * FROM purchase_orders WHERE quotation_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt_po->bind_param("i", $quote_id);
                $stmt_po->execute();
                $view_quote_po = $stmt_po->get_result()->fetch_assoc();

                // Fetch linked Invoice
                $stmt_inv = $conn->prepare("SELECT * FROM sales_invoices WHERE quotation_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt_inv->bind_param("i", $quote_id);
                $stmt_inv->execute();
                $view_quote_invoice = $stmt_inv->get_result()->fetch_assoc();
            }
        }
    } elseif ($active_tab === 'calendar') {
        $cal_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $cal_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        $start_date = date("Y-m-01", strtotime("$cal_year-$cal_month-01"));
        $end_date = date("Y-m-t", strtotime($start_date));
        
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_date >= ? AND event_date <= ? AND deleted_at IS NULL ORDER BY event_date ASC, event_time ASC");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        $events = [];
        while ($row = $res->fetch_assoc()) {
            $events[$row['event_date']][] = $row;
        }
    } elseif ($active_tab === 'logs') {
        $log_date = $_GET['log_date'] ?? '';
        if ($log_date) {
            $stmt = $conn->prepare("SELECT * FROM activity_logs WHERE DATE(created_at) = ? ORDER BY created_at DESC LIMIT 500");
            $stmt->bind_param("s", $log_date);
            $stmt->execute();
            $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $res = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
            $logs = $res->fetch_all(MYSQLI_ASSOC);
        }
    } elseif ($active_tab === 'trash') {
        $trash_items = [];
        $tables = [
            'inquiry' => 'inquiries',
            'inventory' => 'inventory',
            'folder' => 'quote_folders',
            'quotation' => 'quotations',
            'event' => 'events',
            'showcase' => 'showcase'
        ];
        foreach ($tables as $type => $table) {
            $res = $conn->query("SELECT *, '$type' as item_type FROM $table WHERE deleted_at IS NOT NULL");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $trash_items[] = $row;
                }
            }
        }
        usort($trash_items, function($a, $b) {
            return strcmp($b['deleted_at'], $a['deleted_at']);
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - SkyBuild</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: var(--bg); 
            margin: 0; 
            font-family: var(--font);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
        }
        .login-box { 
            background: var(--surface); 
            padding: 40px; 
            border-radius: var(--radius); 
            border: 1px solid var(--border); 
            width: 100%; 
            max-width: 400px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        }
        .login-box h1 { 
            font-size: 24px; 
            margin-bottom: 24px; 
            text-align: center; 
            font-weight: 300;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { 
            display: block; 
            font-size: 11px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            color: var(--muted); 
            margin-bottom: 7px; 
        }
        input.text-input, input.num-input { 
            width: 100%; 
            padding: 10px 13px; 
            border: 1px solid var(--border); 
            border-radius: var(--radius); 
            font-family: var(--font); 
            font-size: 14px; 
            background: #fff; 
            color: var(--text); 
            outline: none; 
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input.text-input:focus, input.num-input:focus { 
            border-color: var(--text); 
            box-shadow: 0 0 0 3px rgba(28,24,20,0.08); 
        }
        .btn { 
            padding: 10px 16px; 
            text-align: center; 
            display: inline-block; 
            border-radius: var(--radius);
            font-size: 13px;
            cursor: pointer;
            border: none;
            background: var(--text);
            color: #fff;
            transition: background 0.2s;
        }
        .btn:hover { background: #333; }
        .btn-full { width: 100%; margin-top: 24px; }
        .alert-error, .alert-success { 
            padding: 14px 16px; 
            border-radius: var(--radius); 
            font-size: 13px; 
            margin-bottom: 20px; 
        }
        .alert-error { border: 1px solid #f5c6c6; background: #fdf5f5; color: #7a2020; }
        .alert-success { border: 1px solid #c3e6c3; background: #f4fbf4; color: #2a5e2a; }
        
        .dashboard { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 60px 20px; 
            width: 100%; 
        }
        .dashboard-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }
        .dashboard-header h1 { margin: 0; font-size: 28px; }
        
        .tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        .tabs::-webkit-scrollbar { display: none; }
        .tabs a {
            text-decoration: none;
            color: var(--muted);
            padding: 8px 14px;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 13px;
            transition: background 0.2s, color 0.2s;
            flex-shrink: 0;
        }
        .tabs a:hover { background: var(--surface); color: var(--text); }
        .tabs a.active { background: var(--text); color: var(--cream); }

        .table-wrap {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); font-weight: 600; background: rgba(0,0,0,0.03); }
        tr:last-child td { border-bottom: none; }
        .consultations-table-wrap { overflow-x: hidden; overflow-y: visible; }
        .consultations-table { width: 100%; table-layout: fixed; }
        .consultations-table th,
        .consultations-table td { padding: 10px 8px; font-size: 12px; line-height: 1.45; }
        .consultations-table th { font-size: 10px; letter-spacing: 1.2px; }
        .consultations-table th:nth-child(1), .consultations-table td:nth-child(1) { width: 3%; text-align: center; }
        .consultations-table th:nth-child(2), .consultations-table td:nth-child(2) { width: 10%; }
        .consultations-table th:nth-child(3), .consultations-table td:nth-child(3) { width: 12%; }
        .consultations-table th:nth-child(4), .consultations-table td:nth-child(4) { width: 20%; }
        .consultations-table th:nth-child(5), .consultations-table td:nth-child(5) { width: 12%; }
        .consultations-table th:nth-child(6), .consultations-table td:nth-child(6) { width: 9%; }
        .consultations-table th:nth-child(7), .consultations-table td:nth-child(7) { width: 22%; }
        .consultations-table th:nth-child(8), .consultations-table td:nth-child(8) { width: 12%; }
        .consultations-table td { vertical-align: middle; overflow-wrap: anywhere; word-break: break-word; }
        .consultation-status-cell { white-space: normal !important; overflow: visible; }
        .consultation-status-actions { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; min-width: 0; }
        .status-pill { border-radius: 12px; display: inline-block; font-size: 10px; font-weight: 700; line-height: 1; padding: 5px 7px; text-transform: uppercase; white-space: normal; }
        .status-pill.resolved { background:#e6f4ea; color:#137333; border:1px solid #a3e2bc; }
        .status-pill.unresolved { background:#fce8e6; color:#c5221f; border:1px solid #fad2cf; }
        .resolve-btn { background:#10b981; border:none; border-radius: var(--radius); color:#fff; cursor:pointer; display:inline-flex; align-items:center; gap:3px; font-family:var(--font); font-size:10px; font-weight:700; line-height:1; padding:6px 7px; white-space: normal; }
        .mark-unresolved-btn { background:none; border:none; color:var(--muted); cursor:pointer; font-family:var(--font); font-size:10px; padding:0; text-align:left; text-decoration:underline; white-space: normal; }
        
        .inv-form { display: flex; gap: 8px; align-items: center; margin: 0; flex-wrap: wrap; }
        .inv-form input.num-input { width: 80px; padding: 8px; }
        .inv-form .btn { padding: 8px 12px; }
        
        .add-item-box {
            background: var(--surface);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-top: 24px;
            display: block;
            width: 100%;
            box-sizing: border-box;
        }
        .add-item-box h3 { margin-bottom: 16px; font-size: 16px; font-weight: 500; margin-top: 0; }
        .add-item-form { display: flex; gap: 16px; align-items: flex-end; margin: 0; flex-wrap: wrap; }
        .add-item-form .form-group { min-width: 120px; flex: 1; }

        .dashboard-header { flex-wrap: wrap; gap: 12px; }

        @media (max-width: 600px) {
    .dashboard { padding: 30px 14px; }
    .dashboard-header h1 { font-size: 20px; }
    .add-item-form { flex-direction: column; align-items: stretch; }
    .add-item-form .form-group { min-width: unset; }

    .logs-filter-box {
    margin-top: 24px !important;
    margin-bottom: 40px !important;
}

.logs-filter-box form {
    width: 100% !important;
}

.logs-filter-box .form-group {
    width: 100% !important;
}

.logs-filter-box input[type="date"] {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    display: block !important;
}

.logs-filter-box form > div:last-child {
    width: 100% !important;
    display: block !important;
}

.logs-filter-box form > div:last-child button,
.logs-filter-box form > div:last-child a {
    width: 100% !important;
    max-width: 100% !important;
    flex: none !important;
    box-sizing: border-box !important;
    display: block !important;
}
        }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <div class="login-wrapper">
        <div class="login-box">

            <?php
            // 2FA screen — shown after password is verified, before dashboard access
            $show_2fa = isset($_GET['verify_2fa']) && ($_SESSION['admin_2fa_pending'] ?? false);
            $fa_step  = intval($_GET['step'] ?? 1);
            $fa_msg   = htmlspecialchars(urldecode($_GET['msg'] ?? ''));
            $fa_err   = htmlspecialchars($_SESSION['admin_2fa_error'] ?? '');
            unset($_SESSION['admin_2fa_error']);
            ?>

            <?php if ($show_2fa): ?>

                <?php if ($fa_step === 2): ?>
                    <!-- Step 2: Enter OTP code -->
                    <div style="text-align:center; margin-bottom: 24px;">
                        <div style="width:56px; height:56px; background:#1c1814; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                            <svg width="26" height="26" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                        </div>
                        <h1 style="font-size:22px; margin:0 0 6px;">Enter OTP Code</h1>
                        <p style="font-size:13px; color:var(--muted); margin:0;">A 6-digit code was sent to your email.<br>Enter it below to access the dashboard.</p>
                    </div>
                    <?php if ($fa_err): ?>
                        <div class="alert-error no-hide" style="margin-bottom:16px;"><?php echo $fa_err; ?></div>
                    <?php endif; ?>
                    <?php if ($fa_msg): ?>
                        <div class="alert-success no-hide" style="margin-bottom:16px;"><?php echo $fa_msg; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="admin.php?verify_2fa=1&step=2">
                        <input type="hidden" name="verify_2fa_otp" value="1">
                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="text-align:center; display:block; margin-bottom:8px;">6-Digit Code</label>
                            <input type="text" name="otp_code" class="text-input" maxlength="6" required autofocus
                                   style="font-size:28px; letter-spacing:10px; text-align:center; padding:14px;"
                                   autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}">
                        </div>
                        <button type="submit" class="btn btn-full">Verify &amp; Access Dashboard</button>
                    </form>
                    <div style="margin-top:16px; text-align:center;">
                        <a href="admin.php?verify_2fa=1" style="color:var(--muted); font-size:13px; text-decoration:none;">← Resend / Change Email</a>
                    </div>

                <?php else: ?>
                    <!-- Step 1: Enter email to receive OTP -->
                    <div style="text-align:center; margin-bottom: 24px;">
                        <div style="width:56px; height:56px; background:#1c1814; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                            <svg width="26" height="26" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <h1 style="font-size:22px; margin:0 0 6px;">Two-Factor Verification</h1>
                        <p style="font-size:13px; color:var(--muted); margin:0;">Enter your registered email address.<br>We'll send a one-time code to confirm it's you.</p>
                    </div>
                    <?php if ($fa_err): ?>
                        <div class="alert-error" style="margin-bottom:16px;"><?php echo $fa_err; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="admin.php?verify_2fa=1">
                        <input type="hidden" name="send_2fa_otp" value="1">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="contact_input">Admin Email Address</label>
                            <input type="email" id="contact_input" name="contact_input" class="text-input" required autofocus
                                   placeholder="<?php echo htmlspecialchars($_SESSION['admin_2fa_email'] ?? 'your@email.com'); ?>"
                                   value="<?php echo htmlspecialchars($_SESSION['admin_2fa_email'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-full">Send OTP Code</button>
                    </form>
                    <div style="margin-top:16px; text-align:center; font-size:12px; color:var(--muted);">
                        ✓ Password verified &nbsp;·&nbsp;
                        <a href="admin.php" style="color:var(--muted); text-decoration:none;">Cancel &amp; back to login</a>
                    </div>
                <?php endif; ?>

            <?php elseif (isset($_GET['reset_mode']) && ($_SESSION['security_passed'] ?? false)): ?>
                <h1>Set New Password</h1>
                <p style="font-size: 13px; color: var(--muted); margin-bottom: 20px;">Password must be <strong>at least 8 characters</strong> and <strong>all uppercase</strong> (letters, numbers, or symbols).</p>
                <?php if ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="admin.php" id="resetForm">
                    <input type="hidden" name="reset_password_security" value="1">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="text-input" required
                               oninput="checkPassStrength(this.value)" autocomplete="new-password">
                        <div id="passStrength" style="margin-top: 6px; font-size: 12px; display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="text-input" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-full">Update Password</button>
                </form>
                <script>
                function checkPassStrength(val) {
                    const el = document.getElementById('passStrength');
                    el.style.display = 'block';
                    if (val.length === 0) { el.style.display = 'none'; return; }
                    const isLong = val.length >= 8;
                    const isUpper = /^[A-Z0-9!@#$%^&*_\-\.]+$/.test(val);
                    if (isLong && isUpper) {
                        el.textContent = '✓ Strong – meets all requirements';
                        el.style.color = '#2a5e2a';
                    } else if (!isLong) {
                        el.textContent = '✗ Too short – need at least 8 characters';
                        el.style.color = '#7a2020';
                    } else {
                        el.textContent = '✗ Must be ALL UPPERCASE (no lowercase letters)';
                        el.style.color = '#7a2020';
                    }
                }
                </script>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="admin.php" style="color: var(--muted); font-size: 13px; text-decoration: none;">Back to login</a>
                </div>

            <?php elseif (isset($_GET['forgot'])): ?>
                <?php
                $otp_step = intval($_GET['otp_step'] ?? 1);
                $msg_param = htmlspecialchars(urldecode($_GET['msg'] ?? ''));
                ?>
                <?php if ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($msg_param): ?>
                    <div class="alert-success"><?php echo $msg_param; ?></div>
                <?php endif; ?>

                <?php if ($otp_step == 2): ?>
                    <h1>Enter OTP Code</h1>
                    <p style="font-size: 13px; color: var(--muted); margin-bottom: 18px;">A 6-digit code was sent to your email. Enter it below to continue.</p>
                    <form method="POST" action="admin.php?forgot=1&otp_step=2">
                        <input type="hidden" name="verify_otp" value="1">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>6-Digit OTP Code</label>
                            <input type="text" name="otp_code" class="text-input" maxlength="6" required
                                   style="font-size: 24px; letter-spacing: 8px; text-align: center;" autocomplete="one-time-code">
                        </div>
                        <button type="submit" class="btn btn-full">Verify Code</button>
                    </form>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="admin.php?forgot=1" style="color: var(--muted); font-size: 13px; text-decoration: none;">Back / Resend OTP</a>
                    </div>
                <?php else: ?>
                    <h1>Forgot Password</h1>
                    <p style="font-size: 13px; color: var(--muted); margin-bottom: 18px;">Enter your admin email to receive an OTP code.</p>
                    <form method="POST" action="admin.php?forgot=1">
                        <input type="hidden" name="send_otp" value="1">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Admin Email Address</label>
                            <input type="email" name="otp_email" class="text-input" required placeholder="skybuildadmin@gmail.com">
                        </div>
                        <button type="submit" class="btn btn-full">Send OTP</button>
                    </form>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="admin.php?forgot=1&use_security=1" style="color: var(--muted); font-size: 12px; text-decoration: none;">Use security questions instead</a>
                    </div>
                    <?php if (isset($_GET['use_security'])): ?>
                        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border);">
                        <h3 style="font-size: 16px; margin-bottom: 16px;">Security Questions</h3>
                        <form method="POST" action="admin.php?forgot=1">
                            <input type="hidden" name="verify_security_questions" value="1">
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label>1. Mother's Maiden Name</label>
                                <input type="text" name="maiden_name" class="text-input" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label>2. Favorite Color</label>
                                <input type="text" name="fav_color" class="text-input" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>3. Dog's Name</label>
                                <input type="text" name="dog_name" class="text-input" required>
                            </div>
                            <button type="submit" class="btn btn-full">Verify Identity</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="admin.php" style="color: var(--muted); font-size: 13px; text-decoration: none;">Back to login</a>
                </div>
            <?php else: ?>
                <h1>Admin Access</h1>
                <?php if ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($action_msg): ?>
                    <div class="alert-success"><?php echo htmlspecialchars($action_msg); ?></div>
                <?php endif; ?>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="text-input" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="text-input" required>
                    </div>
                    <button type="submit" class="btn btn-full">Log In</button>
                </form>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="admin.php?forgot=1" style="color: var(--muted); font-size: 13px; text-decoration: none;">Forgot Password?</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php else: ?>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <a href="admin.php?logout=1" class="btn btn-ghost">Log Out</a>
        </div>
        
        <?php if ($action_msg): ?>
            <div class="alert-success"><?php echo htmlspecialchars($action_msg); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=consultations" class="tab-btn <?php echo $active_tab === 'consultations' ? 'active' : ''; ?>">
                Consultations
                <span id="unreadBadge" style="background: #cc3333; color: white; border-radius: 10px; padding: 2px 7px; font-size: 11px; margin-left: 6px; font-weight: bold; <?php echo $unread_count > 0 ? '' : 'display:none;'; ?>"><?php echo $unread_count; ?></span>
            </a>
            <a href="?tab=inventory" class="tab-btn <?php echo $active_tab === 'inventory' ? 'active' : ''; ?>">Inventory</a>
            <a href="?tab=quotations" class="tab-btn <?php echo $active_tab === 'quotations' ? 'active' : ''; ?>">
                Quotations
                <span id="preQuoteBadge" style="background: #cc3333; color: white; border-radius: 10px; padding: 2px 7px; font-size: 11px; margin-left: 6px; font-weight: bold; <?php echo $total_unviewed_quotations > 0 ? '' : 'display:none;'; ?>"><?php echo $total_unviewed_quotations; ?></span>
            </a>
            <a href="?tab=calendar" class="tab-btn <?php echo $active_tab === 'calendar' ? 'active' : ''; ?>">Calendar</a>
            <a href="?tab=showcase" class="tab-btn <?php echo $active_tab === 'showcase' ? 'active' : ''; ?>">Showcase</a>
            <a href="?tab=logs" class="tab-btn <?php echo $active_tab === 'logs' ? 'active' : ''; ?>">Activity Logs</a>
            <a href="?tab=trash" class="tab-btn <?php echo $active_tab === 'trash' ? 'active' : ''; ?>">Trash Bin</a>
        </div>

        <?php if ($active_tab === 'consultations'): ?>
            <!-- Status Filter Bar -->
            <div style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 1px solid var(--border); padding-bottom: 12px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 13px; font-weight: 600; color: var(--muted); margin-right: 10px;">Filter:</span>
                <a href="?tab=consultations&status_filter=all" class="btn <?php echo ($filter_status === 'all' || !$filter_status) ? '' : 'btn-ghost'; ?>" style="font-size: 13px; padding: 6px 12px;">
                    All (<?php echo $cnt_unresolved + $cnt_resolved; ?>)
                </a>
                <a href="?tab=consultations&status_filter=unresolved" class="btn <?php echo ($filter_status === 'unresolved') ? '' : 'btn-ghost'; ?>" style="font-size: 13px; padding: 6px 12px; display: flex; align-items: center; gap: 6px;">
                    <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background: #cc3333;"></span>
                    Unresolved (<?php echo $cnt_unresolved; ?>)
                </a>
                <a href="?tab=consultations&status_filter=resolved" class="btn <?php echo ($filter_status === 'resolved') ? '' : 'btn-ghost'; ?>" style="font-size: 13px; padding: 6px 12px; display: flex; align-items: center; gap: 6px;">
                    <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background: #28a745;"></span>
                    Resolved (<?php echo $cnt_resolved; ?>)
                </a>
            </div>

            <form id="bulkDeleteForm" method="POST" action="admin.php?tab=consultations" onsubmit="return confirm('Are you sure you want to delete the selected consultations?');">
                <input type="hidden" name="bulk_delete_inquiries" value="1">
                <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <button type="submit" class="btn" style="background: #cc3333; padding: 8px 16px;">Delete Selected</button>
                    <div style="font-size: 13px; color: var(--muted);">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" style="vertical-align: middle; margin-right: 5px;">
                        <label for="selectAll" style="cursor: pointer; vertical-align: middle;">Select All</label>
                    </div>
                </div>

                <div class="table-wrap consultations-table-wrap">
                    <table class="consultations-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;"></th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Source</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="inquiriesBody">
                            <?php if (empty($inquiries)): ?>
                                <tr><td colspan="8" style="text-align:center; color: var(--muted); padding: 30px;">No consultations yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($inquiries as $inq): ?>
                                <tr>
                                    <td style="text-align: center;"><input type="checkbox" name="inquiry_ids[]" value="<?php echo $inq['id']; ?>" class="inq-checkbox"></td>
                                    <td style="color:var(--muted); font-size:12px;"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inq['fullname']); ?></strong>
                                        <?php if (!empty($inq['contact_name']) && $inq['contact_name'] !== $inq['fullname']): ?>
                                            <br><span style="color:var(--muted); font-size:12px;">Contact: <?php echo htmlspecialchars($inq['contact_name']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($inq['email']); ?><br>
                                        <span style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($inq['phone']); ?></span>
                                        <?php if (!empty($inq['contact_number']) && $inq['contact_number'] !== $inq['phone']): ?>
                                            <br><span style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($inq['contact_number']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="background:rgba(0,0,0,0.06); padding:4px 6px; border-radius:4px; font-size:11px; line-height:1.25; display:inline-block;"><?php echo htmlspecialchars($inq['project_type']); ?></span></td>
                                    <td>
                                        <?php
                                        $src = $inq['source'] ?? 'contact';
                                        $src_color = $src === 'estimator' ? '#1565c0' : '#2e7d32';
                                        $src_bg    = $src === 'estimator' ? '#e3f2fd' : '#e8f5e9';
                                        echo "<span style='background:{$src_bg}; color:{$src_color}; padding:3px 6px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; line-height:1.2; display:inline-block;'>" . htmlspecialchars($src) . "</span>";
                                        ?>
                                    </td>
                                    <td style="font-size:12px; line-height:1.45;"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></td>
                                    <td class="consultation-status-cell">
                                        <?php if (($inq['status'] ?? 'unresolved') === 'resolved'): ?>
                                            <div class="consultation-status-actions">
                                                <span class="status-pill resolved">Resolved</span>
                                                <form method="POST" action="admin.php?tab=consultations&status_filter=<?php echo $filter_status; ?>" style="margin:0;">
                                                    <input type="hidden" name="toggle_inquiry_status" value="1">
                                                    <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                    <input type="hidden" name="status" value="unresolved">
                                                    <button type="submit" class="mark-unresolved-btn">Mark Unresolved</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div class="consultation-status-actions">
                                                <span class="status-pill unresolved">Unresolved</span>
                                                <form method="POST" action="admin.php?tab=consultations&status_filter=<?php echo $filter_status; ?>" style="margin:0;">
                                                    <input type="hidden" name="toggle_inquiry_status" value="1">
                                                    <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                    <input type="hidden" name="status" value="resolved">
                                                    <button type="submit" class="resolve-btn">
                                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                        Resolve
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

        <?php elseif ($active_tab === 'inventory'): ?>
            <div class="add-item-box" style="margin-top: 0; margin-bottom: 24px;">
                <h3>Add New Item</h3>
                <form method="POST" action="admin.php?tab=inventory" style="margin:0;">
                    <input type="hidden" name="add_inventory" value="1">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div class="form-group" style="margin:0;">
                            <label>Item Name</label>
                            <input type="text" name="item_name" class="text-input" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Size</label>
                            <input type="text" name="size" class="text-input">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="num-input" value="0" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Unit</label>
                            <input type="text" name="unit" class="text-input">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Price (₱)</label>
                            <input type="number" name="unit_price" class="num-input" value="0.00" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width: 100%; padding: 10px;">Add Item</button>
                </form>
            </div>

            <div style="margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin-bottom:7px;">Search Inventory</label>
                    <input type="text" id="inventorySearch" class="text-input" placeholder="Search inventory items..." onkeyup="searchInventory()">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin-bottom:7px;">Sort By</label>
                    <select class="text-input" onchange="location.href='?tab=inventory&sort=' + this.value + '&dir=<?php echo $dir; ?>'" style="padding: 9px 13px;">
                        <option value="item_name" <?php echo $sort === 'item_name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="updated_at" <?php echo $sort === 'updated_at' ? 'selected' : ''; ?>>Last Updated</option>
                        <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                        <option value="unit_price" <?php echo $sort === 'unit_price' ? 'selected' : ''; ?>>Price</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin-bottom:7px;">Order</label>
                    <select class="text-input" onchange="location.href='?tab=inventory&sort=<?php echo $sort; ?>&dir=' + this.value" style="padding: 9px 13px;">
                        <option value="ASC" <?php echo $dir === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $dir === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Size</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                        <tr id="inv_row_<?php echo $item['id']; ?>">
                            <td style="font-weight:500;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['size'] ?? ''); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td style="color:var(--muted); font-size:13px;"><?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?></td>
                            <td>
                                <button type="button" class="btn" onclick="toggleEdit(<?php echo $item['id']; ?>)">Edit</button>
                            </td>
                        </tr>
                        <tr id="inv_edit_row_<?php echo $item['id']; ?>" style="display:none; background: rgba(0,0,0,0.02);">
                            <td style="font-weight:500;">
                                <form id="inv_form_<?php echo $item['id']; ?>" method="POST" action="admin.php?tab=inventory&sort=<?php echo $sort; ?>&dir=<?php echo $dir; ?>">
                                    <input type="hidden" name="update_inventory" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                </form>
                                <input type="text" name="item_name" form="inv_form_<?php echo $item['id']; ?>" value="<?php echo htmlspecialchars($item['item_name']); ?>" class="text-input" style="padding: 6px; width: 100%; max-width: 150px;" required>
                            </td>
                            <td>
                                <input type="text" name="size" form="inv_form_<?php echo $item['id']; ?>" class="text-input" value="<?php echo htmlspecialchars($item['size'] ?? ''); ?>" style="padding: 6px; width: 60px;">
                            </td>
                            <td>
                                <input type="number" name="quantity" form="inv_form_<?php echo $item['id']; ?>" class="num-input" value="<?php echo $item['quantity']; ?>" required style="padding: 6px; width: 60px;">
                            </td>
                            <td>
                                <input type="text" name="unit" form="inv_form_<?php echo $item['id']; ?>" class="text-input" value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>" style="padding: 6px; width: 50px;">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    ₱<input type="number" name="unit_price" form="inv_form_<?php echo $item['id']; ?>" class="num-input" value="<?php echo $item['unit_price']; ?>" step="0.01" required style="padding: 6px; width: 80px;">
                                </div>
                            </td>
                            <td style="color:var(--muted); font-size:13px;"><?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" form="inv_form_<?php echo $item['id']; ?>" class="btn">Save</button>
                                    <button type="button" class="btn btn-ghost" onclick="toggleEdit(<?php echo $item['id']; ?>)">Cancel</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'quotations'): ?>
            <style>
                .breadcrumb { margin-bottom: 20px; font-size: 14px; }
                .breadcrumb a { color: var(--text); text-decoration: none; font-weight: 500; }
                .breadcrumb a:hover { text-decoration: underline; }
                .grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px; }
                .folder-card, .quote-card { 
                    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); 
                    padding: 20px; text-decoration: none; color: var(--text); display: block;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                .folder-card:hover, .quote-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .folder-icon { font-size: 24px; margin-bottom: 10px; color: #ffb74d; }
                .quote-icon { font-size: 24px; margin-bottom: 10px; color: #64b5f6; }
                
                .quote-form-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; position: relative;}
                .quote-form-row .autocomplete-list {
                    position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--border);
                    z-index: 100; max-height: 150px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .quote-form-row .autocomplete-list div { padding: 8px 12px; cursor: pointer; font-size: 13px; }
                .quote-form-row .autocomplete-list div:hover { background: #f5f5f5; }

                .draggable-item { cursor: grab; }
                .draggable-item:active { cursor: grabbing; }
                .drop-zone-active { background: rgba(100, 181, 246, 0.1) !important; border-color: #64b5f6 !important; }
            </style>

            <div class="breadcrumb">
                <a href="?tab=quotations" ondragover="allowDrop(event)" ondrop="handleDrop(event, 'root')" ondragenter="this.classList.add('drop-zone-active')" ondragleave="this.classList.remove('drop-zone-active')" style="padding: 4px 8px; border-radius: 4px;">Quotations</a>
                <?php foreach ($breadcrumbs as $bc): ?>
                    / <a href="?tab=quotations&folder_id=<?php echo $bc['id']; ?>" ondragover="allowDrop(event)" ondrop="handleDrop(event, <?php echo $bc['id']; ?>)" ondragenter="this.classList.add('drop-zone-active')" ondragleave="this.classList.remove('drop-zone-active')" style="padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($bc['name']); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_GET['create_quote'])): ?>
                <div class="add-item-box" style="display:block; width: 100%; box-sizing: border-box;">
                    <h3>Create New Quotation</h3>
                    <form method="POST" action="admin.php?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>">
                        <input type="hidden" name="add_quotation" value="1">
                        <input type="hidden" name="folder_id" value="<?php echo $folder_id; ?>">
                        
                        <div class="form-group" style="max-width: 400px; margin-bottom: 20px;">
                            <label>Quotation Title / Client Name</label>
                            <input type="text" name="title" class="text-input" required>
                        </div>
                        <div style="display: flex; gap: 16px; max-width: 400px; margin-bottom: 20px;">
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>PO Number</label>
                                <input type="text" name="po_number" class="text-input" placeholder="e.g. PO-12345">
                            </div>
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>Prepared By / Signed By</label>
                                <input type="text" name="signee_name" class="text-input" placeholder="Name for signature">
                            </div>
                        </div>
                        
                        <div id="quoteItems">
                            <div style="display: flex; gap: 10px; margin-bottom: 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted);">
                                <div style="flex: 2;">Item Name</div>
                                <div style="flex: 1;">Qty</div>
                                <div style="flex: 1;">Unit Price</div>
                                <div style="flex: 1; text-align: right;">Total</div>
                                <div style="width: 32px;"></div>
                            </div>
                            <!-- Rows will be added here via JS -->
                        </div>
                        
                        <div style="margin-top: 10px; margin-bottom: 20px;">
                            <button type="button" class="btn btn-ghost" style="border: 1px dashed var(--border);" onclick="addQuoteRow()">+ Add Item</button>
                        </div>
                        
                        <div style="text-align: right; font-size: 18px; font-weight: bold; margin-bottom: 20px;">
                            Grand Total: ₱<span id="grandTotalDisplay">0.00</span>
                            <input type="hidden" name="grand_total" id="grandTotalInput" value="0">
                        </div>
                        
                        <button type="submit" class="btn" style="padding: 12px 24px;">Save Quotation</button>
                        <a href="?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" class="btn btn-ghost" style="margin-left: 10px;">Cancel</a>
                    </form>
                </div>
                
                <script>
                const inventoryItems = <?php echo json_encode($inventory); ?>;
                let rowCount = 0;

                function addQuoteRow() {
                    rowCount++;
                    const container = document.getElementById('quoteItems');
                    const row = document.createElement('div');
                    row.className = 'quote-form-row';
                    row.innerHTML = `
                        <div style="flex: 2; position: relative;">
                            <input type="text" name="items[${rowCount}][name]" class="text-input item-name-input" onkeyup="filterItems(this)" autocomplete="off" required>
                            <div class="autocomplete-list"></div>
                        </div>
                        <div style="flex: 1;">
                            <input type="number" name="items[${rowCount}][qty]" class="num-input qty-input" value="1" min="1" oninput="calcRow(this)" required>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                            ₱ <input type="number" name="items[${rowCount}][price]" class="num-input price-input" step="0.01" min="0" oninput="calcRow(this)" required>
                        </div>
                        <div style="flex: 1; text-align: right; font-weight: 500;">
                            ₱<span class="row-total-display">0.00</span>
                            <input type="hidden" name="items[${rowCount}][total]" class="row-total-input" value="0">
                        </div>
                        <div>
                            <button type="button" class="btn" style="background: #cc3333; padding: 10px;" onclick="this.parentElement.parentElement.remove(); calcGrandTotal();">X</button>
                        </div>
                    `;
                    container.appendChild(row);
                }

                function filterItems(input) {
                    const list = input.nextElementSibling;
                    const val = input.value.toLowerCase();
                    list.innerHTML = '';
                    if (!val) { list.style.display = 'none'; return; }
                    
                    const matches = inventoryItems.filter(i => {
                        let fullName = i.item_name;
                        if (i.size && i.size.trim() !== '') fullName += ' - ' + i.size;
                        return fullName.toLowerCase().includes(val);
                    });
                    if (matches.length === 0) { list.style.display = 'none'; return; }
                    
                    matches.forEach(m => {
                        const div = document.createElement('div');
                        let displayText = m.item_name;
                        if (m.size && m.size.trim() !== '') {
                            displayText += ' - ' + m.size;
                        }
                        div.textContent = displayText;
                        div.onclick = function() {
                            input.value = displayText;
                            list.style.display = 'none';
                            
                            // Auto-fill price
                            const row = input.closest('.quote-form-row');
                            const priceInput = row.querySelector('.price-input');
                            if (priceInput) {
                                priceInput.value = m.unit_price;
                                calcRow(priceInput); // Trigger calculation
                            }
                        };
                        list.appendChild(div);
                    });
                    list.style.display = 'block';
                }

                // Hide autocomplete on outside click
                document.addEventListener('click', function(e) {
                    if(!e.target.classList.contains('item-name-input')) {
                        document.querySelectorAll('.autocomplete-list').forEach(l => l.style.display = 'none');
                    }
                });

                function calcRow(el) {
                    const row = el.closest('.quote-form-row');
                    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                    const price = parseFloat(row.querySelector('.price-input').value) || 0;
                    const total = qty * price;
                    
                    row.querySelector('.row-total-display').textContent = total.toFixed(2);
                    row.querySelector('.row-total-input').value = total.toFixed(2);
                    calcGrandTotal();
                }

                function calcGrandTotal() {
                    let grandTotal = 0;
                    document.querySelectorAll('.row-total-input').forEach(input => {
                        grandTotal += parseFloat(input.value) || 0;
                    });
                    document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(2);
                    document.getElementById('grandTotalInput').value = grandTotal.toFixed(2);
                }

                // Add initial row
                addQuoteRow();
                </script>

            <?php elseif (isset($_GET['view_quote']) && $view_quote): ?>
                <?php if (isset($_GET['gen_success'])): ?>
                    <div class="alert-success" style="margin-bottom: 20px;">✓ Quotation auto-generated from estimator. Review and print below.</div>
                <?php endif; ?>
                <?php if (isset($_GET['po_created'])): ?>
                    <div class="alert-success" style="margin-bottom: 20px;">✓ Purchase Order created successfully.</div>
                <?php endif; ?>
                <?php if (isset($_GET['inv_id'])): ?>
                    <div class="alert-success" style="margin-bottom: 20px;">✓ Sales Invoice generated and inventory quantities deducted.</div>
                <?php endif; ?>

                <?php if (isset($_GET['inv_retracted'])): ?>
                    <div class="alert-success" style="margin-bottom: 20px;">Sales Invoice retracted and inventory quantities restored.</div>
                <?php endif; ?>

                <!-- PO / Invoice Action Bar -->
                <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;" class="no-print">
                    <a href="?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" class="btn btn-ghost" style="border:1px solid var(--border);">← Back</a>
                    <button onclick="window.print()" class="btn">🖨 Print Quotation</button>
                    <?php if (!$view_quote_po): ?>
                        <button class="btn" style="background:#1565c0;" onclick="document.getElementById('poForm').style.display='block'">📋 Convert to Purchase Order</button>
                    <?php else: ?>
                        <span style="padding: 10px 14px; background: #e3f2fd; color: #1565c0; border-radius: var(--radius); font-size: 13px; font-weight: 600;">
                            PO: <?php echo htmlspecialchars($view_quote_po['po_number']); ?> — <?php echo strtoupper($view_quote_po['status']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!$view_quote_invoice): ?>
                        <button class="btn" style="background:#2e7d32;" onclick="document.getElementById('invForm').style.display='block'">🧾 Generate Sales Invoice</button>
                    <?php else: ?>
                        <span style="padding: 10px 14px; background: #e8f5e9; color: #2e7d32; border-radius: var(--radius); font-size: 13px; font-weight: 600;">
                            Invoice: <?php echo htmlspecialchars($view_quote_invoice['invoice_number']); ?> — <?php echo strtoupper($view_quote_invoice['status']); ?>
                            <?php if ($view_quote_invoice['inventory_deducted']): ?> ✓ Inventory Deducted<?php endif; ?>
                        </span>
                        <form method="POST" action="admin.php?tab=quotations&view_quote=<?php echo $view_quote['id']; ?>" style="margin:0;">
                            <input type="hidden" name="retract_invoice" value="1">
                            <input type="hidden" name="quotation_id" value="<?php echo $view_quote['id']; ?>">
                            <input type="hidden" name="invoice_id" value="<?php echo $view_quote_invoice['id']; ?>">
                            <button type="submit" class="btn" style="background:#b91c1c;" onclick="return confirm('Retract this invoice and return the deducted materials to inventory?');">Retract Invoice</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- PO Form (inline) -->
                <div id="poForm" style="display:none; margin-bottom: 20px; padding: 20px; background: #f0f7ff; border: 1px solid #bbdefb; border-radius: var(--radius);" class="no-print">
                    <h4 style="margin: 0 0 12px 0; color: #1565c0;">Create Purchase Order</h4>
                    <form method="POST" action="admin.php?tab=quotations&view_quote=<?php echo $view_quote['id']; ?>">
                        <input type="hidden" name="create_purchase_order" value="1">
                        <input type="hidden" name="quotation_id" value="<?php echo $view_quote['id']; ?>">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Notes (Optional)</label>
                            <input type="text" name="po_notes" class="text-input" placeholder="e.g. Approved by management">
                        </div>
                        <button type="submit" class="btn" style="background:#1565c0;">Create PO</button>
                        <button type="button" class="btn btn-ghost" onclick="document.getElementById('poForm').style.display='none'">Cancel</button>
                    </form>
                </div>

                <!-- Invoice Form (inline) -->
                <div id="invForm" style="display:none; margin-bottom: 20px; padding: 20px; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: var(--radius);" class="no-print">
                    <h4 style="margin: 0 0 12px 0; color: #2e7d32;">Generate Sales Invoice</h4>
                    <p style="font-size: 13px; color: #555; margin: 0 0 12px 0;">⚠ This will <strong>deduct inventory quantities</strong> based on the line items in this quotation.</p>
                    <form method="POST" action="admin.php?tab=quotations&view_quote=<?php echo $view_quote['id']; ?>">
                        <input type="hidden" name="generate_invoice" value="1">
                        <input type="hidden" name="quotation_id" value="<?php echo $view_quote['id']; ?>">
                        <input type="hidden" name="po_id" value="<?php echo $view_quote_po['id'] ?? ''; ?>">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Notes (Optional)</label>
                            <input type="text" name="inv_notes" class="text-input" placeholder="e.g. Payment due in 30 days">
                        </div>
                        <button type="submit" class="btn" style="background:#2e7d32;" onclick="return confirm('Generate invoice and deduct inventory? This cannot be undone.')">Generate Invoice</button>
                        <button type="button" class="btn btn-ghost" onclick="document.getElementById('invForm').style.display='none'">Cancel</button>
                    </form>
                </div>

                <div class="add-item-box" style="display:block; width: 100%; box-sizing: border-box; background: #fff; padding: 40px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                        <div>
                            <img src="image.png" class="quote-logo" style="height: 70px; margin-bottom: 15px; display: block;">
                            <h1 style="margin:0; font-size: 22px; font-weight: 700;">NATH Hardware and Construction Supplies</h1>
                            <p style="margin: 5px 0 20px 0; font-size: 13px; color: var(--muted);">52 Diaz St., Bahayang Pagasa, Pasong Buaya II, Imus, Cavite 1403</p>
                            <h2 style="margin:0; font-size: 20px;"><?php echo htmlspecialchars($view_quote['title']); ?></h2>
                            <?php if(!empty($view_quote['po_number'])): ?>
                                <p style="margin-top: 5px; font-weight: 500;">PO Number: <?php echo htmlspecialchars($view_quote['po_number']); ?></p>
                            <?php endif; ?>
                            <?php if($view_quote_po): ?>
                                <p style="margin-top: 5px; color: #1565c0; font-weight: 500;">Purchase Order: <?php echo htmlspecialchars($view_quote_po['po_number']); ?> (<?php echo $view_quote_po['po_date']; ?>)</p>
                            <?php endif; ?>
                            <?php if($view_quote_invoice): ?>
                                <p style="margin-top: 5px; color: #2e7d32; font-weight: 500;">Invoice: <?php echo htmlspecialchars($view_quote_invoice['invoice_number']); ?> (<?php echo $view_quote_invoice['invoice_date']; ?>)</p>
                            <?php endif; ?>
                            <p style="color: var(--muted); margin-top: 5px;">Date: <?php echo date('M d, Y', strtotime($view_quote['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 50px;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--text);">
                                <th style="padding: 10px 0; text-align: left;">Item Description</th>
                                <th style="padding: 10px 0; text-align: center;">Qty</th>
                                <th style="padding: 10px 0; text-align: right;">Unit Price</th>
                                <th style="padding: 10px 0; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($view_quote_items as $qi): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 0;"><?php echo htmlspecialchars($qi['item_name']); ?></td>
                                <td style="padding: 12px 0; text-align: center;"><?php echo $qi['quantity']; ?></td>
                                <td style="padding: 12px 0; text-align: right;">₱<?php echo number_format($qi['unit_price'], 2); ?></td>
                                <td style="padding: 12px 0; text-align: right; font-weight: 500;">₱<?php echo number_format($qi['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; padding: 20px 0; font-size: 18px; font-weight: bold;">Grand Total:</td>
                                <td style="text-align: right; padding: 20px 0; font-size: 18px; font-weight: bold;">₱<?php echo number_format($view_quote['grand_total'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div style="margin-top: 60px; width: 300px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 10px; height: 30px;"></div>
                        <div style="font-weight: 500; text-align: center; font-size: 16px;">
                            <?php echo !empty($view_quote['signee_name']) ? htmlspecialchars($view_quote['signee_name']) : 'Authorized Signature'; ?>
                        </div>
                    </div>
                </div>

                <style>
                    .no-print { }
                    @media print {
                        @page { margin: 0; }
                        body { margin: 1cm; }
                        body * { visibility: hidden; }
                        .add-item-box, .add-item-box * { visibility: visible; }
                        .add-item-box { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 0; margin: 0; }
                        .no-print { display: none !important; }
                        .btn { display: none !important; }
                        .dashboard { padding: 0; margin: 0; max-width: 100%; }
                        .quote-logo { width: 130px !important; height: auto !important; max-height: none !important; max-width: none !important; }
                    }
                </style>


            <?php else: ?>
                <?php if (false): ?>
                <!-- Customer Pre-Quotations Section -->
                <div style="margin-bottom: 40px;">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-weight: 500; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                        <span>Customer Pre-Quotations</span>
                        <?php if ($unviewed_pre_count > 0): ?>
                            <span style="background: #cc3333; color: white; border-radius: 10px; padding: 2px 7px; font-size: 11px; font-weight: bold;"><?php echo $unviewed_pre_count; ?> new</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if (empty($pre_quotations)): ?>
                        <div style="padding: 20px; text-align: center; color: var(--muted); border: 1px dashed var(--border); border-radius: var(--radius); background: var(--surface); margin-bottom: 30px;">
                            No submitted customer pre-quotations yet.
                        </div>
                    <?php else: ?>
                        <div class="table-wrap" style="margin-bottom: 30px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Project Details</th>
                                        <th>Configuration</th>
                                        <th style="text-align: right;">Est. Total</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pre_quotations as $pq): ?>
                                        <tr>
                                            <td>
                                                <div style="font-size: 13px; color: var(--text);"><?php echo date('M d, Y', strtotime($pq['generated_at'])); ?></div>
                                                <div style="font-size: 11px; color: var(--muted);"><?php echo date('h:i A', strtotime($pq['generated_at'])); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($pq['customer_name']); ?></div>
                                                <div style="font-size: 12px; color: var(--muted);"><?php echo htmlspecialchars($pq['customer_email']); ?> | <?php echo htmlspecialchars($pq['customer_phone']); ?></div>
                                            </td>
                                            <td>
                                                <strong style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $pq['project_type']); ?></strong>
                                                <div style="font-size: 12px; color: var(--muted); text-transform: capitalize;"><?php echo htmlspecialchars($pq['building_type']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px;"><?php echo number_format($pq['sqm'], 2); ?> sqm / <?php echo $pq['floors']; ?> floor<?php echo $pq['floors'] > 1 ? 's' : ''; ?></div>
                                                <div style="font-size: 11px; color: var(--muted); text-transform: capitalize;">Tier: <?php echo htmlspecialchars($pq['material_level']); ?></div>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; font-size: 14px;">
                                                ₱<?php echo number_format($pq['estimated_total'], 2); ?>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <button class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; border: 1px solid var(--border);" 
                                                        onclick="viewPreQuoteDetails(<?php echo htmlspecialchars(json_encode($pq)); ?>)">
                                                    🔍 View Details
                                                </button>
                                                <form method="POST" action="admin.php?tab=quotations" style="display: inline-block; margin: 0;" onsubmit="return confirm('Delete this pre-quotation?');">
                                                    <input type="hidden" name="delete_pre_quotation" value="1">
                                                    <input type="hidden" name="pre_quote_id" value="<?php echo $pq['id']; ?>">
                                                    <button type="submit" class="btn" style="background: #cc3333; padding: 6px 10px; font-size: 12px; margin-left: 4px;">×</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

                <!-- Draft Quotations Section -->
                <div style="margin-bottom: 40px;">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-weight: 500; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                        <span>Draft Quotations</span>
                        <?php if ($unviewed_draft_count > 0): ?>
                            <span style="background: #cc3333; color: white; border-radius: 10px; padding: 2px 7px; font-size: 11px; font-weight: bold;"><?php echo $unviewed_draft_count; ?> new</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if (empty($draft_quotations)): ?>
                        <div style="padding: 20px; text-align: center; color: var(--muted); border: 1px dashed var(--border); border-radius: var(--radius); background: var(--surface); margin-bottom: 30px;">
                            No draft quotations generated yet.
                        </div>
                    <?php else: ?>
                        <div class="table-wrap" style="margin-bottom: 30px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Project Details</th>
                                        <th>Configuration</th>
                                        <th style="text-align: right;">Est. Total</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($draft_quotations as $dq): ?>
                                        <tr>
                                            <td>
                                                <div style="font-size: 13px; color: var(--text);"><?php echo date('M d, Y', strtotime($dq['generated_at'])); ?></div>
                                                <div style="font-size: 11px; color: var(--muted);"><?php echo date('h:i A', strtotime($dq['generated_at'])); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($dq['customer_name']); ?></div>
                                                <div style="font-size: 12px; color: var(--muted);"><?php echo htmlspecialchars($dq['customer_email']); ?> | <?php echo htmlspecialchars($dq['customer_phone']); ?></div>
                                            </td>
                                            <td>
                                                <strong style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $dq['project_type']); ?></strong>
                                                <div style="font-size: 12px; color: var(--muted); text-transform: capitalize;"><?php echo htmlspecialchars($dq['building_type']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px;"><?php echo number_format($dq['sqm'], 2); ?> sqm / <?php echo $dq['floors']; ?> floor<?php echo $dq['floors'] > 1 ? 's' : ''; ?></div>
                                                <div style="font-size: 11px; color: var(--muted); text-transform: capitalize;">Tier: <?php echo htmlspecialchars($dq['material_level']); ?></div>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; font-size: 14px;">
                                                ₱<?php echo number_format($dq['estimated_total'], 2); ?>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <button class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; border: 1px solid var(--border);" 
                                                        onclick="viewPreQuoteDetails(<?php echo htmlspecialchars(json_encode($dq)); ?>)">
                                                    🔍 View Details
                                                </button>
                                                <form method="POST" action="admin.php?tab=quotations" style="display: inline-block; margin: 0;" onsubmit="return confirm('Delete this draft quotation?');">
                                                    <input type="hidden" name="delete_pre_quotation" value="1">
                                                    <input type="hidden" name="pre_quote_id" value="<?php echo $dq['id']; ?>">
                                                    <button type="submit" class="btn" style="background: #cc3333; padding: 6px 10px; font-size: 12px; margin-left: 4px;">×</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <hr style="border: 0; border-top: 1px dashed var(--border); margin-bottom: 30px;">

                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <a href="?tab=quotations&create_quote=1<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" class="btn">+ New Quotation</a>
                    <button class="btn btn-ghost" style="border: 1px solid var(--border);" onclick="document.getElementById('newFolderForm').style.display='block'">+ New Folder</button>
                </div>
                
                <div id="newFolderForm" class="add-item-box" style="display:none; margin-bottom: 20px; margin-top: 0;">
                    <form method="POST" action="admin.php?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" class="add-item-form">
                        <input type="hidden" name="add_folder" value="1">
                        <input type="hidden" name="parent_id" value="<?php echo $folder_id; ?>">
                        <div class="form-group" style="margin:0; width: 250px;">
                            <label>Folder Name</label>
                            <input type="text" name="folder_name" class="text-input" required>
                        </div>
                        <button type="submit" class="btn" style="height: 40px;">Create</button>
                        <button type="button" class="btn btn-ghost" style="height: 40px;" onclick="document.getElementById('newFolderForm').style.display='none'">Cancel</button>
                    </form>
                </div>

                <div class="grid-view">
                    <?php foreach ($folders as $f): ?>
                        <div style="position: relative;">
                            <a href="?tab=quotations&folder_id=<?php echo $f['id']; ?>" 
                               class="folder-card draggable-item" 
                               draggable="true" 
                               ondragstart="handleDragStart(event, '<?php echo $f['id']; ?>', 'folder')"
                               ondragover="allowDrop(event)"
                               ondrop="handleDrop(event, '<?php echo $f['id']; ?>')"
                               ondragenter="this.classList.add('drop-zone-active')"
                               ondragleave="this.classList.remove('drop-zone-active')">
                                <div class="folder-icon">📁</div>
                                <div style="font-weight: 500; font-size: 15px;"><?php echo htmlspecialchars($f['name']); ?></div>
                            </a>
                            <form method="POST" action="admin.php?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" style="position: absolute; top: 10px; right: 10px; margin: 0; display: inline-block;" onsubmit="return confirm('Delete folder and ALL its contents?');">
                                <input type="hidden" name="delete_folder" value="1">
                                <input type="hidden" name="folder_id" value="<?php echo $f['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #cc3333; cursor: pointer; font-size: 16px;">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($quotations as $q): ?>
                        <div style="position: relative;">
                            <a href="?tab=quotations&view_quote=<?php echo $q['id']; ?><?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" 
                               class="quote-card draggable-item"
                               draggable="true"
                               ondragstart="handleDragStart(event, '<?php echo $q['id']; ?>', 'quote')">
                                <div class="quote-icon">📄</div>
                                <div style="font-weight: 500; font-size: 15px; margin-bottom: 4px;"><?php echo htmlspecialchars($q['title']); ?></div>
                                <div style="font-size: 13px; color: var(--muted);">₱<?php echo number_format($q['grand_total'], 2); ?></div>
                            </a>
                            <form method="POST" action="admin.php?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" style="position: absolute; top: 10px; right: 10px; margin: 0; display: inline-block;" onsubmit="return confirm('Delete quotation?');">
                                <input type="hidden" name="delete_quotation" value="1">
                                <input type="hidden" name="quotation_id" value="<?php echo $q['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #cc3333; cursor: pointer; font-size: 16px;">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if(empty($folders) && empty($quotations)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--muted); border: 1px dashed var(--border); border-radius: var(--radius);">
                        This folder is empty. Create a folder or a new quotation.
                    </div>
                <?php endif; ?>

                <form id="moveForm" method="POST" action="admin.php?tab=quotations<?php echo $folder_id ? '&folder_id='.$folder_id : ''; ?>" style="display:none;">
                    <input type="hidden" name="move_item" value="1">
                    <input type="hidden" name="item_id" id="moveItemId">
                    <input type="hidden" name="item_type" id="moveItemType">
                    <input type="hidden" name="target_folder_id" id="moveTargetFolderId">
                </form>

                <script>
                function handleDragStart(e, id, type) {
                    e.dataTransfer.setData('itemId', id);
                    e.dataTransfer.setData('itemType', type);
                    e.dataTransfer.effectAllowed = 'move';
                }

                function allowDrop(e) {
                    e.preventDefault();
                }

                function handleDrop(e, targetId) {
                    e.preventDefault();
                    const id = e.dataTransfer.getData('itemId');
                    const type = e.dataTransfer.getData('itemType');
                    
                    if (id && type) {
                        document.getElementById('moveItemId').value = id;
                        document.getElementById('moveItemType').value = type;
                        document.getElementById('moveTargetFolderId').value = targetId;
                        document.getElementById('moveForm').submit();
                    }
                }
                </script>
            <?php endif; ?>
        <?php elseif ($active_tab === 'calendar'): ?>
            <?php 
                $cal_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
                $cal_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
                
                $prev_month = $cal_month - 1;
                $prev_year = $cal_year;
                if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
                
                $next_month = $cal_month + 1;
                $next_year = $cal_year;
                if ($next_month > 12) { $next_month = 1; $next_year++; }
                
                $month_name = date("F", mktime(0, 0, 0, $cal_month, 10));
                
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $cal_month, $cal_year);
                $first_day_of_month = date("w", strtotime("$cal_year-$cal_month-01"));
            ?>
            <style>
                .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
                .calendar-scroll-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                .calendar-grid { 
                    display: grid; 
                    grid-template-columns: repeat(7, 1fr); 
                    gap: 1px; 
                    background: var(--border); 
                    border: 1px solid var(--border);
                    border-radius: var(--radius);
                    overflow: hidden;
                    min-width: 560px;
                }
                .calendar-day-header { background: #fafafa; padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }
                .calendar-cell { background: #fff; min-height: 90px; padding: 8px 6px; position: relative; }
                .calendar-cell.empty { background: #f9f9f9; }
                .calendar-date { font-weight: 500; margin-bottom: 6px; display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; border-radius: 50%; font-size: 13px; }
                .calendar-cell.today .calendar-date { background: var(--text); color: white; }
                .event-card { 
                    padding: 4px 5px; margin-bottom: 3px; border-radius: 4px; font-size: 10px; 
                    color: #fff; line-height: 1.3; position: relative; cursor: pointer;
                }
                .event-card-title { font-weight: bold; margin-bottom: 1px; }
                .event-card-client { opacity: 0.9; }
                .event-card-time { opacity: 0.8; font-size: 9px; }
            </style>

            <div class="calendar-header">
                <div style="display: flex; gap: 10px;">
                    <a href="?tab=calendar&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-ghost" style="border: 1px solid var(--border);">&larr; Prev</a>
                    <a href="?tab=calendar&month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-ghost" style="border: 1px solid var(--border);">Today</a>
                    <a href="?tab=calendar&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-ghost" style="border: 1px solid var(--border);">Next &rarr;</a>
                </div>
                <h2 style="margin: 0; font-size: 24px;"><?php echo "$month_name $cal_year"; ?></h2>
                <button class="btn" onclick="document.getElementById('eventModal').style.display='flex'">+ Add Event</button>
            </div>

            <div class="calendar-scroll-wrap"><div class="calendar-grid">
                <?php 
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($days as $d) echo "<div class='calendar-day-header'>$d</div>";
                
                // Empty cells before start of month
                for ($i = 0; $i < $first_day_of_month; $i++) {
                    echo "<div class='calendar-cell empty'></div>";
                }
                
                $today_str = date('Y-m-d');
                
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date_str = sprintf("%04d-%02d-%02d", $cal_year, $cal_month, $day);
                    $is_today = ($date_str === $today_str) ? 'today' : '';
                    
                    echo "<div class='calendar-cell $is_today'>";
                    echo "<div class='calendar-date'>$day</div>";
                    
                    if (isset($events[$date_str])) {
                        foreach ($events[$date_str] as $evt) {
                            $bg = htmlspecialchars($evt['color']);
                            $evt_json = htmlspecialchars(json_encode($evt), ENT_QUOTES, 'UTF-8');
                            echo "<div class='event-card' style='background: $bg;' onclick='viewEvent($evt_json)'>";
                            if ($evt['event_time']) echo "<div class='event-card-time'>" . htmlspecialchars($evt['event_time']) . "</div>";
                            echo "<div class='event-card-title'>" . htmlspecialchars($evt['title']) . "</div>";
                            if ($evt['client_name']) echo "<div class='event-card-client'>" . htmlspecialchars($evt['client_name']) . "</div>";
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                }
                
                // Empty cells after end of month
                $total_cells = $first_day_of_month + $days_in_month;
                $remaining_cells = 7 - ($total_cells % 7);
                if ($remaining_cells < 7) {
                    for ($i = 0; $i < $remaining_cells; $i++) {
                        echo "<div class='calendar-cell empty'></div>";
                    }
                }
                ?>
            </div></div><!-- end calendar-grid / calendar-scroll-wrap -->

            <!-- Add Event Modal -->
            <div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
                <div class="add-item-box" style="margin: 0; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Add New Event</h3>
                        <button class="btn btn-ghost" style="padding: 5px 10px;" onclick="document.getElementById('eventModal').style.display='none'">×</button>
                    </div>
                    <form method="POST" action="admin.php?tab=calendar&month=<?php echo $cal_month; ?>&year=<?php echo $cal_year; ?>">
                        <input type="hidden" name="add_event" value="1">
                        
                        <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>Date</label>
                                <input type="date" name="event_date" class="text-input" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>Time</label>
                                <input type="time" name="event_time" class="text-input">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Event Title</label>
                            <input type="text" name="title" class="text-input" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Client Name (Optional)</label>
                            <input type="text" name="client_name" class="text-input">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Description</label>
                            <textarea name="description" class="text-input" style="height: 150px; resize: vertical; width: 100%; box-sizing: border-box;"></textarea>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label>Highlight Color</label>
                            <div style="display: flex; gap: 10px;">
                                <label style="cursor: pointer;"><input type="radio" name="color" value="#64b5f6" checked> <span style="display:inline-block; width:20px; height:20px; background:#64b5f6; border-radius:50%; vertical-align:middle;"></span></label>
                                <label style="cursor: pointer;"><input type="radio" name="color" value="#81c784"> <span style="display:inline-block; width:20px; height:20px; background:#81c784; border-radius:50%; vertical-align:middle;"></span></label>
                                <label style="cursor: pointer;"><input type="radio" name="color" value="#e57373"> <span style="display:inline-block; width:20px; height:20px; background:#e57373; border-radius:50%; vertical-align:middle;"></span></label>
                                <label style="cursor: pointer;"><input type="radio" name="color" value="#ffb74d"> <span style="display:inline-block; width:20px; height:20px; background:#ffb74d; border-radius:50%; vertical-align:middle;"></span></label>
                                <label style="cursor: pointer;"><input type="radio" name="color" value="#ba68c8"> <span style="display:inline-block; width:20px; height:20px; background:#ba68c8; border-radius:50%; vertical-align:middle;"></span></label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-full" style="margin: 0;">Save Event</button>
                    </form>
                </div>
            </div>

            <!-- View Event Modal -->
            <div id="viewEventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
                <div class="add-item-box" style="margin: 0; width: 100%; max-width: 400px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <h3 id="veTitle" style="margin: 0; font-size: 20px;"></h3>
                        <button class="btn btn-ghost" style="padding: 5px 10px;" onclick="document.getElementById('viewEventModal').style.display='none'">×</button>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Date:</strong> <span id="veDate"></span></p>
                        <p style="margin: 5px 0;"><strong>Time:</strong> <span id="veTime"></span></p>
                        <p style="margin: 5px 0;"><strong>Client:</strong> <span id="veClient"></span></p>
                        <p style="margin: 15px 0 5px 0;"><strong>Description:</strong></p>
                        <p id="veDesc" style="margin: 0; background: #f9f9f9; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; font-size: 13px; max-height: 200px; overflow-y: auto;"></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-ghost" style="flex: 1; border: 1px solid var(--border);" onclick="openEditEvent()">Edit Event</button>
                        <form method="POST" action="admin.php?tab=calendar&month=<?php echo $cal_month; ?>&year=<?php echo $cal_year; ?>" onsubmit="return confirm('Are you sure you want to delete this event?');" style="flex: 1; margin: 0;">
                            <input type="hidden" name="delete_event" value="1">
                            <input type="hidden" name="event_id" id="veId" value="">
                            <button type="submit" class="btn" style="background: #cc3333; width: 100%; margin: 0;">Delete Event</button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            let currentEvent = null;
            function viewEvent(evt) {
                currentEvent = evt;
                document.getElementById('veTitle').textContent = evt.title;
                document.getElementById('veDate').textContent = evt.event_date;
                document.getElementById('veTime').textContent = evt.event_time || 'N/A';
                document.getElementById('veClient').textContent = evt.client_name || 'N/A';
                document.getElementById('veDesc').textContent = evt.description || 'No description.';
                document.getElementById('veId').value = evt.id;
                document.getElementById('viewEventModal').style.display = 'flex';
            }
            
            function openEditEvent() {
                if (!currentEvent) return;
                document.getElementById('viewEventModal').style.display = 'none';
                
                // Repurpose Add Event modal for Editing
                const modal = document.getElementById('eventModal');
                modal.querySelector('h3').textContent = 'Edit Event';
                
                const form = modal.querySelector('form');
                const actionInput = form.querySelector('input[name="add_event"]') || form.querySelector('input[name="edit_event"]');
                if (actionInput) actionInput.name = 'edit_event';
                
                // Add hidden event_id if not exists
                let idInput = form.querySelector('input[name="event_id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'event_id';
                    form.appendChild(idInput);
                }
                idInput.value = currentEvent.id;
                
                form.querySelector('input[name="event_date"]').value = currentEvent.event_date;
                form.querySelector('input[name="event_time"]').value = currentEvent.event_time;
                form.querySelector('input[name="title"]').value = currentEvent.title;
                form.querySelector('input[name="client_name"]').value = currentEvent.client_name;
                form.querySelector('textarea[name="description"]').value = currentEvent.description;
                
                const radios = form.querySelectorAll('input[name="color"]');
                radios.forEach(r => {
                    if (r.value === currentEvent.color) r.checked = true;
                });
                
                form.querySelector('button[type="submit"]').textContent = 'Update Event';
                modal.style.display = 'flex';
            }
            
            // Reset modal when closing so it can be used for "Add New" cleanly
            document.querySelector('#eventModal .btn-ghost').addEventListener('click', function() {
                const modal = document.getElementById('eventModal');
                modal.querySelector('h3').textContent = 'Add New Event';
                const form = modal.querySelector('form');
                const actionInput = form.querySelector('input[name="edit_event"]');
                if (actionInput) actionInput.name = 'add_event';
                form.reset();
                form.querySelector('button[type="submit"]').textContent = 'Save Event';
            });
            </script>
        <?php elseif ($active_tab === 'showcase'): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn" onclick="openAddProject()">+ Add New Project</button>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Project Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($showcase)): ?>
                            <tr><td colspan="3" style="text-align:center; color: var(--muted); padding: 30px;">No showcase items yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($showcase as $proj): ?>
                            <tr>
                                <td style="width: 120px;">
                                    <img src="<?php echo htmlspecialchars($proj['image_path']); ?>" style="width: 100px; height: 75px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border);">
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 15px; margin-bottom: 5px;"><?php echo htmlspecialchars($proj['title']); ?></div>
                                    <div style="font-size: 13px; color: var(--muted); line-height: 1.5; max-width: 340px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($proj['description']); ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 10px;">
                                        <?php $proj_json = htmlspecialchars(json_encode($proj), ENT_QUOTES, 'UTF-8'); ?>
                                        <button class="btn btn-ghost" style="border: 1px solid var(--border); padding: 6px 12px;" onclick='openEditProject(<?php echo $proj_json; ?>)'>Edit</button>
                                        <form method="POST" action="admin.php?tab=showcase" onsubmit="return confirm('Move to trash bin?');" style="margin:0; display: inline-block;">
                                            <input type="hidden" name="delete_showcase" value="1">
                                            <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                                            <button type="submit" class="btn" style="background:#cc3333; padding: 6px 12px;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'logs'): ?>
            <div style="height: 24px;"></div>
            <div class="logs-filter-box" style="margin-bottom: 40px; padding: 20px 22px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);">
                <form method="GET" action="admin.php" style="margin: 0;">
                    <input type="hidden" name="tab" value="logs">
                    <div class="form-group" style="margin: 0 0 12px 0;">
                        <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); display: block; margin-bottom: 7px;">Search by Date</label>
                        <input type="date" name="log_date" class="text-input" value="<?php echo htmlspecialchars($_GET['log_date'] ?? ''); ?>" style="padding: 8px; width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="display: block; width: 100%;">
    <button type="submit" class="btn" style="width: 100%; padding: 10px; box-sizing: border-box;">Filter</button>
                        <?php if (isset($_GET['log_date']) && $_GET['log_date']): ?>
                            <a href="admin.php?tab=logs" class="btn btn-ghost" style="border: 1px solid var(--border); width: 100%; padding: 10px; text-align: center; display: block; box-sizing: border-box; margin-top: 8px;">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" style="text-align:center; color: var(--muted); padding: 30px;">No logs yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space:nowrap; font-size: 13px; color: var(--muted);"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                <td style="font-size: 13px;"><?php echo htmlspecialchars($log['details']); ?></td>
                                <td style="font-size: 12px; color: var(--muted);"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'trash'): ?>
            <form id="trashBulkForm" method="POST" action="admin.php?tab=trash"></form>
            
            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="bulk_trash_restore" form="trashBulkForm" class="btn" style="background: #28a745; padding: 8px 16px;">Restore Selected</button>
                    <button type="submit" name="bulk_trash_delete" form="trashBulkForm" class="btn" style="background: #cc3333; padding: 8px 16px;" onclick="return confirm('Permanently delete selected items? This cannot be undone.');">Delete Permanently</button>
                </div>
                <div style="font-size: 13px; color: var(--muted);">
                    <input type="checkbox" id="selectAllTrash" onclick="toggleSelectAllTrash(this)" style="vertical-align: middle; margin-right: 5px;">
                    <label for="selectAllTrash" style="cursor: pointer; vertical-align: middle;">Select All</label>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"></th>
                            <th>Deleted At</th>
                            <th>Type</th>
                            <th>Item Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trash_items)): ?>
                            <tr><td colspan="4" style="text-align:center; color: var(--muted); padding: 30px;">Trash bin is empty.</td></tr>
                        <?php else: ?>
                            <?php foreach ($trash_items as $item): ?>
                            <tr>
                                <td style="text-align: center;"><input type="checkbox" name="trash_items[]" value="<?php echo $item['item_type'] . ':' . $item['id']; ?>" form="trashBulkForm" class="trash-checkbox"></td>
                                <td style="white-space:nowrap; font-size: 13px; color: var(--muted);"><?php echo date('M d, H:i:s', strtotime($item['deleted_at'])); ?></td>
                                <td><span style="background:rgba(0,0,0,0.06); padding:3px 8px; border-radius:4px; font-size:11px; text-transform:uppercase;"><?php echo $item['item_type']; ?></span></td>
                                <td>
                                    <div style="font-weight: 500; font-size: 14px; margin-bottom: 5px;">
                                        <?php 
                                            if ($item['item_type'] === 'inquiry') echo htmlspecialchars($item['fullname']) . ' - ' . htmlspecialchars($item['project_type']);
                                            elseif ($item['item_type'] === 'inventory') echo htmlspecialchars($item['item_name']);
                                            elseif ($item['item_type'] === 'folder') echo "📁 " . htmlspecialchars($item['name']);
                                            elseif ($item['item_type'] === 'quotation') echo "📄 " . htmlspecialchars($item['title']);
                                            elseif ($item['item_type'] === 'event') echo "📅 " . htmlspecialchars($item['title']);
                                            elseif ($item['item_type'] === 'showcase') echo "🖼️ " . htmlspecialchars($item['title']);
                                        ?>
                                    </div>
                                    <div style="display: flex; gap: 15px;">
                                        <form method="POST" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="restore_item" value="1">
                                            <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" style="background:none; border:none; color:#28a745; cursor:pointer; font-size:12px; padding:0; text-decoration:underline;">Restore Item</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Permanently delete? This cannot be undone.');" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="permanent_delete" value="1">
                                            <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" style="background:none; border:none; color:#cc3333; cursor:pointer; font-size:12px; padding:0; text-decoration:underline;">Delete Permanently</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

            <!-- Project Modal (Add/Edit) -->
            <div id="projectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
                <div class="add-item-box" style="margin: 0; width: 100%; max-width: 500px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 id="modalTitle" style="margin: 0;">Add Project</h3>
                        <button class="btn btn-ghost" style="padding: 5px 10px;" onclick="closeProjectModal()">×</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_showcase" id="modalAction" value="1">
                        <input type="hidden" name="project_id" id="modalProjId" value="">
                        <input type="hidden" name="existing_image" id="modalExistingImg" value="">

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Project Title</label>
                            <input type="text" name="title" id="modalProjTitle" class="text-input" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Description</label>
                            <textarea name="description" id="modalProjDesc" class="text-input" style="height: 120px; resize: vertical; width: 100%; box-sizing: border-box;" required></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label>Project Image</label>
                            <div id="imagePreview" style="margin-bottom: 10px; display: none;">
                                <img src="" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                            </div>
                            <input type="file" name="image" id="modalProjFile" accept="image/*">
                            <p style="font-size: 11px; color: var(--muted); margin-top: 5px;">Upload a high-quality photo of the project.</p>
                        </div>

                        <button type="submit" class="btn btn-full" id="modalSubmitBtn" style="margin: 0;">Save Project</button>
                    </form>
                </div>
            </div>

            <script>
            function openAddProject() {
                document.getElementById('modalTitle').textContent = 'Add New Project';
                document.getElementById('modalAction').name = 'add_showcase';
                document.getElementById('modalProjId').value = '';
                document.getElementById('modalProjTitle').value = '';
                document.getElementById('modalProjDesc').value = '';
                document.getElementById('modalExistingImg').value = '';
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('modalProjFile').required = true;
                document.getElementById('projectModal').style.display = 'flex';
            }

            function openEditProject(proj) {
                document.getElementById('modalTitle').textContent = 'Edit Project';
                document.getElementById('modalAction').name = 'edit_showcase';
                document.getElementById('modalProjId').value = proj.id;
                document.getElementById('modalProjTitle').value = proj.title;
                document.getElementById('modalProjDesc').value = proj.description;
                document.getElementById('modalExistingImg').value = proj.image_path;
                
                const preview = document.getElementById('imagePreview');
                preview.querySelector('img').src = proj.image_path;
                preview.style.display = 'block';
                
                document.getElementById('modalProjFile').required = false;
                document.getElementById('projectModal').style.display = 'flex';
            }

            function closeProjectModal() {
                document.getElementById('projectModal').style.display = 'none';
            }
            </script>
    </div>
<?php endif; ?>

<script>
function toggleEdit(id) {
    const viewRow = document.getElementById('inv_row_' + id);
    const editRow = document.getElementById('inv_edit_row_' + id);
    if (viewRow.style.display === 'none') {
        viewRow.style.display = '';
        editRow.style.display = 'none';
    } else {
        viewRow.style.display = 'none';
        editRow.style.display = '';
    }
}

function searchInventory() {
    let input = document.getElementById('inventorySearch');
    if (!input) return;
    let filter = input.value.toLowerCase();
    let table = document.getElementById('inventoryTable');
    if (!table) return;
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Skip header row
        let td = tr[i].getElementsByTagName('td')[0]; // Item Name column
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}

// Auto-hide notifications after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-success, .alert-error');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                if (alert.classList.contains('no-hide')) return;
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 3000);
    }
    
    const inquiriesBody = document.getElementById('inquiriesBody');
    if (inquiriesBody) {
        inquiriesBody.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('inq-checkbox')) {
                const checkboxes = document.querySelectorAll('.inq-checkbox');
                const selectAll = document.getElementById('selectAll');
                if (checkboxes.length > 0 && selectAll) {
                    let allChecked = true;
                    checkboxes.forEach(cb => { if (!cb.checked) allChecked = false; });
                    selectAll.checked = allChecked;
                }
            }
        });
    }
});

// Live Consultations Polling (runs on all tabs to keep the badge updated)
setInterval(fetchLiveInquiries, 5000); // Every 5 seconds

function fetchLiveInquiries() {
    const params = new URLSearchParams(window.location.search);
    const statusFilter = params.get('status_filter') || 'all';
    fetch('admin.php?fetch_live_inquiries=1&status_filter=' + encodeURIComponent(statusFilter))
        .then(response => response.json())
        .then(data => {
            const body = document.getElementById('inquiriesBody');
            const badge = document.getElementById('unreadBadge');
            
            if (badge) {
                badge.textContent = data.unread_count;
                badge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
            }

            if (body) {
                // Capture currently checked checkboxes to preserve state
                const checkedIds = new Set();
                document.querySelectorAll('.inq-checkbox:checked').forEach(cb => checkedIds.add(cb.value));

                let html = '';
                if (data.inquiries.length === 0) {
                    html = '<tr><td colspan="8" style="text-align:center; color: var(--muted); padding: 30px;">No consultations yet.</td></tr>';
                } else {
                    const selectAll = document.getElementById('selectAll');
                    data.inquiries.forEach(inq => {
                        const isChecked = checkedIds.has(inq.id.toString()) || (selectAll && selectAll.checked);
                        const source = inq.source || 'contact';
                        const sourceColor = source === 'estimator' ? '#1565c0' : '#2e7d32';
                        const sourceBg = source === 'estimator' ? '#e3f2fd' : '#e8f5e9';
                        const contactName = inq.contact_name && inq.contact_name !== inq.fullname
                            ? `<br><span style="color:var(--muted); font-size:12px;">Contact: ${escapeHtml(inq.contact_name)}</span>`
                            : '';
                        const contactNumber = inq.contact_number && inq.contact_number !== inq.phone
                            ? `<br><span style="color:var(--muted); font-size:12px;">${escapeHtml(inq.contact_number)}</span>`
                            : '';
                        const status = inq.status || 'unresolved';
                        const statusHtml = status === 'resolved'
                            ? `
                                <div class="consultation-status-actions">
                                    <span class="status-pill resolved">Resolved</span>
                                    <form method="POST" action="admin.php?tab=consultations&status_filter=${encodeURIComponent(statusFilter)}" style="margin:0;">
                                        <input type="hidden" name="toggle_inquiry_status" value="1">
                                        <input type="hidden" name="inquiry_id" value="${inq.id}">
                                        <input type="hidden" name="status" value="unresolved">
                                        <button type="submit" class="mark-unresolved-btn">Mark Unresolved</button>
                                    </form>
                                </div>`
                            : `
                                <div class="consultation-status-actions">
                                    <span class="status-pill unresolved">Unresolved</span>
                                    <form method="POST" action="admin.php?tab=consultations&status_filter=${encodeURIComponent(statusFilter)}" style="margin:0;">
                                        <input type="hidden" name="toggle_inquiry_status" value="1">
                                        <input type="hidden" name="inquiry_id" value="${inq.id}">
                                        <input type="hidden" name="status" value="resolved">
                                        <button type="submit" class="resolve-btn">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            Resolve
                                        </button>
                                    </form>
                                </div>`;
                        html += `
                        <tr>
                            <td style="text-align: center;"><input type="checkbox" name="inquiry_ids[]" value="${inq.id}" class="inq-checkbox" ${isChecked ? 'checked' : ''}></td>
                            <td style="color:var(--muted); font-size:12px;">${inq.formatted_date}</td>
                            <td><strong>${escapeHtml(inq.fullname)}</strong>${contactName}</td>
                            <td>
                                ${escapeHtml(inq.email)}<br>
                                <span style="color:var(--muted); font-size:12px;">${escapeHtml(inq.phone)}</span>
                                ${contactNumber}
                            </td>
                            <td><span style="background:rgba(0,0,0,0.06); padding:4px 6px; border-radius:4px; font-size:11px; line-height:1.25; display:inline-block;">${escapeHtml(inq.project_type)}</span></td>
                            <td><span style="background:${sourceBg}; color:${sourceColor}; padding:3px 6px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; line-height:1.2; display:inline-block;">${escapeHtml(source)}</span></td>
                            <td style="font-size:12px; line-height:1.45;">${escapeHtml(inq.message).replace(/\\n/g, '<br>')}</td>
                            <td class="consultation-status-cell">${statusHtml}</td>
                        </tr>`;
                    });
                }
                body.innerHTML = html;
                
                const checkboxes = document.querySelectorAll('.inq-checkbox');
                const selectAll = document.getElementById('selectAll');
                if (checkboxes.length > 0 && selectAll) {
                    let allChecked = true;
                    checkboxes.forEach(cb => { if (!cb.checked) allChecked = false; });
                    selectAll.checked = allChecked;
                }
            }
        })
        .catch(err => console.error('Error fetching live inquiries:', err));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.inq-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}

function toggleSelectAllTrash(master) {
    const checkboxes = document.querySelectorAll('.trash-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>

<!-- ── Pre-Quotation Details Modal ── -->
<div id="preQuoteViewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:3000; align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius: var(--radius); padding: 30px; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; margin: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <h2 style="margin:0; font-size: 20px; font-weight: 600;" id="pqModalTitle">Pre-Quotation Details</h2>
      <button onclick="closePreQuoteModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--muted); line-height: 1;">×</button>
    </div>
    
    <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; background: #fdfdfd; padding: 15px; border-radius: 6px; border: 1px solid var(--border);">
      <div>
        <h4 style="margin: 0 0 8px 0; font-size: 11px; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px;">Customer Details</h4>
        <strong id="pqModalClient">Name</strong>
        <div id="pqModalEmail" style="color: var(--muted); margin-top: 2px;">Email</div>
        <div id="pqModalPhone" style="color: var(--muted);">Phone</div>
      </div>
      <div>
        <h4 style="margin: 0 0 8px 0; font-size: 11px; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px;">Project Configuration</h4>
        <div id="pqModalConfig">Config</div>
      </div>
    </div>
    
    <h4 style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px;">Estimated Materials List</h4>
    <div class="table-wrap" style="margin-bottom: 20px;">
      <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
          <tr style="background: #fafafa; border-bottom: 1px solid var(--border);">
            <th style="padding: 8px 10px; text-align: left;">Item Description</th>
            <th style="padding: 8px 10px; text-align: right; width: 80px;">Qty</th>
            <th style="padding: 8px 10px; text-align: right; width: 80px;">Price</th>
            <th style="padding: 8px 10px; text-align: right; width: 100px;">Total</th>
          </tr>
        </thead>
        <tbody id="pqModalItemsRows">
          <!-- Dynamic rows -->
        </tbody>
        <tfoot>
          <tr style="border-top: 1px solid var(--border); font-weight: bold;">
            <td colspan="3" style="text-align: right; padding: 10px;">Grand Total Estimate:</td>
            <td style="text-align: right; padding: 10px; font-size: 15px; color: var(--text);" id="pqModalTotal">₱0.00</td>
          </tr>
        </tfoot>
      </table>
    </div>
    
    <div style="display:flex; justify-content:flex-end;">
      <button onclick="closePreQuoteModal()" class="btn">Close</button>
    </div>
  </div>
</div>

<script>
function viewPreQuoteDetails(pq) {
  document.getElementById('pqModalTitle').textContent = `Pre-Quotation Details (ID: ${pq.id})`;
  document.getElementById('pqModalClient').textContent = pq.customer_name;
  document.getElementById('pqModalEmail').textContent = pq.customer_email;
  document.getElementById('pqModalPhone').textContent = pq.customer_phone;
  
  const typeLabel = pq.project_type.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
  const buildLabel = pq.building_type.replace(/\b\w/g, c => c.toUpperCase());
  const tierLabel = pq.material_level.replace(/\b\w/g, c => c.toUpperCase());
  
  document.getElementById('pqModalConfig').innerHTML = `
    <strong>Type:</strong> ${typeLabel} (${buildLabel})<br>
    <strong>Dimensions:</strong> ${parseFloat(pq.sqm).toFixed(2)} sqm / ${pq.floors} floor${pq.floors > 1 ? 's' : ''}<br>
    <strong>Material Tier:</strong> ${tierLabel}
  `;
  
  // Parse items
  let items = [];
  try {
    items = JSON.parse(pq.items_json) || [];
  } catch(e) {
    console.error("Error parsing items JSON", e);
  }
  
  const tbody = document.getElementById('pqModalItemsRows');
  tbody.innerHTML = '';
  
  let matTotal = 0;
  items.forEach(it => {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
    tr.innerHTML = `
      <td style="padding: 8px 10px; text-align: left;">${it.name}</td>
      <td style="padding: 8px 10px; text-align: right;">${it.qty} ${it.unit}</td>
      <td style="padding: 8px 10px; text-align: right;">₱${parseFloat(it.price).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
      <td style="padding: 8px 10px; text-align: right; font-weight: 500;">₱${parseFloat(it.total).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    `;
    tbody.appendChild(tr);
    matTotal += parseFloat(it.total) || 0;
  });
  
  // Calculate Labor / Services
  const total = parseFloat(pq.estimated_total) || 0;
  const vat = total - (total / 1.12);
  const direct = total - vat;
  const laborTotal = direct - matTotal;
  
  // Add Labor/Services row if it's positive
  if (laborTotal > 0) {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
    tr.innerHTML = `
      <td style="padding: 8px 10px; text-align: left; font-style: italic; color: #555;">Labor, Equipment & Contractor Services (Estimated)</td>
      <td style="padding: 8px 10px; text-align: right;">1 lot</td>
      <td style="padding: 8px 10px; text-align: right;">₱${laborTotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
      <td style="padding: 8px 10px; text-align: right; font-weight: 500;">₱${laborTotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    `;
    tbody.appendChild(tr);
  }
  
  // Add VAT row
  if (vat > 0) {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
    tr.innerHTML = `
      <td style="padding: 8px 10px; text-align: left; font-style: italic; color: #555;">VAT (12%)</td>
      <td style="padding: 8px 10px; text-align: right;">—</td>
      <td style="padding: 8px 10px; text-align: right;">—</td>
      <td style="padding: 8px 10px; text-align: right; font-weight: 500;">₱${vat.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    `;
    tbody.appendChild(tr);
  }
  
  document.getElementById('pqModalTotal').textContent = `₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  
  document.getElementById('preQuoteViewModal').style.display = 'flex';
}

function closePreQuoteModal() {
  document.getElementById('preQuoteViewModal').style.display = 'none';
}
</script>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
</body>
</html>
