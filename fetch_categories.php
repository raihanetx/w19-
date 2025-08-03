<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$filePath = __DIR__ . '/categories.json';

if (file_exists($filePath)) {
    $jsonContent = file_get_contents($filePath);
    if ($jsonContent === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to read categories file.']);
        exit;
    }
    echo $jsonContent;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Categories data not found.']);
}
?>
