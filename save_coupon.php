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

$id = $_POST['id'] ?? null;
$code = $_POST['code'] ?? '';
$discount = $_POST['discount'] ?? '';
$usage_limit = $_POST['usage_limit'] ?? '';
$expiry_date = $_POST['expiry_date'] ?? '';

if (empty($code) || empty($discount) || empty($usage_limit) || empty($expiry_date)) {
    header("Location: admin_dashboard.php?page=coupons&error=missing_fields");
    exit();
}

if ($id) {
    // Edit existing coupon
    $coupon_found = false;
    foreach ($coupons as &$coupon) {
        if ($coupon['id'] == $id) {
            $coupon['code'] = $code;
            $coupon['discount'] = $discount;
            $coupon['usage_limit'] = $usage_limit;
            $coupon['expiry_date'] = $expiry_date;
            $coupon_found = true;
            break;
        }
    }
    if (!$coupon_found) {
        header("Location: admin_dashboard.php?page=coupons&error=coupon_not_found");
        exit();
    }
} else {
    // Add new coupon
    $new_coupon = [
        'id' => uniqid(),
        'code' => $code,
        'discount' => $discount,
        'usage_limit' => $usage_limit,
        'expiry_date' => $expiry_date,
        'times_used' => 0
    ];
    $coupons[] = $new_coupon;
}

$json_data = json_encode($coupons, JSON_PRETTY_PRINT);
if (file_put_contents($coupons_file_path, $json_data) === false) {
    header("Location: admin_dashboard.php?page=coupons&error=file_save_error");
    exit();
}

header("Location: admin_dashboard.php?page=coupons&status=success");
exit();
?>
