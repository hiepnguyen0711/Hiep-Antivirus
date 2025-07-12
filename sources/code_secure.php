<?php
header('Content-Type: application/json');
session_start();
include "../admin/lib/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xac_nhan'])) {
    $ma = addslashes($_POST['xac_nhan']);
    if ($ma === _hiep_code) {
        setcookie('code', _hiep_code, time() + (86400 * 30 * 365), "/"); // Lưu cookie 1 năm
        echo json_encode(['status' => 'success']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Mã PIN không chính xác. Vui lòng thử lại.']);
        exit;
    }
}

// Kiểm tra cookie để quyết định ẩn form nhập PIN
// $displayForm = !(isset($_COOKIE['code']) && $_COOKIE['code'] === '580859');
?>
