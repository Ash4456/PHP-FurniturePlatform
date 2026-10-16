<?php
session_start();

if (isset($_SESSION['customer_id'])) {
    header("Location: loginstarpage.php");
    exit();
}

require_once 'db_config.php';
if (!isset($conn) || $conn === false) {
    die(mysqli_connect_error());
}
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = trim($_POST['customer_id']);
    $ctel = trim($_POST['ctel']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);


    if (empty($customer_id)) {
        $message = "Please enter your Customer ID.";
        $message_type = 'error';
    } elseif (empty($ctel)) {
        $message = "Please enter your registered phone number.";
        $message_type = 'error';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $message = "Please enter a new password.";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
        $message_type = 'error';
    } elseif (strlen($new_password) < 4) {
        $message = "Password must be at least 4 characters.";
        $message_type = 'error';
    } else {

        $customer_id = mysqli_real_escape_string($conn, $customer_id);
        $ctel = mysqli_real_escape_string($conn, $ctel);

        $sql = "SELECT * FROM Customers WHERE cid = '$customer_id' AND ctel = '$ctel'";
        $rs = mysqli_query($conn, $sql);

        if (!$rs) {
            $message = "Database error. Please try again.";
            $message_type = 'error';
        } elseif (mysqli_num_rows($rs) == 0) {
            $message = "Customer ID and Phone number do not match our records. Please try again.";
            $message_type = 'error';
        } else {

            $new_password = mysqli_real_escape_string($conn, $new_password);
            $sql_update = "UPDATE Customers SET cpassword = '$new_password' WHERE cid = '$customer_id'";

            if (mysqli_query($conn, $sql_update)) {
                $message = "Password reset successfully! Please login with your new password.";
                $message_type = 'success';
            } else {
                $message = "Database error: " . mysqli_error($conn);
                $message_type = 'error';
            }
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
    <title>Premium Living · Forgot Password</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-wrapper {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            padding: 30px 35px 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        }

        .brand {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand .logo-box {
            background: #141414;
            color: white;
            font-size: 22px;
            font-weight: 700;
            width: 80px;
            height: 80px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .brand .logo-box img {
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
        .brand p {
            color: #6b6b6b;
            font-size: 14px;
            margin-top: 4px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #2c2c2c;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.15s;
        }
        .back-link:hover {
            color: #141414;
        }

        .message {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
        }
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #fce4ec;
            color: #c0392b;
            border: 1px solid #f5c6cb;
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
        .form-group input {
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
        .form-group input:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 20px;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary:hover {
            background: #333;
        }
        .btn-primary:active {
            transform: scale(0.96);
        }

        .btn-secondary {
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
        .btn-secondary:hover {
            background: #f0efea;
            border-color: #aaa;
        }

        .footer-divider {
            margin: 30px 0 18px 0;
            border-top: 1px solid #e4e2db;
        }
        .footer-note {
            text-align: center;
            font-size: 14px;
            color: #6b6b6b;
            padding-top: 4px;
        }


    </style>
</head>
<body>

<div class="main-wrapper">

    <div class="brand">
        <div class="logo-box">
            <img src="Designer (3).png" alt="Premium Living Logo">
        </div>
        <h1>Premium<span>Living</span></h1>
        <p>Reset your password</p>
    </div>

    <a href="logincustomer.php" class="back-link">← Back to Login</a>

    <?php if (!empty($message)) { ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <?php if ($message_type != 'success') { ?>
        <form method="post" action="forget_password.php">
            <div class="form-group">
                <label>Customer ID</label>
                <input type="text" name="customer_id" placeholder="Enter your Customer ID" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="ctel" placeholder="Enter your registered phone number" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password (min 4 characters)" required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Reset Password</button>
                <a href="logincustomer.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    <?php } else { ?>
        <div style="text-align:center; padding:10px 0;">
            <a href="logincustomer.php" class="btn-primary">Go to Login</a>
        </div>
    <?php } ?>

    <div class="footer-divider"></div>
    <div class="footer-note">
        Premium Living Furniture Co. Ltd 2026
    </div>

</div>

</body>
</html>