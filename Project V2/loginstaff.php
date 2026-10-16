<?php

session_start();

if (isset($_SESSION['staff_id'])) {
    header("Location: staff_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living · Staff Login</title>
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
            max-width: 1100px;
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

        .auth-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: stretch;
        }

        .auth-image {
            background: #f3f2ed;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }
        .auth-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 10px 0;
        }

        .auth-form h2 {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
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

        .form-group {
            margin-bottom: 16px;
            text-align: center;
        }
        .form-group label {
            display: inline-block;
            width: 110px;
            font-weight: 600;
            font-size: 15px;
            color: #2c2c2c;
            text-align: right;
            margin-right: 10px;
        }
        .form-group input {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-size: 14px;
            width: 220px;
            outline: none;
            transition: 0.2s;
            font-family: Arial, sans-serif;
        }
        .form-group input:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20,20,20,0.06);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
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

        @media (max-width: 820px) {
            .auth-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .auth-image {
                min-height: 200px;
                order: -1;
            }
            .main-wrapper {
                padding: 20px;
            }
            .form-group label {
                width: 90px;
                font-size: 14px;
            }
            .form-group input {
                width: 180px;
            }
        }
        @media (max-width: 480px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-actions {
                width: 100%;
                flex-wrap: wrap;
            }
            .form-group label {
                display: block;
                text-align: center;
                width: 100%;
                margin-bottom: 4px;
            }
            .form-group input {
                width: 90%;
                max-width: 260px;
            }
            .form-actions {
                flex-direction: column;
                align-items: center;
            }
            .btn-primary, .btn-outline {
                width: 80%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">

    <div class="navbar">
        <div class="brand">
            <div class="logo-box">
                <img src="Designer (3).png" alt="Premium Living Logo">
            </div>
            <h1>Premium<span>Living</span></h1>
        </div>
        <div class="nav-actions">
            <a href="index.html">🏠 Home</a>
            <a href="logincustomer.php">👤 Customer Login</a>
            <a href="loginstaff.php" class="active-link">👔 Staff Login</a>
        </div>
    </div>

    <div class="auth-grid">
        <div class="auth-image">
            <img src="login1.jpg" alt="Staff Office" >
        </div>

        <div class="auth-form">
            <h2>👔 Staff Login</h2>

            <?php
            $error = isset($_GET['error']) ? $_GET['error'] : '';
            $success = isset($_GET['success']) ? $_GET['success'] : '';
            ?>

            <?php if ($error == 1) { ?>
                <div class="error-message">
                    ❌ Invalid Staff ID or Password. Please try again.
                </div>
            <?php } ?>

            <?php if ($success == 1) { ?>
                <div class="success-message">
                    ✅ Login successful!
                </div>
            <?php } ?>

            <form name="staff_login" action="login_staff_process.php" method="post">
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" name="staff_id" placeholder="Enter your Staff ID" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">🔑 Login</button>
                    <input type="reset" value="↩️ Reset" class="btn-outline">
                </div>
            </form>
        </div>
    </div>

    <div class="footer-divider"></div>
    <div class="footer-note">
        <span>© Premium Living Furniture Co. Ltd 2026</span>
    </div>
</div>

</body>
</html>