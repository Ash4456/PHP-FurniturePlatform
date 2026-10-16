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

$sql = "SELECT * FROM Customers WHERE cid = '$customer_id'";
$rs = mysqli_query($conn, $sql);

if (!$rs) {
    die(mysqli_error($conn));
}

$user = mysqli_fetch_assoc($rs);

if (!$user) {
    header("Location: logincustomer.php");
    exit();
}

$update_success = false;
$update_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $ctel = trim($_POST['ctel']);
    $caddr = trim($_POST['caddr']);



    if (empty($ctel)) {
        $update_error = "Contact number is required.";
    }


    if (empty($caddr)) {
        $update_error = "Address is required.";
    }


    if (empty($update_error) && (!empty($new_password) || !empty($confirm_password))) {
        if ($new_password !== $confirm_password) {
            $update_error = "Password and Confirm Password do not match.";
        } elseif (strlen($new_password) < 4) {
            $update_error = "Password must be at least 4 characters.";
        }
    }


    if (empty($update_error)) {
        $ctel = mysqli_real_escape_string($conn, $ctel);
        $caddr = mysqli_real_escape_string($conn, $caddr);

        if (!empty($new_password)) {
            $new_password = mysqli_real_escape_string($conn, $new_password);
            $sql = "UPDATE Customers 
                    SET cpassword = '$new_password', ctel = '$ctel', caddr = '$caddr' 
                    WHERE cid = '$customer_id'";
        } else {
            $sql = "UPDATE Customers 
                    SET ctel = '$ctel', caddr = '$caddr' 
                    WHERE cid = '$customer_id'";
        }

        if (mysqli_query($conn, $sql)) {
            $update_success = true;


            $sql = "SELECT * FROM Customers WHERE cid = '$customer_id'";
            $rs = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($rs);
        } else {
            $update_error = "Database error: " . mysqli_error($conn);
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
    <title>Premium Living · Update Profile</title>

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
            margin: 6px 0 20px 0;
            text-align: center;
            letter-spacing: 0.5px;
        }


        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 18px;
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


        .tab-bar {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
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

        /* ----- 個人資料表單 ----- */
        .profile-card {
            max-width: 480px;
            margin: 0 auto;
            background: #fcfcfa;
            border-radius: 20px;
            padding: 28px 32px 32px;
            border: 1px solid #eee;
        }

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
        .form-group textarea {
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
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }
        .form-group input:disabled {
            background: #f3f2ef;
            color: #888;
            cursor: not-allowed;
        }
        .form-group textarea {
            border-radius: 16px;
            resize: vertical;
            min-height: 70px;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: #141414;
            color: white;
            border: none;
            padding: 10px 32px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.15s;
            font-family: Arial, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary:hover {
            background: #333;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #ccc;
            padding: 9px 28px;
            border-radius: 60px;
            font-weight: 500;
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
        .btn-outline:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        .btn-outline-dark {
            background: transparent;
            border: 1px solid #141414;
            padding: 9px 28px;
            border-radius: 60px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            color: #141414;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
            font-family: Arial, sans-serif;
        }
        .btn-outline-dark:hover {
            background: #141414;
            color: white;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
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


        @media (max-width: 600px) {
            .main-wrapper { padding: 16px; }
            .brand h1 { font-size: 18px; }
            .profile-card { padding: 20px; }
            .tab-btn { padding: 6px 18px; font-size: 13px; }
            .menu { right: 16px; top: 90px; width: 180px; }
            .navbar { flex-direction: column; align-items: flex-start; }
            .nav-actions { width: 100%; justify-content: space-between; }
            .form-actions { flex-direction: column; align-items: center; }
            .btn-primary, .btn-outline, .btn-outline-dark {
                width: 100%;
                justify-content: center;
            }
        }
        @media (max-width: 480px) {
            .menu { right: 10px; top: 110px; width: 170px; }
            .tab-bar { flex-direction: column; align-items: center; }
            .tab-btn { width: 80%; text-align: center; }
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


    <div class="page-title">
        ⚙️ My Profile
    </div>


    <div class="tab-bar">
        <span class="tab-btn active">👤 Customer Profile</span>
        <a href="orderrecord.php" class="tab-btn inactive">📋 Order Records</a>
    </div>


    <?php if ($update_success) { ?>
        <div class="success-message">
            ✅ Profile updated successfully!
        </div>
    <?php } ?>

    <?php if (!empty($update_error)) { ?>
        <div class="error-message">
            ❌ <?php echo htmlspecialchars($update_error); ?>
        </div>
    <?php } ?>


    <div class="profile-card">
        <form name="updateProfile" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

            <div class="form-group">
                <label>👤 Customer Name</label>
                <input type="text" name="cname" value="<?php echo htmlspecialchars($user['cname']); ?>" placeholder="Enter your name" disabled>
            </div>

            <div class="form-group">
                <label>🆔 Customer ID</label>
                <input type="text" value="<?php echo htmlspecialchars($user['cid']); ?>" disabled>
            </div>

            <div class="form-group">
                <label>🔒 New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password (leave blank to keep current)">
            </div>

            <div class="form-group">
                <label>✅ Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter new password">
            </div>

            <div class="form-group">
                <label>📱 Contact Number</label>
                <input type="text" name="ctel" value="<?php echo htmlspecialchars($user['ctel']); ?>" placeholder="Phone number" required>
            </div>

            <div class="form-group">
                <label>📍 Address</label>
                <textarea name="caddr" rows="3" required><?php echo htmlspecialchars($user['caddr']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">💾 Update</button>
                <input type="reset" value="↩️ Cancel" class="btn-outline">
            </div>
        </form>
    </div>


    <div class="back-home">
        <a href="loginstarpage.php" class="btn-outline-dark">⬅️ Back to Home</a>
    </div>

    <div class="divider-black footer-divider"></div>


    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

</body>
</html>