<?php
header('Content-Type: application/json');

$reviews_file_path = __DIR__ . '/reviews.json';
$reviews = [];

if (file_exists($reviews_file_path)) {
    $json_data = file_get_contents($reviews_file_path);
    if ($json_data) {
        $reviews = json_decode($json_data, true);
    }
}

$product_id = $_GET['product_id'] ?? null;

if ($product_id) {
    $product_reviews = array_filter($reviews, function($review) use ($product_id) {
        return $review['product_id'] == $product_id;
    });
    echo json_encode(array_values($product_reviews));
} else {
    echo json_encode($reviews);
}
?>
