<?php
session_start();

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php?page=categories&error=invalid_request");
    exit();
}

$categories_file_path = __DIR__ . '/categories.json';
$all_categories = [];

if (file_exists($categories_file_path)) {
    $json_data = file_get_contents($categories_file_path);
    if ($json_data) {
        $decoded_data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $all_categories = $decoded_data;
        }
    }
}

$category_data = [
    'id'   => trim($_POST['id'] ?? ''),
    'name' => trim($_POST['name'] ?? ''),
    'icon' => trim($_POST['icon'] ?? 'fas fa-tag')
];

// Basic validation
if (empty($category_data['id']) || empty($category_data['name'])) {
    header("Location: admin_dashboard.php?page=categories&error=missing_fields");
    exit();
}

$is_edit_mode = isset($_POST['original_id']) && !empty($_POST['original_id']);
$original_id = $_POST['original_id'] ?? $category_data['id'];
$id_changed = $is_edit_mode && ($original_id !== $category_data['id']);

// Check for duplicate ID if it's a new category or if the ID was changed
if (!$is_edit_mode || $id_changed) {
    foreach ($all_categories as $category) {
        if ($category['id'] === $category_data['id']) {
            header("Location: admin_dashboard.php?page=categories&error=duplicate_id");
            exit();
        }
    }
}

$category_found = false;
if ($is_edit_mode) {
    foreach ($all_categories as $index => $category) {
        if ($category['id'] === $original_id) {
            $all_categories[$index] = $category_data;
            $category_found = true;
            break;
        }
    }
    if (!$category_found) {
        header("Location: admin_dashboard.php?page=categories&error=category_not_found");
        exit();
    }
} else {
    // Add new category
    $all_categories[] = $category_data;
}

$new_json_data = json_encode($all_categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($categories_file_path, $new_json_data)) {
    header("Location: admin_dashboard.php?page=categories&status=success");
} else {
    header("Location: admin_dashboard.php?page=categories&error=file_save_failed");
}
exit();
?>
