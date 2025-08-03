<?php
session_start();

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$reviews_file_path = __DIR__ . '/reviews.json';
$reviews = [];
if (file_exists($reviews_file_path)) {
    $json_data = file_get_contents($reviews_file_path);
    if ($json_data) {
        $reviews = json_decode($json_data, true);
    }
}

$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;
$featured = $_GET['featured'] ?? null;
$review_text = $_POST['review_text'] ?? null;

if (empty($id)) {
    header("Location: admin_dashboard.php?page=reviews&error=missing_id");
    exit();
}

$review_found = false;
foreach ($reviews as &$review) {
    if ($review['id'] == $id) {
        if ($status) {
            $review['status'] = $status;
        }
        if (isset($featured)) {
            $review['is_featured'] = (bool)$featured;
        }
        if ($review_text) {
            $review['review_text'] = $review_text;
        }
        $review_found = true;
        break;
    }
}

if (!$review_found) {
    header("Location: admin_dashboard.php?page=reviews&error=review_not_found");
    exit();
}

$json_data = json_encode($reviews, JSON_PRETTY_PRINT);
if (file_put_contents($reviews_file_path, $json_data) === false) {
    header("Location: admin_dashboard.php?page=reviews&error=file_save_error");
    exit();
}

header("Location: admin_dashboard.php?page=reviews&status=success");
exit();
