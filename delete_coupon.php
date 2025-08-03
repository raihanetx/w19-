<?php
session_start();

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php?page=coupons&error=invalid_request");
    exit();
}

$coupons_file_path = __DIR__ . '/coupons.json';
$coupons = [];
if (file_exists($coupons_file_path)) {
    $json_data = file_get_contents($coupons_file_path);
    if ($json_data) {
        $coupons = json_decode($json_data, true);
    }
}

$id = $_POST['coupon_id_to_delete'] ?? null;

if (empty($id)) {
    header("Location: admin_dashboard.php?page=coupons&error=missing_id");
    exit();
}

$coupon_found = false;
$updated_coupons = [];
foreach ($coupons as $coupon) {
    if ($coupon['id'] == $id) {
        $coupon_found = true;
    } else {
        $updated_coupons[] = $coupon;
    }
}

if (!$coupon_found) {
    header("Location: admin_dashboard.php?page=coupons&error=coupon_not_found");
    exit();
}

$json_data = json_encode($updated_coupons, JSON_PRETTY_PRINT);
if (file_put_contents($coupons_file_path, $json_data) === false) {
    header("Location: admin_dashboard.php?page=coupons&error=file_save_error");
    exit();
}

header("Location: admin_dashboard.php?page=coupons&status=success");
exit();
