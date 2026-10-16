<?php

session_start();
require_once 'db_config.php';


if (!isset($conn) || $conn === false) {
    die("資料庫連線失敗：" . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = trim($_POST['Customername']);
    $password = trim($_POST['password']);


    $customer_id = mysqli_real_escape_string($conn, $customer_id);
    $password = mysqli_real_escape_string($conn, $password);


    $sql = "SELECT * FROM Customers WHERE cid = '$customer_id' AND cpassword = '$password'";
    $rs = mysqli_query($conn, $sql);

    if (!$rs) {
        die(mysqli_error($conn));
    }

    if (mysqli_num_rows($rs) == 1) {
        $user = mysqli_fetch_assoc($rs);
        $_SESSION['customer_id'] = $user['cid'];
        $_SESSION['customer_name'] = $user['cname'];
        $_SESSION['customer_type'] = 'customer';

        header("Location: loginstarpage.php");
        exit();
    } else {

        header("Location: logincustomer.php?error=1");
        exit();
    }
} else {
    header("Location: logincustomer.php");
    exit();
}

mysqli_close($conn);
?>
