<?php
session_start();
// error_reporting(E_ALL); 
// ini_set('display_errors', 1); 

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id_to_delete'])) {
    $orderIdToDelete = trim($_POST['order_id_to_delete']);

    if (empty($orderIdToDelete)) {
        header("Location: admin_dashboard.php?error=Order_ID_missing_for_deletion");
        exit();
    }

    $ordersFilePath = __DIR__ . '/orders.json';
    $allOrders = [];
    $orderFoundAndMarked = false;

    if (file_exists($ordersFilePath)) {
        $jsonData = file_get_contents($ordersFilePath);
        if ($jsonData !== false) {
            $decodedData = json_decode($jsonData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                $allOrders = $decodedData;
            } else {
                header("Location: admin_dashboard.php?error=JSON_decode_failed_on_delete_attempt");
                exit();
            }
        } else {
             header("Location: admin_dashboard.php?error=Could_not_read_orders_file_on_delete_attempt");
             exit();
        }
    } else {
        header("Location: admin_dashboard.php?error=Orders_file_not_found_on_delete_attempt");
        exit();
    }

    foreach ($allOrders as $index => $order) {
        if (isset($order['id']) && $order['id'] === $orderIdToDelete) {
            $allOrders[$index]['is_deleted'] = true; 
            $allOrders[$index]['deleted_at'] = date('c'); 
            $orderFoundAndMarked = true;
            break;
        }
    }

    if ($orderFoundAndMarked) {
        if (file_put_contents($ordersFilePath, json_encode(array_values($allOrders), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX)) {
            header("Location: admin_dashboard.php?status_change=marked_as_deleted&orderid=" . urlencode($orderIdToDelete));
            exit();
        } else {
            header("Location: admin_dashboard.php?error=File_write_failed_on_marking_delete");
            exit();
        }
    } else {
        header("Location: admin_dashboard.php?error=Order_ID_not_found_for_marking_delete_" . urlencode($orderIdToDelete));
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>