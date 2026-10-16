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
    die( mysqli_connect_error());
}


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';


if (!empty($search)) {
    $sql = "SELECT * FROM Furnitures WHERE fname LIKE '%$search%' OR fdesc LIKE '%$search%' ORDER BY fid";
} else {
    $sql = "SELECT * FROM Furnitures ORDER BY fid";
}

$rs = mysqli_query($conn, $sql);


if (!$rs) {
    die(mysqli_error($conn));
}

$furnitures = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $furnitures[] = $row;
}


if (isset($_GET['add_to_cart'])) {
    $fid =$_GET['add_to_cart'];

    $sql_item = "SELECT * FROM Furnitures WHERE fid = $fid";
    $rs_item = mysqli_query($conn, $sql_item);
    $item = mysqli_fetch_assoc($rs_item);

    if ($item) {
        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['fid'] == $fid) {
                $cart_item['qty'] += 1;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                    'fid' => $fid,
                    'fname' => $item['fname'],
                    'fprice' => $item['fprice'],
                    'qty' => 1
            ];
        }
    }

    mysqli_close($conn);
    header("Location: view_cart.php");
    exit();
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Furniture List</title>

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


        .search-area {
            text-align: center;
            margin: 6px 0 20px 0;
        }
        .search-box {
            display: inline-flex;
            align-items: center;
            background: #f3f2ef;
            border-radius: 60px;
            padding: 2px 2px 2px 20px;
            border: 1px solid #ddd;
            transition: 0.2s;
        }
        .search-box:focus-within {
            border-color: #a0a0a0;
            background: white;
        }
        .search-box input {
            border: none;
            background: transparent;
            padding: 10px 12px;
            font-size: 15px;
            width: 260px;
            outline: none;
            font-family: Arial, sans-serif;
        }
        .search-box input::placeholder {
            color: #aaa;
            font-weight: 300;
        }
        .search-box button {
            background: #141414;
            border: none;
            color: white;
            font-weight: 600;
            padding: 8px 28px;
            border-radius: 60px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            letter-spacing: 0.5px;
        }
        .search-box button:hover {
            background: #333;
        }


        .search-result-info {
            text-align: center;
            font-size: 14px;
            color: #6b6b6b;
            margin: -10px 0 16px 0;
        }
        .search-result-info strong {
            color: #141414;
        }

        /* 頁面標題 */
        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: #141414;
            margin: 10px 0 20px 0;
            text-align: center;
            letter-spacing: 0.5px;
        }


        .product-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .product-list .no-result {
            grid-column: span 2;
            text-align: center;
            padding: 60px 20px;
            color: #6b6b6b;
            font-size: 16px;
        }

        .product-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #fcfcfa;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: 0.2s;
            padding: 12px;
            align-items: center;
        }
        .product-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -8px rgba(0,0,0,0.08);
            border-color: #ccc;
        }

        .product-item .img-wrap {
            background: #f3f2ed;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1/1;
            padding: 10px;
        }
        .product-item .img-wrap img {
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
            display: block;
        }
        .product-item .img-wrap img[src=""] {
            display: none;
        }

        .product-item .info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .product-item .info .name {
            font-weight: 700;
            font-size: 17px;
            color: #141414;
        }
        .product-item .info .desc {
            font-size: 13px;
            color: #5f5f5f;
            line-height: 1.4;
        }
        .product-item .info .price {
            font-weight: 700;
            font-size: 18px;
            color: #141414;
        }
        .product-item .info .price small {
            font-weight: 400;
            font-size: 12px;
            color: #888;
            margin-left: 4px;
        }
        .product-item .info .stock {
            font-size: 13px;
            color: #2e7d32;
            font-weight: 500;
        }
        .product-item .info .stock.low {
            color: #c0392b;
        }

        .btn-buy {
            background: #141414;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            margin-top: 6px;
            width: fit-content;
        }
        .btn-buy:hover {
            background: #333;
        }
        .btn-buy:active {
            transform: scale(0.96);
        }

        /* 底部 */
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


        @media (max-width: 820px) {
            .product-list {
                grid-template-columns: 1fr;
            }
            .product-list .no-result {
                grid-column: span 1;
            }
            .product-item {
                grid-template-columns: 1fr 1.5fr;
            }
        }
        @media (max-width: 600px) {
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .product-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .product-item .info .btn-buy {
                margin: 6px auto 0;
            }
            .search-box input { width: 140px; }
            .menu { right: 16px; top: 90px; width: 180px; }
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
            .product-list .no-result { padding: 30px 16px; }
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
                <a href="Updateprofile.php">Welcome, <?php echo $customer_name; ?></a>
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


    <div class="search-area">
        <form method="get" action="viewfurniture.php" style="display:inline-flex; align-items:center;">
            <div class="search-box">
                <input type="text" name="search" placeholder="🔍 Search furniture..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Search</button>
            </div>
            <?php if (!empty($search)) { ?>
                <a href="viewfurniture.php" style="margin-left:10px; color:#141414; text-decoration:none; font-weight:600; font-size:13px; padding:6px 12px; border-radius:40px; border:1px solid #ddd; transition:0.15s;" onmouseover="this.style.background='#f0efec'" onmouseout="this.style.background='transparent'">✕ Clear</a>
            <?php } ?>
        </form>
    </div>

    <div class="page-title">
        📋 Our Furniture Collection
    </div>


    <?php if (!empty($search)) { ?>
        <div class="search-result-info">
            Found <strong><?php echo count($furnitures); ?></strong> result(s) for "<strong><?php echo $search; ?></strong>"
        </div>
    <?php } ?>


    <div class="product-list">
        <?php if (count($furnitures) > 0) { ?>
            <?php foreach ($furnitures as $index => $furniture) {

                $stock_values = [45, 12, 8, 3, 15, 6];
                $stock_value = isset($stock_values[$index]) ? $stock_values[$index] : rand(5, 50);
                $stock_class = $stock_value <= 10 ? "low" : "";
                ?>
                <div class="product-item">
                    <div class="img-wrap">
                        <img src="<?php echo ($furniture['fid']); ?>.png" alt="<?php echo htmlspecialchars($furniture['fname']); ?>">
                    </div>
                    <div class="info">
                        <div class="name"><?php echo $furniture['fname']; ?></div>
                        <div class="desc"><?php echo $furniture['fdesc']; ?></div>
                        <div class="price">$<?php echo number_format($furniture['fprice'], 2); ?> <small>HKD</small></div>
                        <div class="stock <?php echo $stock_class; ?>">📦 Stock: <?php echo $stock_value; ?></div>
                        <!-- Buy Now 按鈕：跳轉到 view_cart.php 並加入購物車 -->
                        <a href="viewfurniture.php?add_to_cart=<?php echo $furniture['fid']; ?>" class="btn-buy">🛒 Buy Now</a>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="no-result">
                🔍
                No furniture found matching "<strong><?php echo $search; ?></strong>"
                <br><br>
                <a href="viewfurniture.php" style="color:#141414; font-weight:600; text-decoration:none; padding:8px 24px; border:1px solid #141414; border-radius:60px; display:inline-block; margin-top:8px;">📋 View All Furniture</a>
            </div>
        <?php } ?>
    </div>
</div>

<div class="divider-black footer-divider"></div>


<div class="footer-note">
    <span>© Premium Living Furniture Co. Ltd 2026</span>

</div>

</div>

</body>
</html>