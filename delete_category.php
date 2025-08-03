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

if (!isset($_POST['category_id_to_delete']) || empty($_POST['category_id_to_delete'])) {
    header("Location: admin_dashboard.php?page=categories&error=no_category_id");
    exit();
}

$category_id_to_delete = $_POST['category_id_to_delete'];
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

$category_found_and_deleted = false;
$updated_categories = [];

foreach ($all_categories as $category) {
    if ($category['id'] == $category_id_to_delete) {
        $category_found_and_deleted = true;
    } else {
        $updated_categories[] = $category;
    }
}

if (!$category_found_and_deleted) {
    header("Location: admin_dashboard.php?page=categories&error=category_not_found_for_deletion");
    exit();
}

$new_json_data = json_encode(array_values($updated_categories), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($categories_file_path, $new_json_data)) {
    header("Location: admin_dashboard.php?page=categories&status=delete_success");
} else {
    header("Location: admin_dashboard.php?page=categories&error=file_save_failed_on_delete");
}
exit();
?>
