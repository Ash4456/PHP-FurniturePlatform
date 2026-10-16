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



$furniture_list = [];
$sql = "SELECT f.fid, f.fname, f.fdesc, f.fprice, 
               GROUP_CONCAT(m.mname SEPARATOR ', ') AS materials
        FROM furnitures f
        LEFT JOIN furniturematerials fm ON f.fid = fm.fid
        LEFT JOIN materials m ON fm.mid = m.mid
        GROUP BY f.fid
        ORDER BY f.fid DESC";

$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $furniture_list[] = $row;
    }
}


$total_furnitures = count($furniture_list);


$material_count = 0;
$material_sql = "SELECT COUNT(*) as total FROM materials";
$material_result = mysqli_query($conn, $material_sql);
if ($material_result) {
    $material_row = mysqli_fetch_assoc($material_result);
    $material_count = $material_row['total'];
}


$order_count = 0;
$order_sql = "SELECT COUNT(*) as total FROM orders";
$order_result = mysqli_query($conn, $order_sql);
if ($order_result) {
    $order_row = mysqli_fetch_assoc($order_result);
    $order_count = $order_row['total'];
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Premium Living</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

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

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 12px;
            background: white;
            padding: 6px;
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav ul li {
            margin: 4px 12px;
        }

        .sidebar-nav ul li a {
            display: block;
            padding: 12px 18px;
            color: #ccc;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            transition: 0.2s;
        }

        .sidebar-nav ul li a:hover {
            background: #2a2a2a;
            color: white;
        }

        .sidebar-nav ul li a.active {
            background: #2a2a2a;
            color: white;
            font-weight: 600;
        }

        .staff-info {
            padding: 12px 18px;
            color: #aaa;
            font-size: 14px;
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #333;
        }

        .logout-btn {
            display: block;
            padding: 12px 18px;
            color: #ff6b6b;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            text-align: center;
        }

        .logout-btn:hover {
            background: #2a1a1a;
            color: #ff4444;
        }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .page-header .add-btn {
            background: #141414;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .page-header .add-btn:hover {
            background: #333;
        }


        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            text-align: center;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #141414;
        }

        .stat-card .label {
            font-size: 14px;
            color: #888;
            margin-top: 4px;
        }

        .stat-card .icon {
            font-size: 28px;
            margin-bottom: 4px;
        }


        .furniture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .furniture-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: 0.2s;
            border: 1px solid #eee;
        }

        .furniture-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .furniture-card .info {
            padding: 16px 20px 20px;
        }

        .furniture-card .name {
            font-weight: 700;
            font-size: 18px;
            color: #141414;
        }

        .furniture-card .desc {
            font-size: 14px;
            color: #666;
            margin: 6px 0 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .furniture-card .price {
            font-weight: 700;
            font-size: 20px;
            color: #141414;
        }

        .furniture-card .price small {
            font-weight: 400;
            font-size: 13px;
            color: #888;
            margin-left: 4px;
        }

        .furniture-card .materials {
            font-size: 13px;
            color: #555;
            margin-top: 6px;
            background: #f5f5f5;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .furniture-card .id-badge {
            font-size: 12px;
            color: #aaa;
            margin-top: 8px;
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            color: #333;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .furniture-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
            .dashboard-container { flex-direction: column; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .page-header { flex-direction: column; align-items: stretch; text-align: center; }
            .page-header .add-btn { justify-content: center; }
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
                <li><a href="staff_dashboard.php" class="active">🏠 Dashboard</a></li>
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
            <h2>📋 Furniture Inventory</h2>
            <a href="insert_items.php" class="add-btn">➕ Add New Item</a>
        </div>


        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🪑</div>
                <div class="number"><?php echo $total_furnitures; ?></div>
                <div class="label">Total Furniture</div>
            </div>
            <div class="stat-card">
                <div class="icon">🧱</div>
                <div class="number"><?php echo $material_count; ?></div>
                <div class="label">Total Materials</div>
            </div>
            <div class="stat-card">
                <div class="icon">📦</div>
                <div class="number"><?php echo $order_count; ?></div>
                <div class="label">Total Orders</div>
            </div>
        </div>


        <?php if (empty($furniture_list)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No furniture items found</h3>
                <p>Click "Add New Item" to create your first furniture product.</p>
            </div>
        <?php else: ?>
            <div class="furniture-grid">
                <?php foreach ($furniture_list as $item): ?>
                    <div class="furniture-card">
                        <div class="info">
                            <div class="name"><?php echo htmlspecialchars($item['fname']); ?></div>
                            <div class="desc"><?php echo htmlspecialchars($item['fdesc'] ?: 'No description'); ?></div>
                            <div class="price">$<?php echo number_format($item['fprice'], 2); ?> <small>HKD</small></div>
                            <?php if ($item['materials']): ?>
                                <div class="materials">🧱 <?php echo htmlspecialchars($item['materials']); ?></div>
                            <?php endif; ?>
                            <span class="id-badge">ID: #<?php echo $item['fid']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>