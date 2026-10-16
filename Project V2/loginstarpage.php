<?php
session_start();


if (!isset($_SESSION['customer_id'])) {
    header("Location: logincustomer.php");
    exit();
}

$customer_name = $_SESSION['customer_name'];
$customer_id = $_SESSION['customer_id'];


require_once 'db_config.php';

if (isset($conn) && $conn !== false) {
    $sql = "SELECT COUNT(*) as total FROM Orders WHERE cid = $customer_id";
    $rs = mysqli_query($conn, $sql);
    if ($rs) {
        $row = mysqli_fetch_assoc($rs);
        $order_count = $row['total'];
    } else {
        $order_count = 0;
    }
    mysqli_close($conn);
} else {
    $order_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Home</title>

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

        /* 黑色分隔線 */
        .divider-black {
            background: #141414;
            height: 6px;
            border-radius: 20px;
            margin: 12px 0 18px 0;
        }

        /* 搜尋列 */
        .search-area {
            text-align: center;
            margin: 6px 0 20px 0;
        }
        .search-area form {
            display: inline-flex;
            align-items: center;
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


        .welcome-banner {
            background: linear-gradient(135deg, #141414 0%, #2d2d2d 100%);
            border-radius: 20px;
            padding: 28px 32px;
            margin: 6px 0 22px 0;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .welcome-banner .greeting h2 {
            font-size: 24px;
            font-weight: 700;
        }
        .welcome-banner .greeting p {
            font-size: 14px;
            color: #b0b0b0;
            margin-top: 4px;
        }
        .welcome-banner .stats {
            display: flex;
            gap: 30px;
        }
        .welcome-banner .stats .stat-item {
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
            padding: 4px 12px;
            border-radius: 12px;
        }
        .welcome-banner .stats .stat-item:hover {
            transform: scale(1.05);
            background: rgba(255,255,255,0.1);
        }
        .welcome-banner .stats .stat-item .number {
            font-size: 28px;
            font-weight: 700;
            color: #f5c542;
        }
        .welcome-banner .stats .stat-item .label {
            font-size: 12px;
            color: #b0b0b0;
            display: block;
            margin-top: 2px;
        }


        .hot-title {
            background: #141414;
            color: white;
            text-align: center;
            padding: 12px 0;
            border-radius: 60px;
            margin: 20px 0 22px 0;
            font-weight: 600;
            font-size: 20px;
            letter-spacing: 1px;
        }

        /* 產品表格 */
        .product-table {
            margin-top: 4px;
        }
        .product-table td {
            vertical-align: top;
            padding: 0 6px;
        }
        .product-card {
            background: #fcfcfa;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px -8px rgba(0,0,0,0.08);
            border-color: #ccc;
        }

        .product-img {
            background: #f3f2ed;
            padding: 20px 10px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        .product-img img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            display: block;
        }

        .product-detail {
            padding: 16px 18px 20px;
            background: white;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 700;
            font-size: 18px;
            color: #141414;
        }
        .product-desc {
            font-size: 14px;
            color: #5f5f5f;
            margin: 6px 0 4px;
            line-height: 1.4;
        }
        .product-price {
            font-weight: 700;
            font-size: 20px;
            color: #141414;
            margin: 4px 0 6px;
        }
        .product-price small {
            font-weight: 400;
            font-size: 13px;
            color: #888;
            margin-left: 4px;
        }
        .product-stock {
            font-size: 13px;
            color: #2e7d32;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .product-stock.low {
            color: #c0392b;
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: auto;
            flex-wrap: wrap;
        }
        .btn-buy {
            background: #141414;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-buy:hover {
            background: #333;
        }
        .btn-buy:active {
            transform: scale(0.96);
        }

        .view-all-container {
            text-align: center;
            margin: 16px 0 8px;
        }
        .btn-view-all {
            background: transparent;
            border: 1px solid #141414;
            padding: 10px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            color: #141414;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-view-all:hover {
            background: #141414;
            color: white;
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


        @media (max-width: 760px) {
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .search-box input { width: 140px; }
            .product-table td { display: block; width: 100% !important; padding: 8px 0; }
            .product-card { margin-bottom: 12px; }
            .footer-note { flex-direction: column; align-items: flex-start; gap: 8px; }
            .menu { right: 16px; top: 90px; width: 180px; }
            .nav-actions .welcome { font-size: 13px; }
            .welcome-banner { flex-direction: column; text-align: center; padding: 20px; }
            .welcome-banner .stats { gap: 20px; justify-content: center; }
            .search-area form { width: 100%; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
        }
        @media (max-width: 480px) {
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
            .menu { right: 10px; top: 110px; width: 170px; }
            .welcome-banner .stats { flex-wrap: wrap; gap: 12px; }
            .welcome-banner .stats .stat-item .number { font-size: 20px; }
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
                <a href="Updateprofile.php">Welcome, <?php echo htmlspecialchars($customer_name); ?></a>
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


    <div class="welcome-banner">
        <div class="greeting">
            <h2>👋 Welcome Back, <?php echo $customer_name; ?>!</h2>
            <p>🕐 Today is <?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="stats">
            <a href="orderrecord.php" style="text-decoration:none; color:inherit;">
                <div class="stat-item">
                    <span class="number"><?php echo $order_count; ?></span>
                    <span class="label">📦 Orders</span>
                </div>
            </a>
        </div>
    </div>


    <div class="search-area">
        <form method="get" action="viewfurniture.php">
            <div class="search-box">
                <input type="text" name="search" placeholder="🔍 Search furniture..." value="">
                <button type="submit">🔍 Search</button>
            </div>
        </form>
    </div>

    <div class="hot-title">
        🔥Hot Furniture Picks🔥
    </div>


    <table class="product-table">
        <colgroup>
            <col style="width:32%">
            <col style="width:2%">
            <col style="width:32%">
            <col style="width:2%">
            <col style="width:32%">
        </colgroup>
        <tr>
            <td>
                <div class="product-card">
                    <div class="product-img">
                        <img src="1.png" alt="Oak Dining Chair">
                    </div>
                    <div class="product-detail">
                        <div class="product-name">Oak Dining Chair</div>
                        <div class="product-desc">Classic style dining chair made of solid oak</div>
                        <div class="product-price">$450 <small>HKD</small></div>
                        <div class="product-stock">📦 Stock: 28</div>
                        <div class="product-actions">
                            <a href="view_cart.php" class="btn-buy">🛒 Buy Now</a>
                        </div>
                    </div>
                </div>
            </td>
            <td></td>
            <td>
                <div class="product-card">
                    <div class="product-img">
                        <img src="3.png" alt="3-Seater Fabric Sofa">
                    </div>
                    <div class="product-detail">
                        <div class="product-name">3-Seater Fabric Sofa</div>
                        <div class="product-desc">Comfortable grey fabric sofa with foam filling</div>
                        <div class="product-price">$3,800 <small>HKD</small></div>
                        <div class="product-stock">📦 Stock: 20</div>
                        <div class="product-actions">
                            <a href="view_cart.php" class="btn-buy">🛒 Buy Now</a>
                        </div>
                    </div>
                </div>
            </td>
            <td></td>
            <td>
                <div class="product-card">
                    <div class="product-img">
                        <img src="4.png" alt="Wooden Wardrobe">
                    </div>
                    <div class="product-detail">
                        <div class="product-name">Wooden Wardrobe</div>
                        <div class="product-desc">Double door wardrobe with hanging space</div>
                        <div class="product-price">$1,800 <small>HKD</small></div>
                        <div class="product-stock low">📦 Stock: 10 (Low)</div>
                        <div class="product-actions">
                            <a href="view_cart.php" class="btn-buy">🛒 Buy Now</a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="view-all-container">
        <a href="viewfurniture.php" class="btn-view-all">➡️ View All Furniture</a>
    </div>

    <div class="divider-black footer-divider"></div>
    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

</body>
</html>