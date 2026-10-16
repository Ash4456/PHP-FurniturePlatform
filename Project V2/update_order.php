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

$message = '';
$message_type = '';
$edit_oid = isset($_GET['oid']) ? intval($_GET['oid']) : 0;
$is_edit_mode = ($edit_oid > 0);

$status_label = array(0 => 'Rejected', 1 => 'Open', 2 => 'Processing', 3 => 'Approved', 4 => 'Pending Delivery', 5 => 'Completed');
$status_class = array(0 => 'rejected', 1 => 'open', 2 => 'processing', 3 => 'approved', 4 => 'pending', 5 => 'completed');

// ===== 處理新增/更新/刪除訂單商品 =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item_to_order'])) {
    $oid = intval($_POST['oid']);
    $fid = intval($_POST['fid']);
    $qty = intval($_POST['qty']);

    if ($qty <= 0) {
        $message = "⚠️ Quantity must be at least 1.";
        $message_type = "error";
    } else {
        $check_sql = "SELECT * FROM OrderFurnitures WHERE oid = $oid AND fid = $fid";
        $check_rs = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_rs) > 0) {
            $sql_update = "UPDATE OrderFurnitures SET oqty = oqty + $qty WHERE oid = $oid AND fid = $fid";
            mysqli_query($conn, $sql_update);
        } else {
            $sql_insert = "INSERT INTO OrderFurnitures (oid, fid, oqty) VALUES ($oid, $fid, $qty)";
            mysqli_query($conn, $sql_insert);
        }

        // 更新物料庫存
        $sql_materials = "SELECT mid, pmqty FROM furniturematerials WHERE fid = $fid";
        $rs_materials = mysqli_query($conn, $sql_materials);
        while ($mat = mysqli_fetch_assoc($rs_materials)) {
            $reduce_qty = $mat['pmqty'] * $qty;
            mysqli_query($conn, "UPDATE materials SET mqty = mqty - $reduce_qty WHERE mid = " . $mat['mid']);
        }

        // 更新訂單總額
        $rs_total = mysqli_query($conn, "SELECT SUM(f.fprice * of2.oqty) as new_total FROM OrderFurnitures of2 JOIN Furnitures f ON of2.fid = f.fid WHERE of2.oid = $oid");
        $row_total = mysqli_fetch_assoc($rs_total);
        $new_total = isset($row_total['new_total']) ? $row_total['new_total'] : 0;
        mysqli_query($conn, "UPDATE Orders SET ototalamount = $new_total WHERE oid = $oid");

        header("Location: update_order.php?oid=$oid&updated=1");
        exit();
    }
}

// 更新商品數量
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item_qty'])) {
    $oid = intval($_POST['oid']);
    $fid = intval($_POST['fid']);
    $new_qty = intval($_POST['new_qty']);
    $old_qty = intval($_POST['old_qty']);

    if ($new_qty <= 0) {
        mysqli_query($conn, "DELETE FROM OrderFurnitures WHERE oid = $oid AND fid = $fid");
    } else {
        mysqli_query($conn, "UPDATE OrderFurnitures SET oqty = $new_qty WHERE oid = $oid AND fid = $fid");
    }

    // 更新物料庫存
    $qty_diff = $new_qty - $old_qty;
    $sql_materials = "SELECT mid, pmqty FROM furniturematerials WHERE fid = $fid";
    $rs_materials = mysqli_query($conn, $sql_materials);
    while ($mat = mysqli_fetch_assoc($rs_materials)) {
        $adjust_qty = $mat['pmqty'] * $qty_diff;
        if ($adjust_qty > 0) {
            mysqli_query($conn, "UPDATE materials SET mqty = mqty - $adjust_qty WHERE mid = " . $mat['mid']);
        } else {
            mysqli_query($conn, "UPDATE materials SET mqty = mqty + " . abs($adjust_qty) . " WHERE mid = " . $mat['mid']);
        }
    }

    // 更新訂單總額
    $rs_total = mysqli_query($conn, "SELECT SUM(f.fprice * of2.oqty) as new_total FROM OrderFurnitures of2 JOIN Furnitures f ON of2.fid = f.fid WHERE of2.oid = $oid");
    $row_total = mysqli_fetch_assoc($rs_total);
    $new_total = isset($row_total['new_total']) ? $row_total['new_total'] : 0;
    mysqli_query($conn, "UPDATE Orders SET ototalamount = $new_total WHERE oid = $oid");

    header("Location: update_order.php?oid=$oid&updated=1");
    exit();
}

