<?php
// check_voucher.php — AJAX endpoint: returns JSON {valid, type, value}
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['valid' => false]);
    exit();
}

$code = mysqli_real_escape_string($conn, trim($_GET['code'] ?? ''));

if (empty($code)) {
    echo json_encode(['valid' => false]);
    exit();
}

$result = $conn->query("SELECT type, value FROM vouchers WHERE code = '$code' AND status = 'active' LIMIT 1");

if ($result && $result->num_rows > 0) {
    $v = $result->fetch_assoc();
    echo json_encode(['valid' => true, 'type' => $v['type'], 'value' => (float)$v['value']]);
} else {
    echo json_encode(['valid' => false]);
}
?>
