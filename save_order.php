<?php
header('Content-Type: application/json');
error_reporting(E_ALL); // ডেবাগিং এর জন্য সব error দেখাবে, প্রোডাকশনে চাইলে error_reporting(0); করতে পারেন
ini_set('display_errors', 1); // ডেবাগিং এর জন্য error প্রদর্শন চালু করুন

$inputJSON = file_get_contents('php://input');
$orderDataFromClient = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($orderDataFromClient) || !isset($orderDataFromClient['id'])) {
    error_log("save_order.php: Invalid JSON or missing order ID. Received: " . $inputJSON);
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid or incomplete order data received by server.']);
    exit;
}

if (!isset($orderDataFromClient['transactionId']) || empty(trim($orderDataFromClient['transactionId']))) {
    error_log("save_order.php: Missing or empty transactionId. Order ID: " . $orderDataFromClient['id']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required and cannot be empty.']);
    exit;
}

if (!isset($orderDataFromClient['customer']['phone']) || empty(trim($orderDataFromClient['customer']['phone']))) {
    error_log("save_order.php: Missing or empty customer phone. Order ID: " . $orderDataFromClient['id']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment Phone Number is required and cannot be empty.']);
    exit;
}

$ordersFilePath = __DIR__ . '/orders.json'; // বর্তমান ডিরেক্টরির orders.json ফাইল
$allOrdersCurrentlyInFile = [];

if (file_exists($ordersFilePath)) {
    $existingJsonData = file_get_contents($ordersFilePath);
    if ($existingJsonData === false) {
        error_log("save_order.php: Could not read orders.json. Check file permissions or path.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Server error: Could not read existing order data.']);
        exit;
    }
    if (!empty($existingJsonData)) {
        $decodedData = json_decode($existingJsonData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
            $allOrdersCurrentlyInFile = $decodedData;
        } else {
            error_log("save_order.php: Error decoding existing orders.json. Content: " . $existingJsonData . " JSON Error: " . json_last_error_msg());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: Existing order data is corrupted. Please contact support.']);
            exit;
        }
    }
} else {
    // If file doesn't exist, it will be created by file_put_contents
    // No error here, $allOrdersCurrentlyInFile remains an empty array
}

$orderDataFromClient['status'] = $orderDataFromClient['status'] ?? 'Pending';
$orderDataFromClient['timestamp'] = $orderDataFromClient['timestamp'] ?? date('c'); // Ensure timestamp exists

$allOrdersCurrentlyInFile[] = $orderDataFromClient;

if (file_put_contents($ordersFilePath, json_encode($allOrdersCurrentlyInFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
    http_response_code(201); // Created
    echo json_encode(['success' => true, 'message' => 'Order saved successfully on server.', 'orderId' => $orderDataFromClient['id']]);
} else {
    error_log("save_order.php: Failed to write to orders.json. Check file permissions for: " . $ordersFilePath);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Could not save the order to file. Please check server logs or contact support.']);
}
?>