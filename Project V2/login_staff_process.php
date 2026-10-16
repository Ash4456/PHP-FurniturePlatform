<?php

session_start();
require_once 'db_config.php';

if (!isset($conn) || $conn === false) {
    die( mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = trim($_POST['staff_id']);
    $password = trim($_POST['password']);

    $staff_id = mysqli_real_escape_string($conn, $staff_id);
    $password = mysqli_real_escape_string($conn, $password);

    $sql = "SELECT * FROM staffs WHERE sid = '$staff_id' AND spassword = '$password'";
    $rs = mysqli_query($conn, $sql);

    if (!$rs) {
        die(mysqli_error($conn));
    }

    if (mysqli_num_rows($rs) == 1) {
        $staff = mysqli_fetch_assoc($rs);
        $_SESSION['staff_id'] = $staff['sid'];
        $_SESSION['staff_name'] = $staff['sname'];
        $_SESSION['staff_role'] = $staff['srole'];
        $_SESSION['staff_type'] = 'staff';

        header("Location: staff_dashboard.php");
        exit();
    } else {
        header("Location: loginstaff.php?error=1");
        exit();
    }
} else {
    header("Location: loginstaff.php");
    exit();
}
mysqli_close($conn);
?>