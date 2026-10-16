<?php
session_start();


if (!isset($_SESSION['customer_id'])) {
    header("Location: logincustomer.php");
    exit();
}

$customer_name = $_SESSION['customer_name'];
$customer_id = $_SESSION['customer_id'];


require_once 'db_config.php';


if (!isset($conn) || $conn === false) {
    die(mysqli_connect_error());
}


$sql = "SELECT * FROM Furnitures ORDER BY fid";
$rs = mysqli_query($conn, $sql);
$furnitures = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $furnitures[] = $row;
}
mysqli_close($conn);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['add_to_cart'])) {
    $fid = $_POST['fid'];
    $fname = $_POST['fname'];
    $fprice = $_POST['fprice'];
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['fid'] == $fid) {
            $item['qty'] += $qty;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
                'fid' => $fid,
                'fname' => $fname,
                'fprice' => $fprice,
                'qty' => $qty
        ];
    }

    header("Location: view_cart.php");
    exit();
}


if (isset($_POST['change_qty'])) {
    $fid = $_POST['fid'];
    $action = $_POST['change_qty'];

    foreach ($_SESSION['cart'] as $index => &$item) {
        if ($item['fid'] == $fid) {
            if ($action == 'increase') {
                $item['qty'] += 1;
            } elseif ($action == 'decrease') {
                $item['qty'] -= 1;
                if ($item['qty'] <= 0) {
                    unset($_SESSION['cart'][$index]);
                }
            }
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: view_cart.php");
    exit();
}


if (isset($_POST['update_qty'])) {
    $fid = $_POST['fid'];
    $qty = intval($_POST['qty']);
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['fid'] == $fid) {
            $item['qty'] = max(1, $qty);
            break;
        }
    }
    header("Location: view_cart.php");
    exit();
}

$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['qty'];
}


