<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: logincustomer.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

require_once 'db_config.php';

if (!isset($conn) || $conn === false) {
    die(mysqli_connect_error());
}


if (isset($_GET['delete']) && isset($_GET['oid'])) {
    $oid = intval($_GET['oid']);

    $check_sql = "SELECT odeliverydate, ostatus FROM Orders WHERE oid = $oid AND cid = $customer_id";
    $check_rs = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_rs);

    if ($check_row) {
        $delivery_date = strtotime($check_row['odeliverydate']);
        $today = time();
        $days_diff = floor(($delivery_date - $today) / (60 * 60 * 24));

        if ($days_diff >= 2 && $check_row['ostatus'] != 5 && $check_row['ostatus'] != 0) {

            mysqli_begin_transaction($conn);

            try {

                $sql_items = "SELECT fid, oqty FROM OrderFurnitures WHERE oid = $oid";
                $rs_items = mysqli_query($conn, $sql_items);

                if (!$rs_items) {
                    throw new Exception("Failed to get order items: " . mysqli_error($conn));
                }

                // 2. 恢復物料庫存
                while ($item = mysqli_fetch_assoc($rs_items)) {
                    $fid = $item['fid'];
                    $qty = $item['oqty'];


                    $sql_materials = "SELECT fm.mid, fm.pmqty FROM furniturematerials fm WHERE fm.fid = $fid";
                    $rs_materials = mysqli_query($conn, $sql_materials);

                    if (!$rs_materials) {
                        throw new Exception("Failed to get materials: " . mysqli_error($conn));
                    }

                    while ($material = mysqli_fetch_assoc($rs_materials)) {
                        $mid = $material['mid'];
                        $restore_qty = $material['pmqty'] * $qty;


                        $sql_update = "UPDATE materials SET mqty = mqty + $restore_qty WHERE mid = $mid";
                        if (!mysqli_query($conn, $sql_update)) {
                            throw new Exception("Failed to restore material stock: " . mysqli_error($conn));
                        }
                    }
                }


                $sql = "UPDATE Orders SET ostatus = 0 WHERE oid = $oid AND cid = $customer_id";
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception("Failed to cancel order: " . mysqli_error($conn));
                }


                mysqli_commit($conn);
                $delete_success = true;

            } catch (Exception $e) {

                mysqli_rollback($conn);
                $delete_error = $e->getMessage();
            }

        } else {
            if ($days_diff < 2) {
                $delete_error = "Orders can only be cancelled at least 2 days before the delivery date.";
            } elseif ($check_row['ostatus'] == 5) {
                $delete_error = "Completed orders cannot be cancelled.";
            } elseif ($check_row['ostatus'] == 0) {
                $delete_error = "This order has already been cancelled.";
            } else {
                $delete_error = "This order cannot be cancelled.";
            }
        }
    } else {
        $delete_error = "Order not found.";
    }
}


$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'odate';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

$allowed_sort_fields = ['oid', 'odate', 'odeliverydate', 'ototalamount'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'odate';
}
$sort_order = strtoupper($sort_order);
if ($sort_order != 'ASC' && $sort_order != 'DESC') {
    $sort_order = 'DESC';
}


$sql = "SELECT * FROM Orders WHERE cid = $customer_id ORDER BY $sort_by $sort_order";
$rs = mysqli_query($conn, $sql);

if (!$rs) {
    die( mysqli_error($conn));
}

$orders = array();


while ($order = mysqli_fetch_assoc($rs)) {

    $sql_items = "SELECT f.fname, f.fid, of2.oqty 
                  FROM OrderFurnitures of2, Furnitures f 
                  WHERE of2.fid = f.fid AND of2.oid = " . $order['oid'];
    $rs_items = mysqli_query($conn, $sql_items);

    $fnames = array();
    $fids = array();
    $qtys = array();

    while ($item = mysqli_fetch_assoc($rs_items)) {
        $fnames[] = $item['fname'];
        $fids[] = $item['fid'];
        $qtys[] = $item['oqty'];
    }

    // 合併到訂單資料中
    $order['furniture_names'] = implode(', ', $fnames);
    $order['furniture_ids'] = implode(', ', $fids);
    $order['furniture_qtys'] = implode(', ', $qtys);

    $orders[] = $order;
}

mysqli_close($conn);


