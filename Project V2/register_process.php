<?php

require_once 'db_config.php';


if (!isset($conn) || $conn === false) {
    die("資料庫連線失敗：" . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cname = trim($_POST['cname']);
    $cpassword = trim($_POST['cpassword']);
    $cconfirm = trim($_POST['cconfirm']);
    $ctel = trim($_POST['ctel']);
    $caddr = trim($_POST['caddr']);
    $company = isset($_POST['company']) ? trim($_POST['company']) : null;


    if ($cpassword !== $cconfirm) {
        header("Location: createaccount.php?error=password_mismatch");
        exit();
    }

    $cname = mysqli_real_escape_string($conn, $cname);
    $cpassword = mysqli_real_escape_string($conn, $cpassword);
    $ctel = mysqli_real_escape_string($conn, $ctel);
    $caddr = mysqli_real_escape_string($conn, $caddr);
    if ($company) {
        $company = mysqli_real_escape_string($conn, $company);
    }


    $check_sql = "SELECT * FROM Customers WHERE ctel = '$ctel'";
    $check_rs = mysqli_query($conn, $check_sql);

    if (!$check_rs) {
        header("Location: createaccount.php?error=db_error");
        exit();
    }

    if (mysqli_num_rows($check_rs) > 0) {
        header("Location: createaccount.php?error=phone_exists");
        exit();
    }

    // 插入新用戶
    if ($company) {
        $sql = "INSERT INTO Customers (cname, cpassword, ctel, caddr, company) 
                VALUES ('$cname', '$cpassword', '$ctel', '$caddr', '$company')";
    } else {
        $sql = "INSERT INTO Customers (cname, cpassword, ctel, caddr) 
                VALUES ('$cname', '$cpassword', '$ctel', '$caddr')";
    }

    if (mysqli_query($conn, $sql)) {
        // ✅ 跳轉到 logincustomer.php
        header("Location: logincustomer.php?success=1");
        exit();
    } else {
        header("Location: createaccount.php?error=db_error");
        exit();
    }
} else {
    header("Location: createaccount.php");
    exit();
}

mysqli_close($conn);
?>
