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

// 獲取報告類型
$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$report_title = array(
    'sales' => 'Sales Report',
    'inventory' => 'Inventory Report',
    'orders' => 'Order Report',
    'customers' => 'Customer Report'
);

// ===== 獲取報告數據 =====
$data = array();

if ($report_type == 'sales') {
    $sql = "SELECT 
                o.oid,
                o.odate,
                o.ototalamount,
                o.ostatus,
                c.cname AS customer_name
            FROM orders o
            LEFT JOIN customers c ON o.cid = c.cid
            WHERE DATE(o.odate) BETWEEN '$from_date' AND '$to_date'
            ORDER BY o.odate DESC";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
} elseif ($report_type == 'inventory') {
    $sql = "SELECT 
                mid,
                mname,
                mqty,
                munit,
                CASE 
                    WHEN mqty <= 0 THEN 'Out of Stock'
                    WHEN mqty < 20 THEN 'Low Stock'
                    ELSE 'In Stock'
                END AS status
            FROM materials
            ORDER BY mid";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
} elseif ($report_type == 'orders') {
    $sql = "SELECT 
                o.oid,
                o.odate,
                o.ototalamount,
                o.ostatus,
                o.odeliverydate,
                c.cname AS customer_name
            FROM orders o
            LEFT JOIN customers c ON o.cid = c.cid
            WHERE DATE(o.odate) BETWEEN '$from_date' AND '$to_date'
            ORDER BY o.odate DESC";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
} elseif ($report_type == 'customers') {
    $sql = "SELECT 
                cid,
                cname,
                ctel,
                caddr,
                company
            FROM customers
            ORDER BY cid";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = $row;
        }
    }
}

// 計算統計數據
$total_count = count($data);
$total_amount = 0;
if ($report_type == 'sales' || $report_type == 'orders') {
    foreach ($data as $row) {
        $total_amount += isset($row['ototalamount']) ? $row['ototalamount'] : 0;
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
    <title><?php echo isset($report_title[$report_type]) ? $report_title[$report_type] : 'Report'; ?> - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px 35px;
            background: #f5f5f5;
            min-height: 100vh;
        }

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

        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }

        .filter-group input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
            background: #fafafa;
        }

        .filter-group input:focus {
            border-color: #141414;
        }

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

        .btn-primary {
            background: #141414;
            color: white;
        }

        .btn-primary:hover {
            background: #333;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #ccc;
            color: #2f2f2f;
        }

        .btn-secondary:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            text-align: center;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .summary-card .label {
            font-size: 13px;
            color: #888;
        }

        .data-table-section {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8f8f8;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        .data-table tr:hover td {
            background: #fafafa;
        }

        .status-badge {
            padding: 2px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 20px;
            }

            .summary-stats {
                grid-template-columns: 1fr 1fr;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
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

            .summary-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- ===== SIDEBAR ===== -->
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
                <li><a href="report_dashboard.php"  class="active">📊 Generate Report</a></li>
                <li><a href="delete_item.php">🗑️ Delete</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

        <!-- Header -->
        <div class="content-header">
            <h1><?php echo isset($report_title[$report_type]) ? $report_title[$report_type] : 'Report'; ?></h1>
            <p>Period: <?php echo $from_date; ?> to <?php echo $to_date; ?></p>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="report_generate.php" class="filter-bar">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">

            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="from" value="<?php echo $from_date; ?>">
            </div>

            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="to" value="<?php echo $to_date; ?>">
            </div>

            <a href="report_dashboard.php" class="btn btn-secondary">← Back</a>
        </form>

        <!-- Summary Stats -->
        <div class="summary-stats">
            <div class="summary-card">
                <div class="value"><?php echo $total_count; ?></div>
                <div class="label">Total Records</div>
            </div>

            <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
                <div class="summary-card">
                    <div class="value">$<?php echo number_format($total_amount, 2); ?></div>
                    <div class="label">Total Amount</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Data Table -->
        <div class="data-table-section">
            <table class="data-table">
                <thead>
                <tr>
                    <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                    <?php elseif ($report_type == 'inventory'): ?>
                        <th>Material ID</th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Unit</th>
                        <th>Status</th>
                    <?php elseif ($report_type == 'customers'): ?>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Company</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php if ($report_type == 'sales' || $report_type == 'orders'): ?>
                                <td><strong>#<?php echo $row['oid']; ?></strong></td>
                                <td><?php echo date('Y-m-d', strtotime($row['odate'])); ?></td>
                                <td><?php echo isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A'; ?></td>
                                <td>$<?php echo number_format($row['ototalamount'], 2); ?></td>
                                <td>
                                        <span class="status-badge <?php echo isset($status_color[$row['ostatus']]) ? $status_color[$row['ostatus']] : 'pending'; ?>">
                                            <?php echo isset($status_map[$row['ostatus']]) ? $status_map[$row['ostatus']] : 'Unknown'; ?>
                                        </span>
                                </td>
                            <?php elseif ($report_type == 'inventory'): ?>
                                <td>#<?php echo $row['mid']; ?></td>
                                <td><?php echo htmlspecialchars($row['mname']); ?></td>
                                <td><strong><?php echo $row['mqty']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['munit']); ?></td>
                                <td>
                                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                </td>
                            <?php elseif ($report_type == 'customers'): ?>
                                <td>#<?php echo $row['cid']; ?></td>
                                <td><?php echo htmlspecialchars($row['cname']); ?></td>
                                <td><?php echo htmlspecialchars($row['ctel']); ?></td>
                                <td><?php echo htmlspecialchars($row['caddr']); ?></td>
                                <td><?php echo isset($row['company']) ? htmlspecialchars($row['company']) : 'N/A'; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#999; padding:20px;">
                            No data found for this period
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Print Button -->
        <div class="print-btn">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="report_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back
            </a>
        </div>

    </main>
</div>

</body>
</html>