$status_label = array(
        0 => 'Rejected',
        1 => 'Open',
        2 => 'Processing',
        3 => 'Approved',
        4 => 'Pending Delivery',
        5 => 'Completed'
);
$status_class = array(
        0 => 'rejected',
        1 => 'open',
        2 => 'processing',
        3 => 'approved',
        4 => 'pending',
        5 => 'completed'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Order Records</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .main-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            padding: 24px 30px 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        }


        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 18px;
            border-bottom: 2px solid #eaeaea;
            margin-bottom: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-box {
            background: #141414;
            color: white;
            font-size: 22px;
            font-weight: 700;
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5px;
            text-decoration: none;
            cursor: pointer;
            overflow: hidden;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .brand h1 {
            font-size: 24px;
            font-weight: 700;
            color: #141414;
            letter-spacing: 0.5px;
        }
        .brand h1 span {
            font-weight: 300;
            color: #777;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nav-actions .welcome {
            font-weight: 600;
            font-size: 14px;
            color: #141414;
            margin-right: 4px;
        }
        .nav-actions .welcome a {
            color: #141414;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 40px;
            transition: background 0.15s;
        }
        .nav-actions .welcome a:hover {
            background: #f0efec;
        }

        #menu-toggle {
            display: none;
        }
        .menu-btn {
            font-size: 26px;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 40px;
            transition: background 0.15s;
            user-select: none;
        }
        .menu-btn:hover {
            background: #f0efec;
        }

        .menu {
            display: none;
            position: absolute;
            top: 100px;
            right: 30px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 16px;
            width: 200px;
            z-index: 999;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .menu a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #2c2c2c;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.15s;
            border-bottom: 1px solid #f0f0f0;
        }
        .menu a:last-child {
            border-bottom: none;
        }
        .menu a:hover {
            background: #f5f5f5;
        }
        .menu a.logout {
            color: #c0392b;
        }

        #menu-toggle:checked + label + .menu {
            display: block;
        }

        .divider-black {
            background: #141414;
            height: 6px;
            border-radius: 20px;
            margin: 12px 0 18px 0;
        }


        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: #141414;
            margin: 6px 0 16px 0;
            text-align: center;
            letter-spacing: 0.5px;
        }


        .tab-bar {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .tab-btn {
            padding: 8px 28px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            border: none;
            cursor: pointer;
        }
        .tab-btn.active {
            background: #141414;
            color: white;
        }
        .tab-btn.inactive {
            background: transparent;
            color: #2c2c2c;
            border: 1px solid #ccc;
        }
        .tab-btn.inactive:hover {
            background: #f0efec;
            border-color: #aaa;
        }


        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #fce4ec;
            color: #c0392b;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #f5c6cb;
        }


        .sort-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 10px 16px;
            background: #fcfcfa;
            border-radius: 12px;
            border: 1px solid #eee;
            margin-bottom: 16px;
        }
        .sort-bar label {
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
        }
        .sort-bar select {
            padding: 6px 14px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-size: 13px;
            outline: none;
            font-family: Arial, sans-serif;
            background: white;
            cursor: pointer;
        }
        .sort-bar select:focus {
            border-color: #141414;
        }
        .sort-bar .clear-btn {
            color: #141414;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 4px 12px;
            border-radius: 40px;
            border: 1px solid #ddd;
            transition: 0.15s;
        }
        .sort-bar .clear-btn:hover {
            background: #f0efec;
            border-color: #aaa;
        }


        .table-container {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid #eee;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .data-table thead {
            background: #141414;
            color: white;
        }
        .data-table th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #333;
            white-space: nowrap;
        }
        .data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0efec;
            vertical-align: middle;
        }
        .data-table tbody tr:hover {
            background: #faf9f7;
        }
        .data-table tbody tr.row-cancelled {
            background: #fce4ec;
            opacity: 0.8;
        }
        .data-table tbody tr.row-cancelled:hover {
            background: #fce4ec;
        }
        .data-table .no-orders {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .furniture-list {
            font-size: 13px;
            line-height: 1.6;
        }
        .furniture-list .item {
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.open {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-badge.processing {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-badge.approved {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-badge.pending {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .status-badge.completed {
            background: #e8f5e9;
            color: #1b5e20;
        }
        .status-badge.rejected {
            background: #fce4ec;
            color: #c0392b;
        }

        .action-btns {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .btn-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            text-decoration: none;
        }
        .btn-view {
            background: #141414;
            color: white;
        }
        .btn-view:hover {
            background: #333;
        }
        .btn-delete {
            background: #c0392b;
            color: white;
        }
        .btn-delete:hover {
            background: #a93226;
        }
        .btn-delete:disabled {
            background: #e0e0e0;
            color: #888;
            cursor: not-allowed;
        }
        .btn-delete.cancelled {
            background: #27ae60;
            color: white;
        }
        .btn-delete.cancelled:hover {
            background: #219a52;
        }


        .footer-divider {
            margin: 28px 0 18px 0;
        }
        .footer-note {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            font-size: 14px;
            color: #6b6b6b;
            padding-top: 6px;
        }

        .back-home {
            text-align: center;
            margin-top: 16px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            background: #141414;
            color: white;
            text-decoration: none;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-back:hover {
            background: #333;
        }


        @media (max-width: 820px) {
            .data-table th, .data-table td {
                padding: 8px 10px;
                font-size: 12px;
            }
            .sort-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
        @media (max-width: 600px) {
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .tab-bar { flex-direction: column; align-items: center; }
            .tab-btn { width: 80%; text-align: center; }
            .data-table th, .data-table td { padding: 6px 8px; font-size: 11px; }
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .menu { right: 16px; top: 90px; width: 180px; }
            .action-btns { flex-direction: row; gap: 4px; }
            .btn-sm { font-size: 10px; padding: 3px 8px; }
        }
        @media (max-width: 480px) {
            .menu { right: 10px; top: 110px; width: 170px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">


    <div class="navbar">
        <div class="brand">
            <a href="loginstarpage.php" class="logo-box">
                <img src="Designer (3).png" alt="Premium Living Logo">
            </a>
            <h1>Premium<span>Living</span></h1>
        </div>
        <div class="nav-actions">
            <span class="welcome">
                <a href="Updateprofile.php">👤 Welcome, <?php echo htmlspecialchars($customer_name); ?></a>
            </span>
            <input type="checkbox" id="menu-toggle">
            <label for="menu-toggle" class="menu-btn">☰</label>
            <div class="menu">
                <a href="loginstarpage.php">🏠 Home</a>
                <a href="viewfurniture.php">📋 View Furniture</a>
                <a href="view_cart.php">🛒 Make Order</a>
                <a href="orderrecord.php">📜 Order Records</a>
                <a href="Updateprofile.php">✏️ Update Profile</a>
                <a href="logout.php" class="logout">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="divider-black"></div>

    <!-- 頁面標題 -->
    <div class="page-title">
        📜 Order Records
    </div>


    <div class="tab-bar">
        <a href="Updateprofile.php" class="tab-btn inactive">👤 Customer Profile</a>
        <span class="tab-btn active">📋 Order Records</span>
    </div>

    <?php if (isset($delete_success) && $delete_success == true): ?>
        <div class="success-message">
            ✅ Order cancelled successfully! Materials have been restored to inventory.
        </div>
    <?php endif; ?>

    <?php if (isset($delete_error) && !empty($delete_error)): ?>
        <div class="error-message">
            ❌ <?php echo $delete_error; ?>
        </div>
    <?php endif; ?>

    <!-- 排序欄 -->
    <form method="get" action="orderrecord.php" class="sort-bar">
        <label>🔀 Sort by:</label>
        <select name="sort_by" onchange="this.form.submit()">
            <option value="odate" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'odate') ? 'selected' : ''; ?>>Order Date</option>
            <option value="odeliverydate" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'odeliverydate') ? 'selected' : ''; ?>>Delivery Date</option>
            <option value="ototalamount" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'ototalamount') ? 'selected' : ''; ?>>Total Amount</option>
            <option value="oid" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'oid') ? 'selected' : ''; ?>>Order ID</option>
        </select>
        <select name="sort_order" onchange="this.form.submit()">
            <option value="DESC" <?php echo (isset($_GET['sort_order']) && $_GET['sort_order'] == 'DESC') ? 'selected' : ''; ?>>⬇️ Descending</option>
            <option value="ASC" <?php echo (isset($_GET['sort_order']) && $_GET['sort_order'] == 'ASC') ? 'selected' : ''; ?>>⬆️ Ascending</option>
        </select>
        <?php if (isset($_GET['sort_by']) || isset($_GET['sort_order'])): ?>
            <a href="orderrecord.php" class="clear-btn">✕ Clear</a>
        <?php endif; ?>
    </form>

    <!-- 訂單表格 -->
    <div class="table-container">
        <table class="data-table">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Furniture</th>
                <th>Furniture ID</th>
                <th>Qty</th>
                <th>Total Amount</th>
                <th>Customer ID</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order):

                    $fnames = !empty($order['furniture_names']) ? explode(', ', $order['furniture_names']) : [];
                    $fids = !empty($order['furniture_ids']) ? explode(', ', $order['furniture_ids']) : [];
                    $qtys = !empty($order['furniture_qtys']) ? explode(', ', $order['furniture_qtys']) : [];

                    $ostatus = intval($order['ostatus']);
                    $is_cancelled = ($ostatus == 0); // Rejected

                    // 計算距離送貨日期還有幾天
                    $delivery_date = strtotime($order['odeliverydate']);
                    $today = time();
                    $days_until_delivery = floor(($delivery_date - $today) / (60 * 60 * 24));


                    $can_cancel = ($days_until_delivery >= 2 && $ostatus != 5 && $ostatus != 0);
                    ?>
                    <tr class="<?php echo $is_cancelled ? 'row-cancelled' : ''; ?>" id="row-<?php echo $order['oid']; ?>">
                        <td><strong><?php echo $order['oid']; ?></strong></td>
                        <td><?php echo date('Y-m-d', strtotime($order['odate'])); ?></td>
                        <td>
                            <span class="furniture-list">
                                <?php
                                if (!empty($fnames)) {
                                    foreach ($fnames as $fname) {
                                        echo '<span class="item">' . htmlspecialchars($fname) . '</span>';
                                    }
                                } else {
                                    echo '<span class="item">-</span>';
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="furniture-list">
                                <?php
                                if (!empty($fids)) {
                                    foreach ($fids as $fid) {
                                        echo '<span class="item">' . htmlspecialchars($fid) . '</span>';
                                    }
                                } else {
                                    echo '<span class="item">-</span>';
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="furniture-list">
                                <?php
                                if (!empty($qtys)) {
                                    foreach ($qtys as $qty) {
                                        echo '<span class="item">' . htmlspecialchars($qty) . '</span>';
                                    }
                                } else {
                                    echo '<span class="item">-</span>';
                                }
                                ?>
                            </span>
                        </td>
                        <td><strong>$<?php echo number_format($order['ototalamount'], 2); ?></strong></td>
                        <td><?php echo $order['cid']; ?></td>
                        <td>
                            <?php echo date('Y-m-d', strtotime($order['odeliverydate'])); ?>
                            <?php if ($days_until_delivery >= 0): ?>
                                <br><small style="color:#888; font-size:10px;">(<?php echo $days_until_delivery; ?> days left)</small>
                            <?php else: ?>
                                <br><small style="color:#c0392b; font-size:10px;">(Past delivery)</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?php echo $status_class[$ostatus]; ?>"><?php echo $status_label[$ostatus]; ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="orderdetail.php?oid=<?php echo $order['oid']; ?>" class="btn-sm btn-view">👁️ View</a>

                                <?php if ($can_cancel): ?>

                                    <a href="orderrecord.php?delete=1&oid=<?php echo $order['oid']; ?>" class="btn-sm btn-delete" onclick="return confirm('Are you sure you want to cancel this order? Materials will be restored to inventory.')">✖️ Cancel</a>

                                <?php elseif ($ostatus == 0): ?>

                                    <button class="btn-sm btn-delete cancelled" disabled>✅ Cancelled</button>

                                <?php elseif ($ostatus == 5): ?>

                                    <button class="btn-sm btn-delete" disabled>✅ Completed</button>

                                <?php else: ?>

                                    <button class="btn-sm btn-delete" disabled title="Orders can only be cancelled at least 2 days before delivery">⛔ Cannot Cancel</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="no-orders">
                        📭
                        You have no orders yet.
                        <br><br>
                        <a href="view_cart.php" style="color:#141414; font-weight:600; text-decoration:none; padding:8px 24px; border:1px solid #141414; border-radius:60px; display:inline-block;">🛒 Start Shopping</a>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>


    <div class="back-home">
        <a href="loginstarpage.php" class="btn-back">🏠 Back to Home</a>
    </div>

    <div class="divider-black footer-divider"></div>


    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

</body>
</html>