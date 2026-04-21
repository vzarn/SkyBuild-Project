<?php
session_start();

// --- 1. Database Initialization ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skybuild";

// Connect to MySQL server first (without DB)
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Create inquiries table
$conn->query("CREATE TABLE IF NOT EXISTS inquiries (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    project_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)"); // Note: the closing parenthesis of CREATE TABLE inquiries

// Add is_read to inquiries if not exists
$res = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'is_read'");
if ($res && $res->num_rows == 0) {
    $conn->query("ALTER TABLE inquiries ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

// Create inventory table
$conn->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Pre-fill inventory if empty
$res = $conn->query("SELECT COUNT(*) AS cnt FROM inventory");
if ($res && $res->fetch_assoc()['cnt'] == 0) {
    $conn->query("INSERT INTO inventory (item_name, quantity) VALUES 
        ('Pipe (PVC 2 inch)', 50), 
        ('Steel Rebar (10mm)', 200), 
        ('Screwdriver Set', 15)");
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

// --- 2. Authentication ---
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $admin_user = $_POST['username'] ?? '';
    $admin_pass = $_POST['password'] ?? '';

    if ($admin_user === 'admin' && $admin_pass === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$is_logged_in = $_SESSION['admin_logged_in'] ?? false;

// --- 3. Handle Actions (If Logged In) ---
$action_msg = '';
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_inventory'])) {
        $item_id = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $item_id);
        $stmt->execute();
        $action_msg = "Inventory updated successfully.";
    } elseif (isset($_POST['add_inventory'])) {
        $item_name = trim($_POST['item_name']);
        $quantity = intval($_POST['quantity']);
        if ($item_name) {
            $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity) VALUES (?, ?)");
            $stmt->bind_param("si", $item_name, $quantity);
            $stmt->execute();
            $action_msg = "Item added to inventory.";
        }
    } elseif (isset($_POST['delete_inventory'])) {
        $item_id = intval($_POST['item_id']);
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $action_msg = "Item deleted from inventory.";
    } elseif (isset($_POST['delete_inquiry'])) {
        $inq_id = intval($_POST['inquiry_id']);
        $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->bind_param("i", $inq_id);
        $stmt->execute();
        $action_msg = "Consultation deleted successfully.";
    } elseif (isset($_POST['add_folder'])) {
        $folder_name = trim($_POST['folder_name']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        if ($folder_name) {
            $stmt = $conn->prepare("INSERT INTO quote_folders (parent_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $parent_id, $folder_name);
            $stmt->execute();
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
        $action_msg = "Quotation saved successfully.";
    } elseif (isset($_POST['delete_folder'])) {
        $folder_id = intval($_POST['folder_id']);
        $stmt = $conn->prepare("DELETE FROM quote_folders WHERE id = ?");
        $stmt->bind_param("i", $folder_id);
        $stmt->execute();
        $action_msg = "Folder deleted.";
    } elseif (isset($_POST['delete_quotation'])) {
        $quote_id = intval($_POST['quotation_id']);
        $stmt = $conn->prepare("DELETE FROM quotations WHERE id = ?");
        $stmt->bind_param("i", $quote_id);
        $stmt->execute();
        $action_msg = "Quotation deleted.";
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
        $action_msg = "Event added to calendar.";
    } elseif (isset($_POST['delete_event'])) {
        $event_id = intval($_POST['event_id']);
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $action_msg = "Event deleted.";
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
        $action_msg = "Project updated.";
    } elseif (isset($_POST['delete_showcase'])) {
        $proj_id = intval($_POST['project_id']);
        
        // Delete image file
        $stmt = $conn->prepare("SELECT image_path FROM showcase WHERE id = ?");
        $stmt->bind_param("i", $proj_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && strpos($res['image_path'], 'uploads/') === 0 && file_exists($res['image_path'])) {
            unlink($res['image_path']);
        }

        $stmt = $conn->prepare("DELETE FROM showcase WHERE id = ?");
        $stmt->bind_param("i", $proj_id);
        $stmt->execute();
        $action_msg = "Project removed from showcase.";
    }
}

// Fetch Data
$inquiries = [];
$inventory = [];
$unread_count = 0;
$active_tab = $_GET['tab'] ?? 'consultations';

if ($is_logged_in) {
    if ($active_tab === 'consultations') {
        $conn->query("UPDATE inquiries SET is_read = 1 WHERE is_read = 0");
    }

    $res = $conn->query("SELECT COUNT(*) AS unread FROM inquiries WHERE is_read = 0");
    if ($res) {
        $unread_count = $res->fetch_assoc()['unread'];
    }

    $res = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $inquiries[] = $row;
    }

    $res = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $inventory[] = $row;
    }

    $showcase = [];
    $res = $conn->query("SELECT * FROM showcase ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $showcase[] = $row;
    }
    
    if ($active_tab === 'quotations') {
        $current_folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : null;
        
        $folders = [];
        $quotations = [];
        $parent_folder = null;
        $breadcrumbs = [];
        
        if ($current_folder_id) {
            $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE id = ?");
            $stmt->bind_param("i", $current_folder_id);
            $stmt->execute();
            $parent_folder = $stmt->get_result()->fetch_assoc();
            
            // Build breadcrumbs
            $curr = $parent_folder;
            while ($curr) {
                array_unshift($breadcrumbs, $curr);
                if ($curr['parent_id']) {
                    $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE id = ?");
                    $stmt->bind_param("i", $curr['parent_id']);
                    $stmt->execute();
                    $curr = $stmt->get_result()->fetch_assoc();
                } else {
                    $curr = null;
                }
            }
            
            $stmt = $conn->prepare("SELECT * FROM quote_folders WHERE parent_id = ? ORDER BY name ASC");
            $stmt->bind_param("i", $current_folder_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $folders[] = $row;

            $stmt = $conn->prepare("SELECT * FROM quotations WHERE folder_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $current_folder_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $quotations[] = $row;
        } else {
            $res = $conn->query("SELECT * FROM quote_folders WHERE parent_id IS NULL ORDER BY name ASC");
            if ($res) while ($row = $res->fetch_assoc()) $folders[] = $row;
            
            $res = $conn->query("SELECT * FROM quotations WHERE folder_id IS NULL ORDER BY created_at DESC");
            if ($res) while ($row = $res->fetch_assoc()) $quotations[] = $row;
        }
        
        // Fetch a specific quote if requested
        $view_quote = null;
        $view_quote_items = [];
        if (isset($_GET['view_quote'])) {
            $quote_id = intval($_GET['view_quote']);
            $stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
            $stmt->bind_param("i", $quote_id);
            $stmt->execute();
            $view_quote = $stmt->get_result()->fetch_assoc();
            
            if ($view_quote) {
                $stmt = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
                $stmt->bind_param("i", $quote_id);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $view_quote_items[] = $row;
            }
        }
    }
    
    $events = [];
    if ($active_tab === 'calendar') {
        $cal_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $cal_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        $start_date = "$cal_year-" . str_pad($cal_month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date("Y-m-t", strtotime($start_date));
        
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_date >= ? AND event_date <= ? ORDER BY event_date ASC, event_time ASC");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $events[$row['event_date']][] = $row;
        }
    }
}
$conn->close();
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
            max-width: 1000px; 
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
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .tabs a {
            text-decoration: none;
            color: var(--muted);
            padding: 8px 16px;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
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
        
        .inv-form { display: flex; gap: 8px; align-items: center; margin: 0; }
        .inv-form input.num-input { width: 80px; padding: 8px; }
        .inv-form .btn { padding: 8px 12px; }
        
        .add-item-box {
            background: var(--surface);
            padding: 24px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-top: 24px;
            display: inline-block;
        }
        .add-item-box h3 { margin-bottom: 16px; font-size: 16px; font-weight: 500; margin-top: 0; }
        .add-item-form { display: flex; gap: 16px; align-items: flex-end; margin: 0; }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <div class="login-wrapper">
        <div class="login-box">
            <h1>Admin Access</h1>
            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
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
            <a href="?tab=consultations" class="<?php echo $active_tab === 'consultations' ? 'active' : ''; ?>">
                Consultations
                <?php if ($unread_count > 0): ?>
                    <span style="background: #cc3333; color: white; border-radius: 10px; padding: 2px 7px; font-size: 11px; margin-left: 6px; font-weight: bold;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=inventory" class="<?php echo $active_tab === 'inventory' ? 'active' : ''; ?>">Inventory</a>
            <a href="?tab=quotations" class="<?php echo $active_tab === 'quotations' ? 'active' : ''; ?>">Quotations</a>
            <a href="?tab=calendar" class="<?php echo $active_tab === 'calendar' ? 'active' : ''; ?>">Calendar</a>
            <a href="?tab=showcase" class="<?php echo $active_tab === 'showcase' ? 'active' : ''; ?>">Showcase</a>
        </div>

        <?php if ($active_tab === 'consultations'): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inquiries)): ?>
                            <tr><td colspan="6" style="text-align:center; color: var(--muted); padding: 30px;">No consultations yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td style="white-space:nowrap; color:var(--muted); font-size:13px;"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($inq['fullname']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($inq['email']); ?><br>
                                    <span style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($inq['phone']); ?></span>
                                </td>
                                <td><span style="background:rgba(0,0,0,0.06); padding:4px 8px; border-radius:4px; font-size:12px;"><?php echo htmlspecialchars($inq['project_type']); ?></span></td>
                                <td style="max-width:300px; font-size:13px; line-height:1.5;"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></td>
                                <td>
                                    <form method="POST" action="admin.php?tab=consultations" onsubmit="return confirm('Are you sure you want to delete this consultation?');" style="margin:0;">
                                        <input type="hidden" name="delete_inquiry" value="1">
                                        <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                        <button type="submit" class="btn" style="background:#cc3333; padding: 6px 10px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'inventory'): ?>
            <div style="margin-bottom: 20px;">
                <input type="text" id="inventorySearch" class="text-input" placeholder="Search inventory items..." style="max-width: 400px; padding: 12px 16px;" onkeyup="searchInventory()">
            </div>
            <div class="table-wrap">
                <table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Last Updated</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td style="color:var(--muted); font-size:13px;"><?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?></td>
                            <td>
                                <form method="POST" action="admin.php?tab=inventory" class="inv-form" style="display:inline-block;">
                                    <input type="hidden" name="update_inventory" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" class="num-input" value="<?php echo $item['quantity']; ?>" required>
                                    <button type="submit" class="btn">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="admin.php?tab=inventory" onsubmit="return confirm('Are you sure you want to delete this item?');" style="margin:0; display:inline-block;">
                                    <input type="hidden" name="delete_inventory" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn" style="background:#cc3333; padding: 8px 12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="add-item-box">
                <h3>Add New Item</h3>
                <form method="POST" action="admin.php?tab=inventory" class="add-item-form">
                    <input type="hidden" name="add_inventory" value="1">
                    <div class="form-group" style="margin:0; width: 200px;">
                        <label>Item Name</label>
                        <input type="text" name="item_name" class="text-input" required>
                    </div>
                    <div class="form-group" style="margin:0; width: 100px;">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="num-input" value="0" required>
                    </div>
                    <button type="submit" class="btn" style="height: 40px;">Add Item</button>
                </form>
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
            </style>

            <div class="breadcrumb">
                <a href="?tab=quotations">Quotations</a>
                <?php foreach ($breadcrumbs as $bc): ?>
                    / <a href="?tab=quotations&folder_id=<?php echo $bc['id']; ?>"><?php echo htmlspecialchars($bc['name']); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_GET['create_quote'])): ?>
                <div class="add-item-box" style="display:block; width: 100%; box-sizing: border-box;">
                    <h3>Create New Quotation</h3>
                    <form method="POST" action="admin.php?tab=quotations<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>">
                        <input type="hidden" name="add_quotation" value="1">
                        <input type="hidden" name="folder_id" value="<?php echo $current_folder_id; ?>">
                        
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
                                <label>Prepared By / Signee Name</label>
                                <input type="text" name="signee_name" class="text-input" placeholder="Name for signature">
                            </div>
                        </div>
                        
                        <div id="quoteItems">
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
                        <a href="?tab=quotations<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" class="btn btn-ghost" style="margin-left: 10px;">Cancel</a>
                    </form>
                </div>
                
                <script>
                const inventoryItems = <?php echo json_encode(array_column($inventory, 'item_name')); ?>;
                let rowCount = 0;

                function addQuoteRow() {
                    rowCount++;
                    const container = document.getElementById('quoteItems');
                    const row = document.createElement('div');
                    row.className = 'quote-form-row';
                    row.innerHTML = `
                        <div style="flex: 2; position: relative;">
                            <input type="text" name="items[${rowCount}][name]" class="text-input item-name-input" placeholder="Item Name (Start typing...)" onkeyup="filterItems(this)" autocomplete="off" required>
                            <div class="autocomplete-list"></div>
                        </div>
                        <div style="flex: 1;">
                            <input type="number" name="items[${rowCount}][qty]" class="num-input qty-input" placeholder="Qty" value="1" min="1" oninput="calcRow(this)" required>
                        </div>
                        <div style="flex: 1; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                            ₱ <input type="number" name="items[${rowCount}][price]" class="num-input price-input" placeholder="Unit Price" step="0.01" min="0" oninput="calcRow(this)" required>
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
                    
                    const matches = inventoryItems.filter(i => i.toLowerCase().includes(val));
                    if (matches.length === 0) { list.style.display = 'none'; return; }
                    
                    matches.forEach(m => {
                        const div = document.createElement('div');
                        div.textContent = m;
                        div.onclick = function() {
                            input.value = m;
                            list.style.display = 'none';
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
                <div class="add-item-box" style="display:block; width: 100%; box-sizing: border-box; background: #fff; padding: 40px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                        <div>
                            <h1 style="margin:0; font-size: 24px; font-weight: 700;">NATH Hardware and Construction Supplies</h1>
                            <p style="margin: 5px 0 20px 0; font-size: 14px; color: var(--muted);">52 Diaz St., Bahayang Pagasa, Pasong Buaya II, Imus, Cavite 1403</p>
                            <h2 style="margin:0; font-size: 22px;"><?php echo htmlspecialchars($view_quote['title']); ?></h2>
                            <?php if(!empty($view_quote['po_number'])): ?>
                                <p style="margin-top: 5px; font-weight: 500;">PO Number: <?php echo htmlspecialchars($view_quote['po_number']); ?></p>
                            <?php endif; ?>
                            <p style="color: var(--muted); margin-top: 5px;">Date: <?php echo date('M d, Y', strtotime($view_quote['created_at'])); ?></p>
                        </div>
                        <button onclick="window.print()" class="btn">Print Quotation</button>
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
                    @media print {
                        @page { margin: 0; }
                        body { margin: 1.6cm; }
                        body * { visibility: hidden; }
                        .add-item-box, .add-item-box * { visibility: visible; }
                        .add-item-box { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 0; margin: 0; }
                        .btn { display: none !important; }
                        .dashboard { padding: 0; margin: 0; max-width: 100%; }
                    }
                </style>

            <?php else: ?>
                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <a href="?tab=quotations&create_quote=1<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" class="btn">+ New Quotation</a>
                    <button class="btn btn-ghost" style="border: 1px solid var(--border);" onclick="document.getElementById('newFolderForm').style.display='block'">+ New Folder</button>
                </div>
                
                <div id="newFolderForm" class="add-item-box" style="display:none; margin-bottom: 20px; margin-top: 0;">
                    <form method="POST" action="admin.php?tab=quotations<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" class="add-item-form">
                        <input type="hidden" name="add_folder" value="1">
                        <input type="hidden" name="parent_id" value="<?php echo $current_folder_id; ?>">
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
                            <a href="?tab=quotations&folder_id=<?php echo $f['id']; ?>" class="folder-card">
                                <div class="folder-icon">📁</div>
                                <div style="font-weight: 500; font-size: 15px;"><?php echo htmlspecialchars($f['name']); ?></div>
                            </a>
                            <form method="POST" action="admin.php?tab=quotations<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" style="position: absolute; top: 10px; right: 10px; margin: 0; display: inline-block;" onsubmit="return confirm('Delete folder and ALL its contents?');">
                                <input type="hidden" name="delete_folder" value="1">
                                <input type="hidden" name="folder_id" value="<?php echo $f['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #cc3333; cursor: pointer; font-size: 16px;">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($quotations as $q): ?>
                        <div style="position: relative;">
                            <a href="?tab=quotations&view_quote=<?php echo $q['id']; ?><?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" class="quote-card">
                                <div class="quote-icon">📄</div>
                                <div style="font-weight: 500; font-size: 15px; margin-bottom: 4px;"><?php echo htmlspecialchars($q['title']); ?></div>
                                <div style="font-size: 13px; color: var(--muted);">₱<?php echo number_format($q['grand_total'], 2); ?></div>
                            </a>
                            <form method="POST" action="admin.php?tab=quotations<?php echo $current_folder_id ? '&folder_id='.$current_folder_id : ''; ?>" style="position: absolute; top: 10px; right: 10px; margin: 0; display: inline-block;" onsubmit="return confirm('Delete quotation?');">
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
                .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
                .calendar-grid { 
                    display: grid; 
                    grid-template-columns: repeat(7, 1fr); 
                    gap: 1px; 
                    background: var(--border); 
                    border: 1px solid var(--border);
                    border-radius: var(--radius);
                    overflow: hidden;
                }
                .calendar-day-header { background: #fafafa; padding: 10px; text-align: center; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
                .calendar-cell { background: #fff; min-height: 120px; padding: 10px; position: relative; }
                .calendar-cell.empty { background: #f9f9f9; }
                .calendar-date { font-weight: 500; margin-bottom: 10px; display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; border-radius: 50%; }
                .calendar-cell.today .calendar-date { background: var(--text); color: white; }
                .event-card { 
                    padding: 6px; margin-bottom: 4px; border-radius: 4px; font-size: 11px; 
                    color: #fff; line-height: 1.3; position: relative; cursor: pointer;
                }
                .event-card-title { font-weight: bold; margin-bottom: 2px; }
                .event-card-client { opacity: 0.9; }
                .event-card-time { opacity: 0.8; font-size: 10px; }
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

            <div class="calendar-grid">
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
            </div>

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
                                    <div style="font-size: 13px; color: var(--muted); line-height: 1.5; max-width: 500px;"><?php echo nl2br(htmlspecialchars($proj['description'])); ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 10px;">
                                        <?php $proj_json = htmlspecialchars(json_encode($proj), ENT_QUOTES, 'UTF-8'); ?>
                                        <button class="btn btn-ghost" style="border: 1px solid var(--border); padding: 6px 12px;" onclick='openEditProject(<?php echo $proj_json; ?>)'>Edit</button>
                                        <form method="POST" action="admin.php?tab=showcase" onsubmit="return confirm('CRITICAL: Are you sure you want to permanently remove this project from the showcase? This action cannot be undone.');" style="margin:0; display: inline-block;">
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
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
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
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 3000);
    }
});
</script>

<?php include 'components/footer.php'; ?>
</body>
</html>
