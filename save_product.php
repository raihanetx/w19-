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

// Handle stock status
$stock_status = isset($_POST['stock_status']) ? 'In Stock' : 'Out of Stock';

// Handle durations
$durations = [];
if (isset($_POST['duration_labels']) && isset($_POST['duration_prices'])) {
    $labels = $_POST['duration_labels'];
    $prices = $_POST['duration_prices'];
    for ($i = 0; $i < count($labels); $i++) {
        if (!empty($labels[$i]) && !empty($prices[$i])) {
            $durations[] = [
                'label' => trim($labels[$i]),
                'price' => floatval($prices[$i])
            ];
        }
    }
}


// Get data from form
$product_data = [
    'id'                => $_POST['id'] ?? null,
    'name'              => trim($_POST['name'] ?? ''),
    'description'       => trim($_POST['description'] ?? ''),
    'longDescription'   => trim($_POST['longDescription'] ?? ''),
    'category'          => trim($_POST['category'] ?? 'uncategorized'),
    'price'             => floatval($_POST['price'] ?? 0),
    'image'             => trim($_POST['image'] ?? ''),
    'stock'             => $stock_status,
    'isFeatured'        => isset($_POST['isFeatured']),
    'durations'         => $durations
];


$is_edit_mode = !empty($product_data['id']);

if ($is_edit_mode) {
    // Update existing product
    $product_found = false;
    foreach ($all_products as $index => $product) {
        if ($product['id'] == $product_data['id']) {
            // Preserve existing reviews
            $product_data['reviews'] = $product['reviews'] ?? [];
            $all_products[$index] = $product_data;
            $product_found = true;
            break;
        }
    }
    if (!$product_found) {
        header("Location: admin_dashboard.php?page=products&error=product_not_found");
        exit();
    }
} else {
    // Add new product
    // Generate a new unique ID
    $new_id = time(); // Simple unique ID
    $product_data['id'] = $new_id;
    $product_data['reviews'] = []; // New products have no reviews
    $all_products[] = $product_data;
}

// Save the updated array back to the file
$new_json_data = json_encode($all_products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($products_file_path, $new_json_data)) {
    header("Location: admin_dashboard.php?page=products&status=success");
} else {
    header("Location: admin_dashboard.php?page=products&error=file_save_failed");
}
exit();
?>
