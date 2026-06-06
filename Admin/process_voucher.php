<?php
// process_voucher.php — Admin: Add or deactivate vouchers from the Dashboard
session_start();
require_once '../db.php';

// Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD NEW VOUCHER
    if (isset($_POST['add_voucher'])) {
        $code  = mysqli_real_escape_string($conn, strtoupper(trim($_POST['voucher_code'])));
        $type  = ($_POST['voucher_type'] === 'flat') ? 'flat' : 'percentage';
        $value = floatval($_POST['voucher_value']);

        if (empty($code) || $value <= 0) {
            header("Location: ad_DashBoard.php?voucher_error=invalid");
            exit();
        }

        // Check duplicate
        $check = $conn->query("SELECT id FROM vouchers WHERE code = '$code'");
        if ($check && $check->num_rows > 0) {
            header("Location: ad_DashBoard.php?voucher_error=duplicate");
            exit();
        }

        $conn->query("INSERT INTO vouchers (code, type, value, status) VALUES ('$code', '$type', $value, 'active')");
        $conn->query("INSERT INTO system_logs (log_message) VALUES ('Admin added voucher: $code ($type, $value)')");
        header("Location: ad_DashBoard.php?voucher_success=1");
        exit();
    }

    // DEACTIVATE VOUCHER
    if (isset($_POST['deactivate_voucher'])) {
        $id = intval($_POST['voucher_id']);
        $conn->query("UPDATE vouchers SET status = 'inactive' WHERE id = $id");
        $conn->query("INSERT INTO system_logs (log_message) VALUES ('Admin deactivated voucher ID: $id')");
        header("Location: ad_DashBoard.php?voucher_success=deactivated");
        exit();
    }
}

// Fallback
header("Location: ad_DashBoard.php");
exit();
?>
