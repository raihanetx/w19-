<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); 
header('Pragma: no-cache'); 
header('Expires: 0');
// error_reporting(0); // প্রোডাকশনের জন্য

$inputJSON = file_get_contents('php://input');
$requestData = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($requestData['order_ids']) || !is_array($requestData['order_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Expected an array of order_ids.']);
    exit;
}

$requestedOrderIds = array_filter(array_map('trim', $requestData['order_ids']));

if (empty($requestedOrderIds)) {
    echo json_encode(['success' => true, 'orders' => []]);
    exit;
}

$ordersFilePath = __DIR__ . '/orders.json';
$foundOrders = [];

if (file_exists($ordersFilePath)) {
    $jsonOrderData = file_get_contents($ordersFilePath);
    if ($jsonOrderData === false) {
        // error_log("fetch_user_orders_status.php: Could not read orders.json.");
    } elseif (!empty($jsonOrderData)) {
        $allSiteOrders = json_decode($jsonOrderData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($allSiteOrders)) {
            foreach ($allSiteOrders as $order) {
                if (isset($order['id']) && in_array($order['id'], $requestedOrderIds)) {
                    $orderInfo = [
                        'id' => $order['id'],
                        'status' => $order['status'] ?? 'Unknown',
                        'timestamp' => $order['timestamp'] ?? null,
                        'confirmed_at' => $order['confirmed_at'] ?? null,
                        'cancelled_at' => $order['cancelled_at'] ?? null,
                        'totalAmount' => $order['totalAmount'] ?? 0,
                        'paymentMethod' => $order['paymentMethod'] ?? 'N/A',
                        'transactionId' => $order['transactionId'] ?? 'N/A',
                        'customer' => ['name' => $order['customer']['name'] ?? 'N/A'],
                        'items' => []
                    ];
                    if(isset($order['items']) && is_array($order['items'])){
                        foreach($order['items'] as $item){
                            $orderInfo['items'][] = [
                                'name' => $item['name'] ?? 'Unknown Item',
                                'quantity' => $item['quantity'] ?? 1,
                                'price' => $item['price'] ?? 0,
                                'selectedDurationLabel' => $item['selectedDurationLabel'] ?? null
                            ];
                        }
                    }
                    $foundOrders[] = $orderInfo;
                }
            }
        } else {
            // error_log("fetch_user_orders_status.php: Error decoding orders.json. JSON Error: " . json_last_error_msg());
        }
    }
} else {
     // error_log("fetch_user_orders_status.php: orders.json file not found at " . $ordersFilePath);
}

echo json_encode(['success' => true, 'orders' => $foundOrders]);
?>