// 更新訂單資訊
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $oid = intval($_POST['oid']);
    $ostatus = intval($_POST['ostatus']);
    $odeliverydate = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $odeliveraddress = mysqli_real_escape_string($conn, $_POST['delivery_address']);

    mysqli_query($conn, "UPDATE Orders SET ostatus = $ostatus, odeliverydate = '$odeliverydate', odeliveraddress = '$odeliveraddress' WHERE oid = $oid");
    header("Location: update_order.php?updated=1&oid=$oid");
    exit();
}

// ===== 查詢資料 =====
// 所有訂單
$orders = array();
$rs = mysqli_query($conn, "SELECT o.*, c.cname FROM Orders o JOIN Customers c ON o.cid = c.cid ORDER BY o.oid DESC");
while ($row = mysqli_fetch_assoc($rs)) { $orders[] = $row; }

// 所有家具
$all_furnitures = array();
$rs = mysqli_query($conn, "SELECT * FROM Furnitures ORDER BY fname");
while ($row = mysqli_fetch_assoc($rs)) { $all_furnitures[] = $row; }

// 編輯模式 - 訂單詳情
$order_data = null;
$order_items = array();
$material_usage = array();
$is_completed_order = false;

if ($is_edit_mode) {
    $rs = mysqli_query($conn, "SELECT o.*, c.cname, c.ctel, c.caddr FROM Orders o JOIN Customers c ON o.cid = c.cid WHERE o.oid = $edit_oid");
    $order_data = mysqli_fetch_assoc($rs);

    if ($order_data) {
        $is_completed_order = ($order_data['ostatus'] == 5);

        $rs_items = mysqli_query($conn, "SELECT of2.*, f.fname, f.fprice FROM OrderFurnitures of2 JOIN Furnitures f ON of2.fid = f.fid WHERE of2.oid = $edit_oid");
        while ($row = mysqli_fetch_assoc($rs_items)) { $order_items[] = $row; }

        $rs_materials = mysqli_query($conn, "SELECT m.mid, m.mname, fm.pmqty, (SELECT SUM(of2.oqty) FROM OrderFurnitures of2 WHERE of2.fid = fm.fid AND of2.oid = $edit_oid) as total_qty, (fm.pmqty * (SELECT SUM(of2.oqty) FROM OrderFurnitures of2 WHERE of2.fid = fm.fid AND of2.oid = $edit_oid)) as total_used, m.mqty as stock_after FROM furniturematerials fm JOIN materials m ON fm.mid = m.mid WHERE fm.fid IN (SELECT fid FROM OrderFurnitures WHERE oid = $edit_oid)");
        while ($row = mysqli_fetch_assoc($rs_materials)) { $material_usage[] = $row; }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Orders - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ===== Reset & Base ===== */
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

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
        }

        .page-header p {
            color: #6b6b6b;
            font-size: 14px;
            margin-top: 4px;
        }

        .msg {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-edit {
            background: #141414;
            color: white;
            padding: 6px 14px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-edit:hover {
            background: #333;
        }

        .btn-view {
            background: #e8f0fe;
            color: #1a73e8;
            padding: 6px 14px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-view:hover {
            background: #d2e3fc;
        }

        .btn-add-item {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-add-item:hover {
            background: #219a52;
        }

        .btn-submit {
            background: #141414;
            color: white;
            border: none;
            padding: 12px 36px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #333;
        }

        .btn-submit:disabled {
            background: #999;
            cursor: not-allowed;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid #ccc;
            padding: 11px 28px;
            border-radius: 60px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            color: #2f2f2f;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-cancel:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        /* ===== Tables ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: #f8f8f8;
            padding: 12px 14px;
            text-align: left;
            font-weight: 700;
            color: #2c2c2c;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0efec;
        }

        .data-table tr:hover td {
            background: #faf9f7;
        }

        /* ===== Status Badges ===== */
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.processing {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.pending {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .order-detail-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 20px;
        }

        .detail-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }

        .detail-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #eaeaea;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 12px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-item span {
            font-size: 14px;
            color: #2c2c2c;
            margin-top: 2px;
        }

        .info-item.full-width {
            grid-column: span 2;
        }

        /* ===== Order Items ===== */
        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f0efec;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item .item-details {
            flex: 1;
        }

        .order-item .item-details h4 {
            font-size: 14px;
            color: #2c2c2c;
        }

        .order-item .item-details .item-id {
            font-size: 12px;
            color: #888;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .qty-control input[type="number"] {
            width: 50px;
            padding: 4px 6px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }

        .qty-control input[type="number"]:focus {
            border-color: #141414;
            outline: none;
        }

        .btn-qty {
            background: #f0efec;
            border: 1px solid #ddd;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-qty:hover {
            background: #e0ded9;
        }

        .order-total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #eaeaea;
        }

        .total-row.grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            display: flex;
            justify-content: space-between;
        }

        /* ===== Add Item Section ===== */
        .add-item-section {
            background: #f8f8f8;
            border-radius: 12px;
            padding: 16px;
            margin-top: 8px;
            border: 1px dashed #ddd;
        }

        .add-item-section h4 {
            font-size: 14px;
            color: #2c2c2c;
            margin-bottom: 10px;
        }

        .add-item-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .add-item-form select {
            flex: 1;
            min-width: 150px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .add-item-form select:focus {
            border-color: #141414;
            outline: none;
        }

        .add-item-form input[type="number"] {
            width: 70px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
        }

        .add-item-form input[type="number"]:focus {
            border-color: #141414;
            outline: none;
        }

        /* ===== Form ===== */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #141414;
            background: white;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        /* ===== Order Stats ===== */
        .order-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .mini-stat {
            background: white;
            border-radius: 16px;
            padding: 18px 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border-left: 4px solid #ccc;
        }

        .mini-stat .count {
            font-size: 28px;
            font-weight: 700;
            color: #141414;
            display: block;
        }

        .mini-stat .label {
            font-size: 13px;
            color: #6b6b6b;
        }

        .mini-stat.pending {
            border-left-color: #f39c12;
        }

        .mini-stat.processing {
            border-left-color: #3498db;
        }

        .mini-stat.shipped {
            border-left-color: #2ecc71;
        }

        .mini-stat.delivered {
            border-left-color: #27ae60;
        }

        /* ===== Misc ===== */
        .readonly-badge {
            display: inline-block;
            background: #fff3e0;
            color: #e65100;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .back-link {
            display: inline-block;
            color: #6b6b6b;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .back-link:hover {
            color: #141414;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .order-detail-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item.full-width {
                grid-column: span 1;
            }

            .add-item-form {
                flex-direction: column;
                align-items: stretch;
            }

            .add-item-form select,
            .add-item-form input {
                width: 100%;
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

            .form-actions {
                flex-direction: column;
            }

            .form-actions button {
                width: 100%;
                justify-content: center;
            }
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
                <li><a href="material_list.php">🧱 Material</a></li>
                <li><a href="update_order.php" class="active">📝 Update Orders</a></li>
                <li><a href="report_dashboard.php">📊 Generate Report</a></li>
                <li><a href="delete_item.php">🗑️ Delete</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="logout-btn">🚪 Logout</a></div>
    </aside>

    <main class="main-content">

        <?php if ($is_edit_mode && $order_data): ?>

            <div class="page-header">
                <a href="update_order.php" class="back-link">⬅️ Back to Orders</a>
                <h2>
                    <?php echo $is_completed_order ? '👁️ View Order: #' . $edit_oid : '📝 Edit Order: #' . $edit_oid; ?>
                    <?php if ($is_completed_order): ?>
                        <span class="readonly-badge">🔒 Read Only</span>
                    <?php endif; ?>
                </h2>
                <p><?php echo $is_completed_order ? 'This order is completed and cannot be modified.' : 'Update order status, items and details'; ?></p>
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="order-detail-layout">
                <!-- 左欄：訂單資訊 + 商品 -->
                <div>
                    <div class="detail-card">
                        <h2>📋 Order Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Order ID</label>
                                <span>#<?php echo $order_data['oid']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Order Date</label>
                                <span><?php echo date('Y-m-d', strtotime($order_data['odate'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Customer</label>
                                <span><?php echo htmlspecialchars($order_data['cname']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Contact</label>
                                <span><?php echo htmlspecialchars($order_data['ctel']); ?></span>
                            </div>
                            <div class="info-item full-width">
                                <label>Current Status</label>
                                <span><span class="status-badge <?php echo $status_class[intval($order_data['ostatus'])]; ?>"><?php echo $status_label[intval($order_data['ostatus'])]; ?></span></span>
                            </div>
                            <div class="info-item full-width">
                                <label>Delivery Address</label>
                                <span><?php echo htmlspecialchars($order_data['odeliveraddress']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h2>📦 Order Items</h2>
                        <?php if (count($order_items) > 0): ?>
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <h4><?php echo htmlspecialchars($item['fname']); ?></h4>
                                        <p class="item-id">ID: <?php echo $item['fid']; ?></p>
                                    </div>
                                    <?php if ($is_completed_order): ?>
                                        <span style="font-weight:600;">× <?php echo $item['oqty']; ?></span>
                                    <?php else: ?>
                                        <form method="POST" action="update_order.php" class="qty-control" id="qty_form_<?php echo $item['fid']; ?>">
                                            <input type="hidden" name="oid" value="<?php echo $edit_oid; ?>">
                                            <input type="hidden" name="fid" value="<?php echo $item['fid']; ?>">
                                            <input type="hidden" name="old_qty" value="<?php echo $item['oqty']; ?>">
                                            <input type="hidden" name="new_qty" id="new_qty_<?php echo $item['fid']; ?>" value="<?php echo $item['oqty']; ?>">
                                            <button type="button" class="btn-qty" onclick="updateQty(<?php echo $item['fid']; ?>, <?php echo $item['oqty']; ?>, -1)">−</button>
                                            <input type="number" value="<?php echo $item['oqty']; ?>" min="0" max="999" onchange="updateQty(<?php echo $item['fid']; ?>, this.value, 0)">
                                            <button type="button" class="btn-qty" onclick="updateQty(<?php echo $item['fid']; ?>, <?php echo $item['oqty']; ?>, 1)">+</button>
                                            <button type="submit" name="update_item_qty" value="1" style="display:none;" id="submit_<?php echo $item['fid']; ?>"></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#888; text-align:center; padding:20px;">📭 No items in this order.</p>
                        <?php endif; ?>

                        <?php if (!$is_completed_order): ?>
                            <div class="add-item-section">
                                <h4>➕ Add Product to Order</h4>
                                <form method="POST" action="update_order.php" class="add-item-form">
                                    <input type="hidden" name="oid" value="<?php echo $edit_oid; ?>">
                                    <select name="fid" required>
                                        <option value="">Select Furniture...</option>
                                        <?php foreach ($all_furnitures as $f):
                                            $already_in_order = false;
                                            foreach ($order_items as $oi) {
                                                if ($oi['fid'] == $f['fid']) { $already_in_order = true; break; }
                                            }
                                            ?>
                                            <option value="<?php echo $f['fid']; ?>" <?php echo $already_in_order ? 'disabled style="color:#999;"' : ''; ?>>
                                                <?php echo $f['fname']; ?> ($<?php echo number_format($f['fprice'], 2); ?>)
                                                <?php echo $already_in_order ? '(Already in order)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="qty" value="1" min="1" max="99">
                                    <button type="submit" name="add_item_to_order" class="btn-add-item">➕ Add</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="order-total">
                            <div class="total-row grand-total">
                                <span>Total Amount</span>
                                <strong>$<?php echo number_format($order_data['ototalamount'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 右欄：更新訂單 -->
                <div>
                    <div class="detail-card">
                        <h2><?php echo $is_completed_order ? '📋 Order Details' : '🔄 Update Order Status'; ?></h2>
                        <form method="POST" action="update_order.php" class="update-form">
                            <input type="hidden" name="oid" value="<?php echo $order_data['oid']; ?>">

                            <div class="form-group">
                                <label for="ostatus">Order Status</label>
                                <select id="ostatus" name="ostatus" <?php echo $is_completed_order ? 'disabled' : ''; ?> required>
                                    <option value="1" <?php echo $order_data['ostatus'] == 1 ? 'selected' : ''; ?>>Open</option>
                                    <option value="2" <?php echo $order_data['ostatus'] == 2 ? 'selected' : ''; ?>>Processing</option>
                                    <option value="3" <?php echo $order_data['ostatus'] == 3 ? 'selected' : ''; ?>>Approved</option>
                                    <option value="4" <?php echo $order_data['ostatus'] == 4 ? 'selected' : ''; ?>>Pending Delivery</option>
                                    <option value="5" <?php echo $order_data['ostatus'] == 5 ? 'selected' : ''; ?>>Completed</option>
                                    <option value="0" <?php echo $order_data['ostatus'] == 0 ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="delivery_date">📅 Delivery Date</label>
                                <input type="date" id="delivery_date" name="delivery_date" value="<?php echo date('Y-m-d', strtotime($order_data['odeliverydate'])); ?>" <?php echo $is_completed_order ? 'disabled' : ''; ?> required>
                            </div>

                            <div class="form-group">
                                <label for="delivery_address">📍 Delivery Address</label>
                                <input type="text" id="delivery_address" name="delivery_address" value="<?php echo htmlspecialchars($order_data['odeliveraddress']); ?>" <?php echo $is_completed_order ? 'disabled' : ''; ?> required>
                            </div>

                            <div class="form-actions">
                                <?php if ($is_completed_order): ?>
                                    <a href="update_order.php" class="btn-cancel">⬅️ Back to Orders</a>
                                <?php else: ?>
                                    <button type="submit" name="update_order" class="btn-submit">💾 Save Changes</button>
                                    <a href="update_order.php" class="btn-cancel">❌ Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <?php if (!empty($material_usage)): ?>
                        <div class="detail-card">
                            <h2>🪵 Material Usage</h2>
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Used</th>
                                    <th>Stock After</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($material_usage as $mat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mat['mname']); ?></td>
                                        <td><?php echo $mat['total_used']; ?> units</td>
                                        <td><?php echo $mat['stock_after']; ?> units</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>

            <div class="page-header">
                <h2>📝 Update Order Records</h2>
                <p>Manage and update customer orders</p>
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="order-stats">
                <div class="mini-stat pending">
                    <span class="count"><?php echo count(array_filter($orders, function($o) { return $o['ostatus'] == 1; })); ?></span>
                    <span class="label">Open</span>
                </div>
                <div class="mini-stat processing">
                    <span class="count"><?php echo count(array_filter($orders, function($o) { return $o['ostatus'] == 2; })); ?></span>
                    <span class="label">Processing</span>
                </div>
                <div class="mini-stat shipped">
                    <span class="count"><?php echo count(array_filter($orders, function($o) { return $o['ostatus'] == 3 || $o['ostatus'] == 4; })); ?></span>
                    <span class="label">Approved / Pending</span>
                </div>
                <div class="mini-stat delivered">
                    <span class="count"><?php echo count(array_filter($orders, function($o) { return $o['ostatus'] == 5; })); ?></span>
                    <span class="label">Completed</span>
                </div>
            </div>

            <div class="detail-card">
                <h2>📋 Order List</h2>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total (HKD)</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['oid']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['cname']); ?></td>
                                <td>$<?php echo number_format($order['ototalamount'], 2); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($order['odate'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($order['odeliverydate'])); ?></td>
                                <td><span class="status-badge <?php echo $status_class[intval($order['ostatus'])]; ?>"><?php echo $status_label[intval($order['ostatus'])]; ?></span></td>
                                <td>
                                    <?php if ($order['ostatus'] == 5): ?>
                                        <a href="update_order.php?oid=<?php echo $order['oid']; ?>" class="btn-view">👁️ View</a>
                                    <?php else: ?>
                                        <a href="update_order.php?oid=<?php echo $order['oid']; ?>" class="btn-edit">✏️ Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:#888;">📭 No orders found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </main>
</div>

<script>
    function updateQty(fid, currentQty, change) {
        var newQty = parseInt(currentQty) + parseInt(change);
        if (newQty < 0) newQty = 0;
        document.getElementById('new_qty_' + fid).value = newQty;
        document.getElementById('submit_' + fid).click();
    }
</script>

</body>
</html>