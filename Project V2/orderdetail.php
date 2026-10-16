<?php
session_start();


if (!isset($_SESSION['customer_id'])) {
    header("Location: logincustomer.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];


$oid = isset($_GET['oid']) ? intval($_GET['oid']) : 0;

if ($oid == 0) {
    header("Location: orderrecord.php");
    exit();
}


require_once 'db_config.php';

if (!isset($conn) || $conn === false) {
    die("資料庫連線失敗：" . mysqli_connect_error());
}


$sql = "SELECT o.*, c.cname, c.ctel, c.caddr 
        FROM Orders o 
        JOIN Customers c ON o.cid = c.cid 
        WHERE o.oid = $oid AND o.cid = $customer_id";

$rs = mysqli_query($conn, $sql);

if (!$rs || mysqli_num_rows($rs) == 0) {
    mysqli_close($conn);
    header("Location: orderrecord.php");
    exit();
}

$order = mysqli_fetch_assoc($rs);


$sql_items = "SELECT * FROM OrderFurnitures WHERE oid = $oid";
$rs_items = mysqli_query($conn, $sql_items);

$items = array();


while ($row = mysqli_fetch_assoc($rs_items)) {
    $fid = $row['fid'];


    $sql_furniture = "SELECT fname, fprice FROM Furnitures WHERE fid = $fid";
    $rs_furniture = mysqli_query($conn, $sql_furniture);
    $furniture = mysqli_fetch_assoc($rs_furniture);

    // 合併資料：訂單項目 + 家具資料
    $row['fname'] = $furniture['fname'];
    $row['fprice'] = $furniture['fprice'];

    $items[] = $row;
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

$ostatus = intval($order['ostatus']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Order #<?php echo $oid; ?></title>
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
            max-width: 800px;
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


        .order-header {
            background: #141414;
            color: white;
            text-align: center;
            padding: 14px 0;
            border-radius: 16px;
            margin-bottom: 16px;
        }
        .order-header h2 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }


        .detail-card {
            background: #fcfcfa;
            border-radius: 16px;
            padding: 18px 22px 22px;
            border: 1px solid #eee;
            margin-bottom: 16px;
        }
        .detail-card .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f0efec;
            font-size: 14px;
        }
        .detail-card .info-row:last-child {
            border-bottom: none;
        }
        .detail-card .info-row .label {
            font-weight: 600;
            color: #2c2c2c;
        }
        .detail-card .info-row .value {
            color: #141414;
            text-align: right;
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


        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        .items-table thead {
            background: #141414;
            color: white;
        }
        .items-table th {
            padding: 10px 14px;
            text-align: left;
            font-weight: 700;
        }
        .items-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0efec;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .items-table tbody tr:hover {
            background: #faf9f7;
        }

        .total-amount {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            padding: 12px 0 6px;
            border-top: 2px solid #eaeaea;
            margin-top: 12px;
        }
        .total-amount small {
            font-weight: 400;
            font-size: 14px;
            color: #888;
        }


        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 28px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            text-decoration: none;
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

        /* ----- 底部 ----- */
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

        @media (max-width: 600px) {
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .order-header h2 { font-size: 18px; }
            .detail-card .info-row { flex-direction: column; align-items: flex-start; gap: 2px; }
            .detail-card .info-row .value { text-align: left; }
            .items-table th, .items-table td { padding: 6px 10px; font-size: 12px; }
            .action-buttons { flex-direction: column; align-items: center; }
            .btn { width: 100%; justify-content: center; }
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .menu { right: 16px; top: 90px; width: 180px; }
        }
        @media (max-width: 480px) {
            .menu { right: 10px; top: 110px; width: 170px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">

    <!-- 導航列 -->
    <div class="navbar">
        <div class="brand">
            <a href="loginstarpage.php" class="logo-box">
                <img src="Designer (3).png" alt="Premium Living Logo">
            </a>
            <h1>Premium<span>Living</span></h1>
        </div>
        <div class="nav-actions">
            <span class="welcome">
                <a href="Updateprofile.php">👤 Welcome, <?php echo $customer_name; ?></a>
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

    <!-- 訂單標題 -->
    <div class="order-header">
        <h2>🧾 Order ID: <?php echo $oid; ?></h2>
    </div>

    <!-- 訂單詳情 -->
    <div class="detail-card">
        <div class="info-row">
            <span class="label">📅 Order Create Time</span>
            <span class="value"><?php echo date('Y-m-d H:i', strtotime($order['odate'])); ?></span>
        </div>
        <div class="info-row">
            <span class="label">👤 Customer Name</span>
            <span class="value"><?php echo $order['cname']; ?></span>
        </div>
        <div class="info-row">
            <span class="label">📱 Contact Number</span>
            <span class="value"><?php echo $order['ctel']; ?></span>
        </div>
        <div class="info-row">
            <span class="label">🚚 Delivery Date</span>
            <span class="value"><?php echo date('Y-m-d', strtotime($order['odeliverydate'])); ?></span>
        </div>
        <div class="info-row">
            <span class="label">📍 Delivery Address</span>
            <span class="value"><?php echo $order['odeliveraddress']; ?></span>
        </div>
        <div class="info-row">
            <span class="label">ℹ️ Order Status</span>
            <span class="value"><span class="status-badge <?php echo $status_class[$ostatus]; ?>"><?php echo $status_label[$ostatus]; ?></span></span>
        </div>
    </div>

    <!-- 產品列表 -->
    <div class="detail-card">
        <h3 style="margin-bottom:12px; font-size:16px; color:#141414;">
            📦 Order Items
        </h3>
        <table class="items-table">
            <thead>
            <tr>
                <th>Furniture</th>
                <th>Furniture ID</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($items) > 0) {
                $total = 0;
                foreach ($items as $item) {
                    $subtotal = $item['fprice'] * $item['oqty'];
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><?php echo $item['fname']; ?></td>
                        <td><?php echo $item['fid']; ?></td>
                        <td>$<?php echo number_format($item['fprice'], 2); ?></td>
                        <td><?php echo $item['oqty']; ?></td>
                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php
                }
            } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#888; padding:20px;">No items found for this order.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <div class="total-amount">
            Total Amount: <strong>$<?php echo number_format($order['ototalamount'], 2); ?></strong> <small>HKD</small>
        </div>
    </div>


    <div class="action-buttons">
        <a href="loginstarpage.php" class="btn btn-primary">🏠 Back to Home</a>
        <a href="orderrecord.php" class="btn btn-secondary">📋 Back to Order Records</a>
    </div>

    <div class="divider-black footer-divider"></div>


    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>

    </div>

</div>

</body>
</html>