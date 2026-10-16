<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: loginstaff.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Error: " . mysqli_connect_error());
}

$mid = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($mid <= 0) {
    header("Location: material_list.php");
    exit();
}

$material = null;
$sql = "SELECT * FROM materials WHERE mid = $mid";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $material = mysqli_fetch_assoc($result);
} else {
    header("Location: material_list.php");
    exit();
}

// 查詢使用此物料的家具
$related_products = [];
$sql = "SELECT f.fid, f.fname, fm.pmqty 
        FROM furniturematerials fm 
        JOIN furnitures f ON fm.fid = f.fid 
        WHERE fm.mid = $mid";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $related_products[] = $row;
    }
}

// 獲取庫存狀態
$status = 'in-stock';
$status_text = 'In Stock';
if ($material['mqty'] <= 0) {
    $status = 'out-of-stock';
    $status_text = 'Out of Stock';
} elseif ($material['mqty'] < 20) {
    $status = 'low-stock';
    $status_text = 'Low Stock';
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Detail - #<?php echo $material['mid']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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

        .content-header {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .breadcrumb { font-size: 14px; color: #888; }
        .breadcrumb a { color: #141414; text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { font-weight: 600; color: #141414; }

        .btn {
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            font-family: Arial, sans-serif;
            transition: 0.15s;
        }

        .btn-primary { background: #141414; color: white; }
        .btn-primary:hover { background: #333; }
        .btn-secondary { background: transparent; border: 1px solid #ccc; color: #2f2f2f; }
        .btn-secondary:hover { background: #f0efea; border-color: #aaa; }

        .detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .detail-title { font-size: 28px; font-weight: 700; color: #141414; }
        .detail-id { color: #888; font-size: 14px; margin-top: 4px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 20px 0;
        }

        .info-item {
            background: #f8f9fa;
            padding: 16px 20px;
            border-radius: 12px;
            text-align: center;
        }
        .info-item .label { font-size: 13px; color: #888; display: block; }
        .info-item .value { font-size: 22px; font-weight: 700; color: #141414; display: block; }

        .status-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
        }

        .related-section { margin-top: 24px; }
        .related-section h3 { font-size: 18px; font-weight: 600; color: #141414; margin-bottom: 16px; }

        .related-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .related-item {
            background: #f8f9fa;
            padding: 12px 18px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .related-item .name { font-weight: 500; color: #141414; }
        .related-item .qty { color: #888; font-size: 14px; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .content-header { flex-direction: column; text-align: center; }
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
        <div class="staff-info">👤 Welcome, <?php echo htmlspecialchars(isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Staff'); ?></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="staff_dashboard.php">🏠 Dashboard</a></li>
                <li><a href="insert_items.php">➕ Insert Item</a></li>
                <li><a href="material_list.php" class="active">🧱 Material</a></li>
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

        <div class="content-header">
            <div class="breadcrumb">
                <a href="material_list.php">📦 Materials</a> / <span>#<?php echo $material['mid']; ?></span>
            </div>
            <a href="material_list.php" class="btn btn-secondary">← Back</a>
        </div>

        <div class="detail-card">

            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div>
                    <h1 class="detail-title"><?php echo htmlspecialchars($material['mname']); ?></h1>
                    <p class="detail-id">Material ID: <strong>#<?php echo $material['mid']; ?></strong></p>
                </div>
                <span class="status-badge <?php echo $status; ?>"><?php echo $status_text; ?></span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Unit</span>
                    <span class="value"><?php echo htmlspecialchars($material['munit']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Stock Quantity</span>
                    <span class="value"><?php echo $material['mqty']; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Status</span>
                    <span class="value" style="font-size:18px;color:<?php echo $status == 'in-stock' ? '#2e7d32' : ($status == 'low-stock' ? '#e65100' : '#c0392b'); ?>;">
                        <?php echo $status_text; ?>
                    </span>
                </div>
            </div>

        </div>

        <?php if (count($related_products) > 0): ?>
            <div class="related-section">
                <h3>🪑 Products Using This Material</h3>
                <div class="related-list">
                    <?php foreach ($related_products as $product): ?>
                        <div class="related-item">
                            <span class="name"><?php echo htmlspecialchars($product['fname']); ?></span>
                            <span class="qty">Uses: <?php echo $product['pmqty']; ?> pieces</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
            <a href="material_list.php" class="btn btn-secondary">← Back to List</a>
            <a href="insert_material.php" class="btn btn-primary">+ Add New Material</a>
        </div>

    </main>
</div>

</body>
</html>