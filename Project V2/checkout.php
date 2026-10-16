<?php
session_start();


if (!isset($_SESSION['customer_id'])) {
    header("Location: logincustomer.php");
    exit();
}

$customer_name = $_SESSION['customer_name'];
$customer_id = $_SESSION['customer_id'];

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: view_cart.php");
    exit();
}


require_once 'db_config.php';

if (!isset($conn) || $conn === false) {
    die(mysqli_connect_error());
}


$sql = "SELECT * FROM Customers WHERE cid = '$customer_id'";
$rs = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($rs);


$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['fprice'] * $item['qty'];
}

$merged_cart = [];
foreach ($_SESSION['cart'] as $item) {
    $fid = $item['fid'];
    if (isset($merged_cart[$fid])) {
        $merged_cart[$fid]['qty'] += $item['qty'];
    } else {
        $merged_cart[$fid] = $item;
    }
}
$merged_cart = array_values($merged_cart);


$order_success = false;
$order_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $delivery_date = $_POST['delivery_date'];
    $delivery_address = trim($_POST['delivery_address']);
    $special_need = trim($_POST['special_need']);


    if (empty($delivery_date)) {
        $order_error = "Please select a delivery date.";
    } elseif (empty($delivery_address)) {
        $order_error = "Please enter delivery address.";
    } else {

        $delivery_date = mysqli_real_escape_string($conn, $delivery_date);
        $delivery_address = mysqli_real_escape_string($conn, $delivery_address);
        $special_need = mysqli_real_escape_string($conn, $special_need);
        mysqli_begin_transaction($conn);

        try {

            $insufficient_materials = [];

            foreach ($merged_cart as $item) {
                $fid = $item['fid'];
                $qty = $item['qty'];


                $sql_materials = "SELECT mid, pmqty FROM furniturematerials WHERE fid = $fid";
                $rs_materials = mysqli_query($conn, $sql_materials);


                while ($material = mysqli_fetch_assoc($rs_materials)) {
                    $mid = $material['mid'];
                    $pmqty = $material['pmqty'];
                    $required_qty = $pmqty * $qty;


                    $sql_material_info = "SELECT mname, mqty FROM materials WHERE mid = $mid";
                    $rs_material_info = mysqli_query($conn, $sql_material_info);
                    $material_info = mysqli_fetch_assoc($rs_material_info);

                    if ($material_info['mqty'] < $required_qty) {
                        $insufficient_materials[] = $material_info['mname'] . " (Need: " . $required_qty . ", Stock: " . $material_info['mqty'] . ")";
                    }
                }
            }


            if (!empty($insufficient_materials)) {
                throw new Exception("Insufficient materials: " . implode(", ", $insufficient_materials));
            }


            $sql = "INSERT INTO Orders (odate, ototalamount, cid, odeliverydate, odeliveraddress, ostatus) 
                    VALUES (NOW(), '$total_price', '$customer_id', '$delivery_date', '$delivery_address', 1)";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Failed to create order: " . mysqli_error($conn));
            }

            $oid = mysqli_insert_id($conn);

            foreach ($merged_cart as $item) {
                $fid = $item['fid'];
                $qty = $item['qty'];

                // Insert order item
                $sql_item = "INSERT INTO OrderFurnitures (oid, fid, oqty) VALUES ('$oid', '$fid', '$qty')";
                if (!mysqli_query($conn, $sql_item)) {
                    throw new Exception("Failed to insert order item: " . mysqli_error($conn));
                }


                $sql_materials = "SELECT mid, pmqty FROM furniturematerials WHERE fid = $fid";
                $rs_materials = mysqli_query($conn, $sql_materials);


                while ($material = mysqli_fetch_assoc($rs_materials)) {
                    $mid = $material['mid'];
                    $reduce_qty = $material['pmqty'] * $qty;


                    $sql_update = "UPDATE materials SET mqty = mqty - $reduce_qty WHERE mid = $mid";
                    if (!mysqli_query($conn, $sql_update)) {
                        throw new Exception("Failed to update material stock: " . mysqli_error($conn));
                    }
                }
            }

            mysqli_commit($conn);

            $_SESSION['cart'] = [];
            $order_success = true;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $order_error = $e->getMessage();
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
    <title>Premium Living · Checkout</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .main-wrapper {
            max-width: 900px;
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

        #menu-toggle { display: none; }
        .menu-btn {
            font-size: 26px;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 40px;
            transition: background 0.15s;
            user-select: none;
        }
        .menu-btn:hover { background: #f0efec; }
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
        .menu a:last-child { border-bottom: none; }
        .menu a:hover { background: #f5f5f5; }
        .menu a.logout { color: #c0392b; }
        #menu-toggle:checked + label + .menu { display: block; }

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
            margin: 6px 0 20px 0;
            text-align: center;
            letter-spacing: 0.5px;
        }


        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 16px 20px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #fce4ec;
            color: #c0392b;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #f5c6cb;
        }


        .order-summary {
            background: #fcfcfa;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #eee;
            margin-bottom: 16px;
        }
        .order-summary h3 {
            font-size: 16px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f0efec;
            font-size: 14px;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-item .sname {
            color: #2c2c2c;
        }
        .summary-item .sprice {
            font-weight: 600;
            color: #141414;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 12px 0 4px;
            border-top: 2px solid #eaeaea;
            margin-top: 6px;
            font-size: 18px;
            font-weight: 700;
            color: #141414;
        }


        .checkout-form .form-group {
            margin-bottom: 14px;
        }
        .checkout-form .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
            margin-bottom: 4px;
        }
        .checkout-form .form-group input,
        .checkout-form .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
            background: white;
        }
        .checkout-form .form-group input:focus,
        .checkout-form .form-group textarea:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }
        .checkout-form .form-group textarea {
            border-radius: 16px;
            resize: vertical;
            min-height: 60px;
        }
        .checkout-form .form-group input:disabled {
            background: #f3f2ef;
            color: #888;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: #141414;
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 60px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background: #333;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #ccc;
            padding: 11px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            color: #2f2f2f;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-secondary:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        /* ----- Footer ----- */
        .footer-divider { margin: 28px 0 18px 0; }
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
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .menu { right: 16px; top: 90px; width: 180px; }
            .form-actions { flex-direction: column; align-items: center; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
        }
        @media (max-width: 480px) {
            .menu { right: 10px; top: 110px; width: 170px; }
            .summary-item { font-size: 13px; }
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
                <a href="view_cart.php">🛒 Shopping Cart</a>
                <a href="orderrecord.php">📜 Order Records</a>
                <a href="Updateprofile.php">✏️ Update Profile</a>
                <a href="logout.php" class="logout">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="divider-black"></div>

    <!-- Page Title -->
    <div class="page-title">
        💳 Checkout
    </div>

    <?php if ($order_success) { ?>
        <div class="success-message">
            ✅ Order placed successfully! Your order has been confirmed.
            <br><br>
            <a href="orderrecord.php" style="color:#2e7d32; font-weight:600; text-decoration:underline;">📜 View My Orders</a>
        </div>
    <?php } ?>

    <?php if (!empty($order_error)) { ?>
        <div class="error-message">
            ❌ <?php echo $order_error; ?>
        </div>
    <?php } ?>

    <?php if (!$order_success) { ?>


        <div class="order-summary">
            <h3>📋 Order Summary</h3>
            <?php foreach ($_SESSION['cart'] as $item) { ?>
                <div class="summary-item">
                    <span class="sname"><?php echo $item['fname']; ?> × <?php echo $item['qty']; ?></span>
                    <span class="sprice">$<?php echo number_format($item['fprice'] * $item['qty'], 2); ?></span>
                </div>
            <?php } ?>
            <div class="summary-total">
                <span>Total Amount</span>
                <span>$<?php echo number_format($total_price, 2); ?> <small>HKD</small></span>
            </div>
        </div>


        <form class="checkout-form" method="post" action="checkout.php">
            <div class="form-group">
                <label>👤 Customer Name</label>
                <input type="text" value="<?php echo $user['cname']; ?>" disabled>
            </div>

            <div class="form-group">
                <label>🆔 Customer ID</label>
                <input type="text" value="<?php echo $user['cid']; ?>" disabled>
            </div>

            <div class="form-group">
                <label>📱 Contact Number</label>
                <input type="text" value="<?php echo $user['ctel']; ?>" disabled>
            </div>

            <div class="form-group">
                <label>📅 Delivery Date <span style="color:#c0392b;">*</span></label>
                <input type="date" name="delivery_date" required min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
            </div>

            <div class="form-group">
                <label>📍 Delivery Address <span style="color:#c0392b;">*</span></label>
                <input type="text" name="delivery_address" value="<?php echo $user['caddr']; ?>" required>
            </div>

            <div class="form-group">
                <label>📝 Special Need</label>
                <textarea name="special_need" rows="2" placeholder="Any special requests for delivery or installation..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">✅ Place Order</button>
                <a href="view_cart.php" class="btn-secondary">⬅️ Back to Cart</a>
            </div>
        </form>

    <?php } ?>

    <div class="divider-black footer-divider"></div>


    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

</body>
</html>