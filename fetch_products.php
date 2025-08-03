<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$filePath = __DIR__ . '/products.json';

if (file_exists($filePath)) {
    $jsonContent = file_get_contents($filePath);
    // Check if file is empty or content is invalid
    if ($jsonContent === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to read products file.']);
        exit;
    }
    // Just pass the raw content through, assuming it's valid JSON.
    // The client-side will parse it.
    echo $jsonContent;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Products data not found.']);
}
?>
