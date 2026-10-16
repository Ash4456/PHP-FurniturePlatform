<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: loginstaff.php");
    exit();
}

// ===== 資料庫連線 =====
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Error: " . mysqli_connect_error());
}
// ===================================

$message = '';
$message_type = '';

// 獲取下一個 Material ID
$next_id = 1;
$id_sql = "SELECT MAX(mid) as max_id FROM materials";
$id_result = mysqli_query($conn, $id_sql);
if ($id_result) {
    $row = mysqli_fetch_assoc($id_result);
    $next_id = ($row['max_id'] ?: 0) + 1;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mname = trim($_POST['material_name']);
    $mqty = intval($_POST['stock_qty']);
    $munit = trim($_POST['unit']);

    if (empty($mname) || empty($munit)) {
        $message = "❌ Material Name and Unit are required!";
        $message_type = "error";
    } else {
        $mname = mysqli_real_escape_string($conn, $mname);
        $munit = mysqli_real_escape_string($conn, $munit);

        $sql = "INSERT INTO materials (mname, mqty, munit) VALUES ('$mname', '$mqty', '$munit')";

        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            $message = "✅ Material added successfully！Material ID: #$new_id";
            $message_type = "success";
        } else {
            $message = "❌ Failed！" . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Material - Premium Living</title>
    <style>
        /* 使用與 insert_items.php 相同的樣式 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; }

        .dashboard-container { display: flex; min-height: 100vh; }

        .sidebar {
            width: 250px;
            background: #141414;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid #333; text-align: center; }
        .sidebar-logo { width: 80px; height: 80px; object-fit: contain; border-radius: 12px; background: white; padding: 6px; margin-bottom: 10px; }
        .sidebar-header h2 { font-size: 20px; font-weight: 600; color: #fff; }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav ul li { margin: 4px 12px; }
        .sidebar-nav ul li a {
            display: block; padding: 12px 18px; color: #ccc; text-decoration: none;
            border-radius: 12px; font-size: 15px; font-weight: 500; transition: 0.2s;
        }
        .sidebar-nav ul li a:hover { background: #2a2a2a; color: white; }
        .sidebar-nav ul li a.active { background: #2a2a2a; color: white; font-weight: 600; }

        .staff-info { padding: 12px 18px; color: #aaa; font-size: 14px; border-bottom: 1px solid #333; margin-bottom: 8px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid #333; }
        .logout-btn { display: block; padding: 12px 18px; color: #ff6b6b; text-decoration: none; border-radius: 12px; font-size: 15px; font-weight: 500; text-align: center; }
        .logout-btn:hover { background: #2a1a1a; color: #ff4444; }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px 35px;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .page-header h1 { font-size: 24px; font-weight: 700; color: #141414; }
        .page-header p { color: #888; font-size: 14px; margin-top: 4px; }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; color: #2c2c2c; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
            background: #fafafa;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
            background: white;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            font-family: Arial, sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
        }

        .btn-primary { background: #141414; color: white; }
        .btn-primary:hover { background: #333; }

        .btn-secondary { background: transparent; border: 1px solid #ccc; color: #2f2f2f; }
        .btn-secondary:hover { background: #f0efea; border-color: #aaa; }

        .msg {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .msg.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c3e6cb; }
        .msg.error { background: #fce4ec; color: #c0392b; border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .form-actions { flex-direction: column; }
            .form-actions button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="logo.png" alt="Logo" class="sidebar-logo">
            <h2>Staff Portal</h2>
        </div>

        <div class="staff-info">
            👤 Welcome, <?php echo htmlspecialchars(isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Staff'); ?>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li><a href="staff_dashboard.php">🏠 Dashboard</a></li>
                <li><a href="insert_items.php">➕ Insert Item</a></li>
                <li><a href="material_list.php">🧱 Material</a></li>
                <li><a href="update_order.php">📝 Update Orders</a></li>
                <li><a href="report_dashboard.php">📊 Generate Report</a></li>
                <li><a href="delete_item.php">🗑️ Delete</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <h1>🧱 Insert New Material</h1>
            <p>Add raw materials to inventory</p>
        </div>

        <div class="form-card">

            <?php if ($message): ?>
                <div class="msg <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="insert_material.php">

                <div class="form-row">
                    <div class="form-group">
                        <label for="material-id">Material ID</label>
                        <input type="text" id="material-id" value="#<?php echo $next_id; ?>" readonly style="background:#f5f5f5; color:#888;">
                    </div>
                    <div class="form-group">
                        <label for="material-name">Material Name *</label>
                        <input type="text" id="material-name" name="material_name" placeholder="e.g., Oak Wood Plank" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit of Measurement *</label>
                        <select id="unit" name="unit" required>
                            <option value="">Select Unit</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="m">Meters (m)</option>
                            <option value="sqm">Square Meters (sqm)</option>
                            <option value="L">Liters (L)</option>
                            <option value="sheets">Sheets</option>
                            <option value="rolls">Rolls</option>
                            <option value="boxes">Boxes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock-qty">Current Stock Quantity *</label>
                        <input type="number" id="stock-qty" name="stock_qty" min="0" placeholder="0" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">➕ Add Material</button>
                    <button type="reset" class="btn btn-secondary">↩️ Reset</button>
                    <a href="material_list.php" class="btn btn-secondary">⬅️ Back to List</a>
                </div>

            </form>
        </div>

    </main>
</div>

</body>
</html>