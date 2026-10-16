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

// ===== 統計數據 =====
$monthly_orders = 0;
$rs = mysqli_query($conn, "SELECT COUNT(*) AS count FROM orders WHERE MONTH(odate) = MONTH(CURRENT_DATE())");
if ($rs) { $row = mysqli_fetch_assoc($rs); $monthly_orders = $row['count']; }

$monthly_revenue = 0;
$rs = mysqli_query($conn, "SELECT SUM(ototalamount) AS total FROM orders WHERE MONTH(odate) = MONTH(CURRENT_DATE())");
if ($rs) { $row = mysqli_fetch_assoc($rs); $monthly_revenue = isset($row['total']) ? $row['total'] : 0; }

$total_orders = 0;
$rs = mysqli_query($conn, "SELECT COUNT(*) AS count FROM orders");
if ($rs) { $row = mysqli_fetch_assoc($rs); $total_orders = $row['count']; }

// ===== 獲取訂單列表（包含商品數量） =====
$orders = array();
$sql = "SELECT 
            o.oid,
            o.odate,
            o.ototalamount,
            o.ostatus,
            c.cname AS customer_name,
            (SELECT COUNT(*) FROM orderfurnitures WHERE oid = o.oid) AS item_count
        FROM orders o
        LEFT JOIN customers c ON o.cid = c.cid
        ORDER BY o.oid DESC";
$rs = mysqli_query($conn, $sql);
if ($rs) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $orders[] = $row;
    }
}

$status_map = array(1 => 'Pending', 2 => 'Processing', 3 => 'Shipped', 4 => 'Completed', 5 => 'Cancelled');
$status_color = array(1 => 'pending', 2 => 'processing', 3 => 'shipped', 4 => 'completed', 5 => 'cancelled');

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== Reset ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }

        /* ===== Dashboard Layout ===== */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar ===== */
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

        .sidebar-nav ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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

        /* ===== Main Content ===== */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px 35px;
            background: #f5f5f5;
            min-height: 100vh;
        }

        /* ===== Header ===== */
        .content-header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .content-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .content-header p {
            color: #888;
            font-size: 14px;
            margin-top: 4px;
        }

        /* ===== Stats Row ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            font-size: 28px;
            width: 52px;
            height: 52px;
            background: #f0f2f5;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-info .stat-number {
            font-size: 22px;
            font-weight: 700;
            color: #141414;
            display: block;
        }

        .stat-info .stat-label {
            font-size: 13px;
            color: #888;
        }

        /* ===== Table ===== */
        .table-section {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            overflow-x: auto;
        }

        .table-section h2 {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 16px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8f8f8;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: #fafafa;
        }

        .data-table .text-center {
            text-align: center;
        }

        /* ===== Buttons ===== */
        .btn {
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            font-family: Arial, sans-serif;
            transition: 0.15s;
        }


        .btn-primary {
            background: #141414;
            color: white;
        }

        .btn-primary:hover {
            background: #333;
        }

        /* ===== Status Badge ===== */
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 30px 0;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
            gap: 12px;
        }

        .table-footer .info {
            color: #888;
            font-size: 13px;
        }

        .table-footer .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 20px;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .table-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .table-footer .actions {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .dashboard-container {
                flex-direction: column;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }

            .btn {
                font-size: 11px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- ===== Sidebar ===== -->
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
                <li><a href="staff_dashboard.php" >🏠 Dashboard</a></li>
                <li><a href="insert_items.php">➕ Insert Item</a></li>
                <li><a href="material_list.php">🧱 Material</a></li>
                <li><a href="update_order.php">📝 Update Orders</a></li>
                <li><a href="report_dashboard.php" class="active">📊 Generate Report</a></li>
                <li><a href="delete_item.php">🗑️ Delete</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content">

        <!-- Header -->
        <div class="content-header">
            <h1>📊 Sales Report</h1>
            <p>View all orders and sales statistics</p>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <span class="stat-number">$<?php echo number_format($monthly_revenue, 0); ?></span>
                    <span class="stat-label">Monthly Revenue</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $monthly_orders; ?></span>
                    <span class="stat-label">Orders This Month</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $total_orders; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
            </div>
        </div>

        <!-- Order List -->
        <div class="table-section">
            <h2>📋 Order List</h2>

            <?php if (count($orders) > 0): ?>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th class="text-center">Items</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $order['oid']; ?></strong>
                            </td>
                            <td>
                                <?php echo isset($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'N/A'; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $order['item_count']; ?>
                            </td>
                            <td>
                                    <span class="status-badge <?php echo isset($status_color[$order['ostatus']]) ? $status_color[$order['ostatus']] : 'pending'; ?>">
                                        <?php echo isset($status_map[$order['ostatus']]) ? $status_map[$order['ostatus']] : 'Unknown'; ?>
                                    </span>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($order['ototalamount'], 2); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="table-footer">
                    <div class="info">
                        Showing <?php echo count($orders); ?> order(s)
                    </div>
                    <div class="actions">
                        <a href="report_generate.php?type=sales" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </a>
                        <a href="report_preview.php?type=sales" class="btn btn-primary" style="background:#27ae60;">
                            <i class="fas fa-print"></i> Preview Report
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="no-data">
                    <p>📭 No orders found.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>