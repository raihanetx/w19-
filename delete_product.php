<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php?page=products&error=invalid_request");
    exit();
}

if (!isset($_POST['product_id_to_delete']) || empty($_POST['product_id_to_delete'])) {
    header("Location: admin_dashboard.php?page=products&error=no_product_id");
    exit();
}

$product_id_to_delete = $_POST['product_id_to_delete'];
$products_file_path = __DIR__ . '/products.json';
$all_products = [];

// Read existing products
if (file_exists($products_file_path)) {
    $json_data = file_get_contents($products_file_path);
    if ($json_data) {
        $decoded_data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $all_products = $decoded_data;
        }
    }
}

$product_found_and_deleted = false;
$updated_products = [];

// Find and remove the product
foreach ($all_products as $product) {
    if ($product['id'] == $product_id_to_delete) {
        $product_found_and_deleted = true;
        // Skip adding this product to the new array
    } else {
        $updated_products[] = $product;
    }
}

if (!$product_found_and_deleted) {
    header("Location: admin_dashboard.php?page=products&error=product_not_found_for_deletion");
    exit();
}

// Save the updated array back to the file
$new_json_data = json_encode($updated_products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($products_file_path, $new_json_data)) {
    header("Location: admin_dashboard.php?page=products&status=success");
} else {
    header("Location: admin_dashboard.php?page=products&error=file_save_failed_on_delete");
}
exit();
?>
