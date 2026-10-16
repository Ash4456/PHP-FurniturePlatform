<?php
session_start();
if (isset($_SESSION['customer_id'])) {
    header("Location: loginstarpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Create Account</title>

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
            max-width: 820px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            padding: 30px 35px 25px;
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
            margin-bottom: 24px;
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
        }
        .nav-actions a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2c2c2c;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 6px 14px;
            border-radius: 40px;
            transition: background 0.15s;
        }
        .nav-actions a:hover {
            background: #f0efec;
        }
        .nav-actions .active-link {
            background: #141414;
            color: white;
        }
        .nav-actions .active-link:hover {
            background: #333;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .signup-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #141414;
            letter-spacing: 0.5px;
        }
        .signup-header p {
            color: #6b6b6b;
            font-size: 14px;
            margin-top: 4px;
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

        .signup-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .form-row label {
            font-weight: 600;
            font-size: 14px;
            color: #2c2c2c;
            min-width: 130px;
            text-align: right;
        }
        .form-row input {
            flex: 1;
            min-width: 180px;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
        }
        .form-row input:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }
        .form-row input.address-input {
            min-width: 240px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 18px;
        }
        .login-link p {
            font-size: 14px;
            color: #6b6b6b;
        }
        .login-link a {
            color: #141414;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }

        .footer-divider {
            margin: 30px 0 18px 0;
            border-top: 1px solid #e4e2db;
        }
        .footer-note {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            font-size: 14px;
            color: #6b6b6b;
            padding-top: 4px;
        }

        @media (max-width: 640px) {
            .main-wrapper {
                padding: 18px;
            }
            .form-row {
                flex-direction: column;
                align-items: stretch;
            }
            .form-row label {
                text-align: left;
                min-width: auto;
            }
            .form-row input {
                min-width: 100%;
            }
            .form-row input.address-input {
                min-width: 100%;
            }
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-actions {
                width: 100%;
                flex-wrap: wrap;
            }
            .form-actions {
                flex-direction: column;
                align-items: center;
            }
            .btn-primary, .btn-outline, .btn-outline-dark {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">

    <div class="navbar">
        <div class="brand">
            <div class="logo-box">PL</div>
            <h1>Premium<span>Living</span></h1>
        </div>
        <div class="nav-actions">
            <a href="index.html">🏠 Home</a>
            <a href="logincustomer.php">👤 Customer Login</a>
            <a href="createaccount.php" class="active-link">📝 Sign Up</a>
        </div>
    </div>

    <div class="signup-header">
        <h2>✏️ Create Account</h2>
        <p>Join Premium Living and enjoy exclusive offers</p>
    </div>

    <?php
    $error = isset($_GET['error']) ? $_GET['error'] : '';
    ?>

    <?php if ($error == 'password_mismatch') { ?>
        <div class="error-message">
            ❌ Password and Confirm Password do not match. Please try again.
        </div>
    <?php } elseif ($error == 'phone_exists') { ?>
        <div class="error-message">
            ❌ This phone number is already registered. Please use another number.
        </div>
    <?php } elseif ($error == 'invalid_phone') { ?>
        <div class="error-message">
            ❌ Phone number must be exactly 8 characters.
        </div>
    <?php } elseif ($error == 'db_error') { ?>
        <div class="error-message">
            ❌ Database error. Please try again later.
        </div>
    <?php } ?>
    <form class="signup-form" name="Create_Account" action="register_process.php" method="post">
        <div class="form-row">
            <label>👤 Customer Name</label>
            <input type="text" name="cname" placeholder="Full name" required>
        </div>
        <div class="form-row">
            <label>🔒 Password</label>
            <input type="password" name="cpassword" placeholder="Create password" required>
        </div>
        <div class="form-row">
            <label>✅ Confirm Password</label>
            <input type="password" name="cconfirm" placeholder="Re-enter password" required>
        </div>
        <div class="form-row">
            <label>📱 Contact Number</label>
            <input type="text" name="ctel" placeholder="Phone number (8 digits)" maxlength="8" pattern="[0-9]{8}" required>
        </div>
        <div class="form-row">
            <label>📍 Address</label>
            <input type="text" name="caddr" class="address-input" placeholder="Street, city, district" required>
        </div>
        <div class="form-row">
            <label>🏢 Company (Optional)</label>
            <input type="text" name="company" placeholder="Company name (if any)">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">📝 Sign Up</button>
            <input type="reset" value="🔄 Reset" class="btn-outline">
            <a href="logincustomer.php" class="btn-outline-dark">⬅️ Back to Login</a>
        </div>
    </form>

    <div class="login-link">
        <p>Already have an account? <a href="logincustomer.php">Login here</a></p>
    </div>

    <div class="footer-divider"></div>
    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>

</div>

</body>
</html>