<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'admin'){
        header("Location: admin/admin_dashboard.php");
    } else {
        header("Location: employee/employee_dashboard.php");
    }
} else {
    header("Location: auth/login.php");
}
exit();
?>
