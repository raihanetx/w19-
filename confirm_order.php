<?php
session_start();
// error_reporting(E_ALL); // ডেবাগিং এর জন্য
// ini_set('display_errors', 1); // ডেবাগিং এর জন্য

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id_to_change']) && isset($_POST['new_status'])) {
    $orderIdToChange = trim($_POST['order_id_to_change']);
    $newStatus = trim($_POST['new_status']);

    if (empty($orderIdToChange)) {
        header("Location: admin_dashboard.php?error=Order_ID_missing_for_status_change");
        exit();
    }
    
    if (!in_array($newStatus, ['Confirmed', 'Cancelled'])) {
        header("Location: admin_dashboard.php?error=Invalid_status_update_attempted_" . urlencode($newStatus));
        exit();
    }

    $ordersFilePath = __DIR__ . '/orders.json';
    $allOrdersCurrentlyInFile = [];
    $orderFoundAndUpdated = false;

    if (file_exists($ordersFilePath)) {
        $existingJsonData = file_get_contents($ordersFilePath);
        if ($existingJsonData === false) {
             // error_log("confirm_order.php: Could not read orders.json for order ID " . $orderIdToChange);
             header("Location: admin_dashboard.php?error=Could_not_read_orders_file_on_confirm");
             exit();
        }
        if (!empty($existingJsonData)) {
            $decodedData = json_decode($existingJsonData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                $allOrdersCurrentlyInFile = $decodedData;
            } else {
                 // error_log("confirm_order.php: JSON_decode_failed_on_confirm. Order ID: " . $orderIdToChange . ". Error: " . json_last_error_msg());
                 header("Location: admin_dashboard.php?error=JSON_decode_failed_on_confirm");
                 exit();
            }
        }
    } else {
        // error_log("confirm_order.php: Orders_file_not_found_on_confirm. Order ID: " . $orderIdToChange);
        header("Location: admin_dashboard.php?error=Orders_file_not_found_on_confirm");
        exit();
    }

    foreach ($allOrdersCurrentlyInFile as $index => $order) {
        if (isset($order['id']) && $order['id'] === $orderIdToChange) {
            // Allow status change if current status is Pending
            if (strtolower($allOrdersCurrentlyInFile[$index]['status']) === 'pending') {
                $allOrdersCurrentlyInFile[$index]['status'] = $newStatus;
                if ($newStatus === 'Confirmed') {
                    $allOrdersCurrentlyInFile[$index]['confirmed_at'] = date('c');
                    if(isset($allOrdersCurrentlyInFile[$index]['cancelled_at'])) unset($allOrdersCurrentlyInFile[$index]['cancelled_at']);
                } elseif ($newStatus === 'Cancelled') {
                    $allOrdersCurrentlyInFile[$index]['cancelled_at'] = date('c');
                    if(isset($allOrdersCurrentlyInFile[$index]['confirmed_at'])) unset($allOrdersCurrentlyInFile[$index]['confirmed_at']);
                }
                $orderFoundAndUpdated = true;
                break;
            } else {
                // If order is not pending, redirect with an error (or handle as needed)
                $_SESSION['product_action_message'] = "Order (ID: " . htmlspecialchars($orderIdToChange) . ") is already processed and cannot be changed from current status: " . htmlspecialchars($allOrdersCurrentlyInFile[$index]['status']);
                header("Location: admin_dashboard.php?error=Order_already_processed_" . urlencode($orderIdToChange));
                exit();
            }
        }
    }

    if ($orderFoundAndUpdated) {
        if (file_put_contents($ordersFilePath, json_encode($allOrdersCurrentlyInFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            header("Location: admin_dashboard.php?status_change=success&orderid=" . urlencode($orderIdToChange) . "&new_status=" . urlencode($newStatus));
            exit();
        } else {
            // error_log("confirm_order.php: File_write_failed_on_confirm. Order ID: " . $orderIdToChange);
            header("Location: admin_dashboard.php?error=File_write_failed_on_confirm");
            exit();
        }
    } else {
        // error_log("confirm_order.php: Order_ID_not_found_for_confirm. Order ID: " . $orderIdToChange);
        header("Location: admin_dashboard.php?error=Order_ID_not_found_for_confirm_" . urlencode($orderIdToChange));
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>