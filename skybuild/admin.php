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
    } elseif (isset($_POST['delete_inquiry'])) {
        $inq_id = intval($_POST['inquiry_id']);
        $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->bind_param("i", $inq_id);
        $stmt->execute();
        $action_msg = "Consultation deleted successfully.";
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td style="color:var(--muted); font-size:13px;"><?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?></td>
                            <td>
                                <form method="POST" action="admin.php?tab=inventory" class="inv-form">
                                    <input type="hidden" name="update_inventory" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" class="num-input" value="<?php echo $item['quantity']; ?>" required>
                                    <button type="submit" class="btn">Save</button>
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
</script>

<?php include 'components/footer.php'; ?>
</body>
</html>