$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['fprice'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Shopping Cart</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .main-wrapper {
            max-width: 1300px;
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
        .nav-actions .cart-badge {
            background: #c0392b;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 4px;
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


        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }


        .product-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .page-subtitle {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            grid-column: span 2;
            margin-bottom: 4px;
        }

        .product-card {
            background: #fcfcfa;
            border-radius: 16px;
            border: 1px solid #eee;
            overflow: hidden;
            transition: 0.2s;
            padding: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border-color: #ccc;
        }
        .product-card .img-wrap {
            background: #f3f2ed;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1/1;
            padding: 8px;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
        }
        .product-card .img-wrap img {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
            display: block;
            transition: transform 0.3s;
        }
        .product-card .img-wrap:hover img {
            transform: scale(1.05);
        }
        .product-card .pname {
            font-weight: 700;
            font-size: 14px;
            color: #141414;
            margin: 6px 0 2px;
            text-align: center;
        }
        .product-card .pprice {
            font-weight: 600;
            font-size: 16px;
            color: #141414;
            text-align: center;
        }
        .product-card .pprice small {
            font-weight: 400;
            font-size: 11px;
            color: #888;
        }
        .product-card .add-form {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 6px;
            flex-wrap: wrap;
        }
        .product-card .add-form input[type="number"] {
            width: 48px;
            padding: 4px 2px;
            border: 1px solid #ddd;
            border-radius: 30px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            outline: none;
        }
        .product-card .add-form input[type="number"]:focus {
            border-color: #141414;
        }
        .btn-add {
            background: #141414;
            color: white;
            border: none;
            padding: 5px 14px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-add:hover {
            background: #333;
        }

        /* ----- 右欄：購物車 ----- */
        .cart-section {
            background: #fcfcfa;
            border-radius: 20px;
            border: 1px solid #eee;
            padding: 18px 20px 20px;
            position: sticky;
            top: 20px;
        }
        .cart-section .cart-title {
            font-size: 18px;
            font-weight: 700;
            color: #141414;
            padding-bottom: 12px;
            border-bottom: 2px solid #eaeaea;
            margin-bottom: 12px;
        }
        .cart-section .cart-title .badge {
            background: #141414;
            color: white;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 40px;
            margin-left: 8px;
        }

        .cart-empty {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 50px 1fr auto;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f0efec;
            align-items: center;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item .cart-img {
            width: 40px;
            height: 40px;
            background: #f3f2ed;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .cart-item .cart-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .cart-item .cart-info .cname {
            font-weight: 600;
            font-size: 14px;
            color: #141414;
        }
        .cart-item .cart-info .cprice {
            font-size: 13px;
            color: #6b6b6b;
        }
        .cart-item .cart-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }

        .btn-qty {
            background: #f0efec;
            border: 1px solid #ddd;
            color: #141414;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .btn-qty:hover {
            background: #e0ded9;
            border-color: #bbb;
        }
        .btn-qty:active {
            transform: scale(0.92);
        }
        .cart-item .cart-actions input[type="number"] {
            width: 36px;
            padding: 3px 0;
            border: 1px solid #ddd;
            border-radius: 30px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            outline: none;
        }
        .cart-item .cart-actions input[type="number"]:focus {
            border-color: #141414;
        }
        .cart-item .cart-subtotal {
            font-weight: 700;
            font-size: 14px;
            color: #141414;
            text-align: right;
            min-width: 60px;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            padding: 14px 0 6px;
            border-top: 2px solid #eaeaea;
            margin-top: 6px;
            font-size: 18px;
            font-weight: 700;
            color: #141414;
        }
        .cart-total .total-amount {
            font-size: 22px;
            color: #141414;
        }
        .cart-total .total-amount small {
            font-weight: 400;
            font-size: 13px;
            color: #888;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 12px;
            background: #141414;
            color: white;
            border: none;
            border-radius: 60px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            margin-top: 12px;
            text-align: center;
            text-decoration: none;
        }
        .btn-checkout:hover {
            background: #333;
        }
        .btn-checkout:disabled {
            background: #e0e0e0;
            color: #888;
            cursor: not-allowed;
        }

        .btn-view-furniture {
            display: block;
            width: 100%;
            padding: 12px;
            background: transparent;
            color: #141414;
            border: 1px solid #ccc;
            border-radius: 60px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            margin-top: 10px;
            text-align: center;
            text-decoration: none;
        }
        .btn-view-furniture:hover {
            background: #f0efec;
            border-color: #aaa;
        }

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


        @media (max-width: 1000px) {
            .two-column { grid-template-columns: 1fr; }
            .cart-section { position: static; }
            .product-list { grid-template-columns: 1fr 1fr 1fr; }
        }
        @media (max-width: 760px) {
            .product-list { grid-template-columns: 1fr 1fr; }
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .menu { right: 16px; top: 90px; width: 180px; }
        }
        @media (max-width: 480px) {
            .product-list { grid-template-columns: 1fr; }
            .menu { right: 10px; top: 110px; width: 170px; }
            .cart-item {
                grid-template-columns: 40px 1fr;
                gap: 6px;
            }
            .cart-item .cart-subtotal {
                grid-column: span 2;
                text-align: left;
                padding-left: 46px;
            }
            .cart-item .cart-actions {
                grid-column: span 2;
                padding-left: 46px;
                flex-wrap: wrap;
            }
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


    <div class="two-column">


        <div>
            <div class="product-list">
                <div class="page-subtitle">📋 Select Furniture</div>
                <?php foreach ($furnitures as $furniture){ ?>
                    <div class="product-card">
                        <div class="img-wrap" onclick="addToCart(<?php echo $furniture['fid']; ?>, '<?php echo addslashes($furniture['fname']); ?>', <?php echo $furniture['fprice']; ?>)">
                            <img src="<?php echo $furniture['fid']; ?>.png" alt="<?php echo $furniture['fname']; ?>">
                        </div>
                        <div class="pname"><?php echo $furniture['fname']; ?></div>
                        <div class="pprice">$<?php echo number_format($furniture['fprice'], 2); ?> <small>HKD</small></div>
                        <form method="post" action="view_cart.php" class="add-form">
                            <input type="hidden" name="fid" value="<?php echo $furniture['fid']; ?>">
                            <input type="hidden" name="fname" value="<?php echo $furniture['fname']; ?>">
                            <input type="hidden" name="fprice" value="<?php echo $furniture['fprice']; ?>">
                            <input type="number" name="qty" value="1" min="1" max="99">
                            <button type="submit" name="add_to_cart" class="btn-add">➕ Add</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>


        <div class="cart-section">
            <div class="cart-title">
                🛒 Shopping Cart
                <span class="badge"><?php echo $total_items; ?> items</span>
            </div>

            <?php if (empty($_SESSION['cart'])) { ?>
                <div class="cart-empty">
                    <p>🛒 Your cart is empty</p>
                    <p style="font-size:13px; color:#aaa;">Click "Add" on products to add them here</p>
                </div>
            <?php } else { ?>
                <?php foreach ($_SESSION['cart'] as $index => $item) { ?>
                    <div class="cart-item">
                        <div class="cart-img">
                            <img src="<?php echo $item['fid']; ?>.png" alt="<?php echo $item['fname']; ?>">
                        </div>
                        <div class="cart-info">
                            <div class="cname"><?php echo $item['fname']; ?></div>
                            <div class="cprice">$<?php echo number_format($item['fprice'], 2); ?> each</div>
                        </div>
                        <div class="cart-subtotal">$<?php echo number_format($item['fprice'] * $item['qty'], 2); ?></div>
                        <div class="cart-actions">
                            <form method="post" action="view_cart.php" style="display:flex; align-items:center; gap:4px;">
                                <input type="hidden" name="fid" value="<?php echo $item['fid']; ?>">
                                <button type="submit" name="change_qty" value="decrease" class="btn-qty">−</button>
                                <input type="number" name="qty" value="<?php echo $item['qty']; ?>" min="1" max="99" onchange="this.form.submit()" style="width:40px; padding:3px 0; border:1px solid #ddd; border-radius:30px; text-align:center; font-size:13px; font-weight:600; outline:none;">
                                <button type="submit" name="change_qty" value="increase" class="btn-qty">+</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>

                <div class="cart-total">
                    <span>Total:</span>
                    <span class="total-amount">$<?php echo number_format($total_price, 2); ?> <small>HKD</small></span>
                </div>

                <?php if (!empty($_SESSION['cart'])) { ?>
                    <a href="checkout.php" class="btn-checkout">✅ Proceed to Checkout</a>
                <?php } else { ?>
                    <button class="btn-checkout" disabled>🛒 Cart is empty</button>
                <?php } ?>
            <?php } ?>

            <a href="viewfurniture.php" class="btn-view-furniture">
                ⬅️ Back to View Furniture
            </a>

        </div>

    </div>

    <div class="divider-black footer-divider"></div>

    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

<script>
    function addToCart(fid, fname, fprice) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'view_cart.php';

        var inputs = [
            {name: 'fid', value: fid},
            {name: 'fname', value: fname},
            {name: 'fprice', value: fprice},
            {name: 'qty', value: 1},
            {name: 'add_to_cart', value: ''}
        ];

        inputs.forEach(function(data) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = data.name;
            input.value = data.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
</script>

</body>
</html>