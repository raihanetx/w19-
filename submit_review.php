<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html?error=invalid_request");
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

$product_id = $_POST['product_id'] ?? '';
$customer_name = $_POST['customer_name'] ?? 'Anonymous';
$review_text = $_POST['review_text'] ?? '';
$rating = $_POST['rating'] ?? 0;

if (empty($product_id) || empty($review_text) || empty($rating)) {
    header("Location: index.html?error=missing_fields");
    exit();
}

$new_review = [
    'id' => uniqid(),
    'product_id' => $product_id,
    'customer_name' => $customer_name,
    'review_text' => $review_text,
    'rating' => $rating,
    'status' => 'pending',
    'is_featured' => false,
    'created_at' => date('Y-m-d H:i:s')
];

$reviews[] = $new_review;

$json_data = json_encode($reviews, JSON_PRETTY_PRINT);
if (file_put_contents($reviews_file_path, $json_data) === false) {
    header("Location: index.html?error=file_save_error");
    exit();
}

header("Location: index.html?status=review_submitted");
exit();
?>
