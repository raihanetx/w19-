<?php
session_start();
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$correct_admin_password = "YOUR_VERY_STRONG_AND_UNIQUE_PASSWORD_HERE"; // <<<<<<<<<<<<< এই পাসওয়ার্ডটা পরিবর্তন করুন!

if (isset($_POST['password'])) {
    if ($_POST['password'] === $correct_admin_password) {
        $_SESSION['admin_logged_in_thinkplusbd'] = true;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: admin_login.php?error=1");
        exit();
    }
}

if (!isset($_SESSION['admin_logged_in_thinkplusbd']) || $_SESSION['admin_logged_in_thinkplusbd'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

function getStatsForPeriod($orders, $startDate, $endDate) {
    $stats = [
        'total_orders' => 0,
        'confirmed_orders' => 0,
        'cancelled_orders' => 0,
        'pending_orders_in_period' => 0,
        'total_revenue' => 0.0
    ];
    if (!is_array($orders)) $orders = [];
    foreach ($orders as $order) {
        $orderTimestamp = isset($order['timestamp']) ? strtotime($order['timestamp']) : 0;
        $orderStatus = strtolower($order['status'] ?? 'unknown');
        $orderTotalAmount = floatval($order['totalAmount'] ?? 0);
        
        if ($orderTimestamp >= $startDate && $orderTimestamp <= $endDate) {
            $stats['total_orders']++;
            if ($orderStatus === 'confirmed') {
                $stats['confirmed_orders']++;
                if (!isset($order['is_deleted']) || $order['is_deleted'] !== true || (isset($order['confirmed_at']) && (!isset($order['deleted_at']) || strtotime($order['deleted_at']) > strtotime($order['confirmed_at'])) ) ) {
                    $stats['total_revenue'] += $orderTotalAmount;
                }
            } elseif ($orderStatus === 'cancelled') {
                $stats['cancelled_orders']++;
            } elseif ($orderStatus === 'pending') {
                $stats['pending_orders_in_period']++;
            }
        }
    }
    return $stats;
}

function getCurrentTotalPendingOrders($orders) {
    $count = 0;
    if (!is_array($orders)) $orders = [];
    foreach ($orders as $order) {
        if (strtolower($order['status'] ?? 'unknown') === 'pending' && (!isset($order['is_deleted']) || $order['is_deleted'] !== true)) {
            $count++;
        }
    }
    return $count;
}

$orders_file_path = __DIR__ . '/orders.json';
$all_site_orders_for_stats = []; 
$orders_for_display = [];      
$json_load_error = null;

if (file_exists($orders_file_path)) {
    $json_order_data = file_get_contents($orders_file_path);
    if ($json_order_data === false) {
        $json_load_error = "Could not read orders.json file.";
    } elseif (!empty($json_order_data)) {
        $decoded_orders = json_decode($json_order_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_orders)) {
            $all_site_orders_for_stats = $decoded_orders; 
            foreach ($all_site_orders_for_stats as $order) {
                if (!isset($order['is_deleted']) || $order['is_deleted'] !== true) {
                    $orders_for_display[] = $order;
                }
            }
            usort($orders_for_display, function($a, $b) { 
                $timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
                $timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
                return $timeB - $timeA;
            });
        } else {
            $json_load_error = "Critical Error: Could not decode orders.json. Error: " . json_last_error_msg();
        }
    }
}

date_default_timezone_set('Asia/Dhaka'); 
$today_start = strtotime('today midnight');
$today_end = strtotime('tomorrow midnight') - 1;
$week_start = strtotime('-6 days midnight', $today_start);
$month_start = strtotime('-29 days midnight', $today_start);
$ninety_days_start = strtotime('-89 days midnight', $today_start);
$year_start = strtotime('-364 days midnight', $today_start);

$stats_today = getStatsForPeriod($all_site_orders_for_stats, $today_start, $today_end);
$stats_week = getStatsForPeriod($all_site_orders_for_stats, $week_start, $today_end);
$stats_month = getStatsForPeriod($all_site_orders_for_stats, $month_start, $today_end);
$stats_90_days = getStatsForPeriod($all_site_orders_for_stats, $ninety_days_start, $today_end);
$stats_year = getStatsForPeriod($all_site_orders_for_stats, $year_start, $today_end);
$current_total_pending_all_time = getCurrentTotalPendingOrders($all_site_orders_for_stats);

// ---------- PAGE ROUTING LOGIC ----------
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
// ----------------------------------------

// ---------- LOAD PRODUCTS (if on products page) ----------
$products = [];
$products_load_error = null;
if ($page === 'products' || $page === 'edit_product') {
    $products_file_path = __DIR__ . '/products.json';
    if (file_exists($products_file_path)) {
        $json_product_data = file_get_contents($products_file_path);
        if ($json_product_data === false) {
            $products_load_error = "Could not read products.json file.";
        } else {
            $decoded_products = json_decode($json_product_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_products)) {
                $products = $decoded_products;
            } else {
                $products_load_error = "Critical Error: Could not decode products.json. Error: " . json_last_error_msg();
            }
        }
    } else {
        $products_load_error = "products.json file not found.";
    }
}

// ---------- LOAD CATEGORIES ----------
$categories = [];
$categories_load_error = null;
$categories_file_path = __DIR__ . '/categories.json';
if (file_exists($categories_file_path)) {
    $json_category_data = file_get_contents($categories_file_path);
    if ($json_category_data === false) {
        $categories_load_error = "Could not read categories.json file.";
    } else {
        $decoded_categories = json_decode($json_category_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_categories)) {
            $categories = $decoded_categories;
        } else {
            $categories_load_error = "Critical Error: Could not decode categories.json. Error: " . json_last_error_msg();
        }
    }
} else {
    $categories_load_error = "categories.json file not found.";
}
// ---------------------------------------------------------

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - THINK PLUS BD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #8F87F1; 
            --primary-color-rgb: 143, 135, 241; /* For rgba focus */
            --primary-color-darker: #756dcf;
            --text-color: #343f52; /* Slightly softer black */
            --text-muted: #778398;
            --background-color: #f5f7fa; /* Lighter, cleaner background */
            --card-bg-color: #ffffff;
            --border-color: #e3e8ee;
            --sidebar-bg: #ffffff; /* White sidebar */
            --sidebar-text: #525f7f; /* Darker text for white sidebar */
            --sidebar-icon-color: #8898aa;
            --sidebar-hover-bg: #f5f7fa;
            --sidebar-hover-text: var(--primary-color);
            --sidebar-active-bg: rgba(var(--primary-color-rgb), 0.1); /* Light primary for active */
            --sidebar-active-text: var(--primary-color);
            --sidebar-active-icon-color: var(--primary-color);
            --font-family-sans-serif: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            --box-shadow: 0 0 30px 0 rgba(82,63,105,0.05); /* Softer shadow */
            --border-radius: 6px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.65;
            font-size: 14px; /* Base font size for a more compact UI */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        /* --- START: UPDATED SIDEBAR STYLES --- */
        .admin-sidebar {
            background-color: var(--sidebar-bg);
            width: 250px;
            padding: 1.75rem 1.25rem;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            box-shadow: var(--box-shadow);
            transform: translateX(-100%); /* Hidden by default on all screens */
        }
        .admin-sidebar.open {
            transform: translateX(0); /* Becomes visible when .open class is added */
        }
        /* --- END: UPDATED SIDEBAR STYLES --- */

        .admin-sidebar .logo-admin { text-align: center; margin-bottom: 2.5rem; }
        .admin-sidebar .logo-admin img { max-height: 45px; }
        
        .admin-sidebar .admin-nav ul { list-style: none; }
        .admin-sidebar .admin-nav li a {
            color: var(--sidebar-text);
            text-decoration: none;
            display: flex; 
            align-items: center;
            padding: 0.8rem 1rem; 
            border-radius: var(--border-radius); 
            margin-bottom: 0.3rem;
            transition: background-color 0.2s ease, color 0.2s ease, fill 0.2s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .admin-sidebar .admin-nav li a:hover {
            background-color: var(--sidebar-hover-bg);
            color: var(--sidebar-hover-text);
        }
        .admin-sidebar .admin-nav li a:hover i {
            color: var(--sidebar-hover-text);
        }
        .admin-sidebar .admin-nav li a.active {
            background-color: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 600;
        }
        .admin-sidebar .admin-nav li a.active i {
            color: var(--sidebar-active-icon-color);
        }
        .admin-sidebar .admin-nav li a i {
            margin-right: 0.85rem; 
            width: 18px; 
            text-align: center;
            font-size: 1rem; 
            color: var(--sidebar-icon-color);
            transition: color 0.2s ease;
        }

        /* --- START: UPDATED MAIN CONTENT STYLES --- */
        .admin-main-content {
            margin-left: 0; /* No margin by default */
            width: 100%; /* Full width by default */
            padding: 0;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* --- END: UPDATED MAIN CONTENT STYLES --- */
        
        .admin-topbar {
            background-color: var(--card-bg-color);
            padding: 0.85rem 2rem; 
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* --- START: UPDATED SIDEBAR TOGGLE STYLES --- */
        .admin-topbar .sidebar-toggle { 
            font-size: 1.4rem; 
            cursor: pointer; 
            color: var(--text-muted); 
            margin-right: 1.5rem; 
            display: inline-block; /* Always visible */
            transition: color 0.2s ease;
        }
        /* --- END: UPDATED SIDEBAR TOGGLE STYLES --- */

        .admin-topbar .sidebar-toggle:hover { color: var(--primary-color); }
        .admin-topbar h1 { 
            font-size: 1.3rem; /* Smaller title */
            color: var(--text-color); 
            margin: 0; 
            font-weight: 600; 
        }
        .admin-topbar .logout-btn {
            background-color: transparent;
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            border: 1px solid var(--primary-color);
            transition: background-color 0.2s ease, color 0.2s ease;
            font-size: 0.85rem;
        }
        .admin-topbar .logout-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .admin-topbar .logout-btn i { margin-right: 5px; }

        .admin-page-content { padding: 2rem; } /* Consistent padding */
        
        .content-card {
            background-color: var(--card-bg-color);
            border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow); 
            border: 1px solid var(--border-color);
            padding: 1.75rem;
            margin-bottom: 2rem;
        }
        .content-card h2.card-title {
            font-size: 1.15rem; /* Smaller card title */
            color: var(--text-color);
            margin-top: 0;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        .orders-table-container { overflow-x: auto; }
        table.orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            border: 1px solid var(--border-color); /* Table border */
            border-radius: var(--border-radius);
            overflow: hidden; /* For border-radius on table */
        }
        .orders-table th, .orders-table td {
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 1rem; 
            text-align: left;
            vertical-align: middle; 
        }
        .orders-table th {
            background-color: #f8f9fa; /* Lighter gray for header */
            font-weight: 500; /* Lighter header weight */
            color: var(--text-muted); 
            white-space: nowrap;
            text-transform: none; /* No uppercase */
            font-size: 0.85rem; 
            letter-spacing: 0;
        }
        .orders-table tr:last-child td { border-bottom: none; }
        .orders-table td { color: var(--text-color); }
        .orders-table tr:hover td { background-color: #fcfdff; } 
        
        .orders-table td[data-label='Customer Info'] strong { font-weight: 500; display: block; margin-bottom: 2px;}
        .orders-table td[data-label='Customer Info'] small { color: var(--text-muted); font-size: 0.9em;}

        .order-items-list-admin { list-style: none; padding: 0; margin: 0; }
        .order-items-list-admin li { margin-bottom: 3px; font-size: 0.9em; color: var(--text-muted); }
        .order-items-list-admin li .item-price { color: var(--text-muted); }
        
        .status-badge {
            padding: 4px 10px; 
            border-radius: 1rem; /* Pill shape */
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 75px;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .status-pending { background-color: #fff0c2; color: #855d0b; border-color: #ffe58f; }
        .status-confirmed { background-color: #d1f2eb; color: #0b5742; border-color: #a3e9dd;}
        .status-cancelled { background-color: #fde2e4; color: #8c1c13; border-color: #f5c2c7;}
        .status-unknown { background-color: #e9ecef; color: #495057; border-color: #ced4da;}
        
        .action-buttons-group { display: flex; flex-direction: row; gap: 0.5rem; align-items: center; flex-wrap: wrap;}
        .action-btn {
            padding: 0.4rem 0.8rem; 
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius); 
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.2s ease;
            background-color: var(--card-bg-color);
            color: var(--text-muted) !important;
            font-weight: 500;
            line-height: 1.2;
        }
        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color) !important;
            background-color: rgba(var(--primary-color-rgb), 0.05);
        }
        .action-btn-confirm:hover { background-color: #d1e7dd; border-color: #198754; color: #0f5132 !important; }
        .action-btn-cancel:hover { background-color: #f8d7da; border-color: #dc3545; color: #58151c !important; }
        .action-btn-delete:hover { background-color: #e9ecef; border-color: #adb5bd; color: #495057 !important; }
        
        .action-btn-text { color: var(--text-color); font-weight: 500; font-size: 0.9rem; }
        .action-btn-text.confirmed { color: #198754; }
        .action-btn-text.cancelled { color: #dc3545; }
        .action-btn-text small { font-size: 0.8em; color: var(--text-muted); display: block; line-height: 1.2; }

        .alert-message {
            padding: 0.85rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 400; /* Normal weight */
            border: 1px solid transparent;
            font-size: 0.9rem;
        }
        .alert-success { background-color: #e6fffa; color: #00684a; border-color: #bcf0e4;}
        .alert-danger { background-color: #fff0f1; color: #a01326; border-color: #ffd9dd;}
        
        .stats-period-selector {
            margin-bottom: 1.5rem;
            display: flex; 
            align-items: center;
            flex-wrap: wrap; 
            gap: 0.75rem; /* Reduced gap */
        }
        .stats-period-selector label { font-weight: 500; margin-right: 0.25rem; font-size:0.9rem; color: var(--text-muted); }
        .stats-period-selector select {
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
            background-color: white;
            min-width: 130px;
        }
        .stats-period-selector select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb),.2);
            outline: 0;
        }
        .stats-period-selector p { margin: 0; margin-left:auto; font-size:0.9rem; color: var(--text-muted); }
        .stats-period-selector p strong { font-weight: 600; color: var(--text-color); }
        
        #stats-display-area {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 1.25rem; 
        }
        .stat-card {
            background-color: var(--card-bg-color);
            padding: 1.25rem; 
            border-radius: var(--border-radius);
            text-align: left;
            border: 1px solid var(--border-color);
            box-shadow: none; /* Flatter cards */
            transition: border-color 0.2s ease;
        }
        .stat-card:hover { border-color: var(--primary-color); }
        .stat-card h4 {
            margin-top: 0;
            margin-bottom: 0.25rem; /* Less space */
            font-size: 0.8rem; 
            color: var(--text-muted); 
            text-transform: none; /* No uppercase */
            letter-spacing: 0;
            font-weight: 400; /* Lighter weight */
        }
        .stat-card p {
            font-size: 1.75rem; 
            font-weight: 600; 
            color: var(--text-color);
            margin: 0;
            line-height: 1.1;
        }
        .stat-card#stat_total_revenue_card p {
            color: var(--primary-color); 
        }
        .no-orders-message {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* --- START: REMOVED OLD MEDIA QUERY FOR LAYOUT --- */
        @media (max-width: 991.98px) {
            /* Old layout rules are removed. Now only styling adjustments remain */
        }
        /* --- END: REMOVED OLD MEDIA QUERY --- */

        @media (max-width: 767.98px) {
            body { font-size: 13.5px; }
            .admin-topbar { padding: 0.75rem 1rem; }
            .admin-topbar h1 { font-size: 1.15rem; }
            .admin-page-content { padding: 1.25rem 1rem; }
            .content-card { padding: 1.25rem; }
            .content-card h2.card-title { font-size: 1.1rem; }
            #stats-display-area { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
            .stat-card p { font-size: 1.5rem; }
            .orders-table th, .orders-table td { padding: 0.75rem; font-size: 0.825rem;}
            .action-btn { font-size:0.75rem; padding: 0.35rem 0.65rem; }
            .action-buttons-group { flex-direction: column; align-items: stretch; width: 100%; }
            .action-buttons-group form, .action-buttons-group .action-btn { width: 100%; }
            .action-buttons-group .action-btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="logo-admin">
                <img src="https://i.postimg.cc/4NtztqPt/IMG-20250603-130207-removebg-preview-1.png" alt="THINK PLUS BD Logo">
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="admin_dashboard.php?page=dashboard" class="<?php echo ($page === 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a></li>
                    <li><a href="admin_dashboard.php?page=products" class="<?php echo ($page === 'products') ? 'active' : ''; ?>"><i class="fas fa-box"></i> <span>Manage Products</span></a></li>
                    <li><a href="admin_dashboard.php?page=categories" class="<?php echo ($page === 'categories') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> <span>Manage Categories</span></a></li>
                    <li><a href="admin_dashboard.php?logout=1"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>
        <main class="admin-main-content" id="adminMainContent">
            <header class="admin-topbar">
                <div style="display:flex; align-items:center;">
                    <i class="fas fa-bars sidebar-toggle" id="sidebarToggle"></i>
                    <h1>Admin Panel</h1>
                </div>
                <a href="admin_dashboard.php?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </header>

            <div class="admin-page-content">

                <?php if ($page === 'dashboard'): ?>
                <div class="content-card">
                    <h2 class="card-title">Performance Overview</h2>
                    <div class="stats-period-selector">
                        <label for="period_selector">Showing stats for:</label>
                        <select id="period_selector" onchange="updateStatsDisplay(this.value)">
                            <option value="today" selected>Today</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="ninetydays">Last 90 Days</option>
                            <option value="year">Last 365 Days</option>
                        </select>
                        <p>
                            <strong>Pending (All Time):</strong> 
                            <span id="currentTotalPendingAllTime"><?php echo $current_total_pending_all_time; ?></span>
                        </p>
                    </div>
                    <div id="stats-display-area">
                        <div class="stat-card"><h4>Total Orders</h4><p id="stat_total_orders">0</p></div>
                        <div class="stat-card"><h4>Confirmed</h4><p id="stat_confirmed_orders">0</p></div>
                        <div class="stat-card"><h4>Cancelled</h4><p id="stat_cancelled_orders">0</p></div>
                        <div class="stat-card"><h4>Pending (Period)</h4><p id="stat_pending_orders_in_period">0</p></div>
                        <div class="stat-card" id="stat_total_revenue_card"><h4>Total Revenue</h4><p id="stat_total_revenue">৳0.00</p></div>
                    </div>
                </div>
                <div class="content-card">
                    <h2 class="card-title">Manage Orders</h2>
                    <?php if ($json_load_error): ?>
                        <div class="alert-message alert-danger"><?php echo htmlspecialchars($json_load_error); ?></div>
                    <?php endif; ?>
                    <?php
                        if (isset($_GET['status_change'])) {
                            $changed_order_id = isset($_GET['orderid']) ? htmlspecialchars($_GET['orderid']) : '';
                            if ($_GET['status_change'] == 'success') {
                                $new_status = isset($_GET['new_status']) ? htmlspecialchars($_GET['new_status']) : 'updated';
                                echo '<div class="alert-message alert-success">Order ' . $changed_order_id . ' successfully marked as ' . $new_status . '!</div>';
                            } elseif ($_GET['status_change'] == 'marked_as_deleted') {
                                echo '<div class="alert-message alert-success">Order ' . $changed_order_id . ' successfully hidden from active list.</div>';
                            }
                        }
                        if (isset($_GET['error'])) {
                             echo '<div class="alert-message alert-danger">Error: ' . htmlspecialchars(str_replace('_', ' ', $_GET['error'])) . '</div>';
                        }
                    ?>
                    <div class="orders-table-container">
                        <?php if (empty($orders_for_display) && !$json_load_error): ?>
                            <p class='no-orders-message'>No active orders to display.</p>
                        <?php elseif (!empty($orders_for_display)): ?>
                            <table class='orders-table'>
                            <thead><tr><th>Order ID</th><th>Date</th><th>Customer</th><th>Contact</th><th>TrxID</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($orders_for_display as $single_order): ?>
                                <tr>
                                <td data-label='Order ID' style="font-weight:500;"><?php echo htmlspecialchars($single_order['id']); ?></td>
                                <td data-label='Date'><?php echo htmlspecialchars(date('d M Y, H:i', (isset($single_order['timestamp']) ? strtotime($single_order['timestamp']) : time()))); ?></td>
                                <td data-label='Customer'><strong><?php echo htmlspecialchars($single_order['customer']['name'] ?? 'N/A'); ?></strong><small><?php echo htmlspecialchars($single_order['customer']['email'] ?? 'N/A'); ?></small></td>
                                <td data-label='Contact'><?php echo htmlspecialchars($single_order['customer']['phone'] ?? 'N/A'); ?></td>
                                <td data-label='TrxID'><?php echo htmlspecialchars($single_order['transactionId'] ?? 'N/A'); ?></td>
                                <td data-label='Items'><ul class='order-items-list-admin'>
                                <?php if (isset($single_order['items']) && is_array($single_order['items'])): foreach ($single_order['items'] as $item):
                                    $item_name = htmlspecialchars($item['name'] ?? 'Unknown');
                                    $item_quantity = htmlspecialchars($item['quantity'] ?? 1);
                                    $item_price = htmlspecialchars(number_format(floatval($item['price'] ?? 0), 0));
                                    $item_duration = isset($item['selectedDurationLabel']) && !empty($item['selectedDurationLabel']) ? ' (' . htmlspecialchars($item['selectedDurationLabel']) . ')' : '';
                                ?>
                                    <li><?php echo $item_name . $item_duration; ?> (x<?php echo $item_quantity; ?>)</li>
                                <?php endforeach; endif; ?>
                                </ul></td>
                                <td data-label='Total' style="font-weight:600; color:var(--text-color);">৳<?php echo htmlspecialchars(number_format(floatval($single_order['totalAmount'] ?? 0), 0)); ?></td>
                                <td data-label='Payment'><?php echo htmlspecialchars(ucfirst($single_order['paymentMethod'] ?? 'N/A')); ?></td>
                                <?php
                                    $order_status_val = strtolower($single_order['status'] ?? 'unknown');
                                    $status_class_name = 'status-' . str_replace(' ', '-', $order_status_val);
                                    if (!in_array($status_class_name, ['status-pending', 'status-confirmed', 'status-cancelled'])) {
                                        $status_class_name = 'status-unknown';
                                    }
                                ?>
                                <td data-label='Status'><span class='status-badge <?php echo $status_class_name; ?>'><?php echo htmlspecialchars($order_status_val); ?></span></td>
                                <td data-label='Actions'>
                                <div class="action-buttons-group">
                                <?php if ($order_status_val === 'pending'): ?>
                                    <form method='POST' action='confirm_order.php' style='display:inline;'><input type='hidden' name='order_id_to_change' value='<?php echo htmlspecialchars($single_order['id']); ?>'><input type='hidden' name='new_status' value='Confirmed'><button type='submit' class='action-btn action-btn-confirm'>Confirm</button></form>
                                    <form method='POST' action='confirm_order.php' style='display:inline;'><input type='hidden' name='order_id_to_change' value='<?php echo htmlspecialchars($single_order['id']); ?>'><input type='hidden' name='new_status' value='Cancelled'><button type='submit' class='action-btn action-btn-cancel'>Cancel</button></form>
                                <?php elseif ($order_status_val === 'confirmed'): ?>
                                    <span class='action-btn-text confirmed'>Confirmed <small><?php if(isset($single_order['confirmed_at'])) echo htmlspecialchars(date('d M, H:i', strtotime($single_order['confirmed_at']))); ?></small></span>
                                <?php elseif ($order_status_val === 'cancelled'): ?>
                                    <span class='action-btn-text cancelled'>Cancelled <small><?php if(isset($single_order['cancelled_at'])) echo htmlspecialchars(date('d M, H:i', strtotime($single_order['cancelled_at']))); ?></small></span>
                                <?php endif; ?>
                                <form method='POST' action='delete_order.php' style='display:inline;' onsubmit="return confirm('Are you sure you want to hide Order ID: <?php echo htmlspecialchars($single_order['id']); ?>?');"><input type='hidden' name='order_id_to_delete' value='<?php echo htmlspecialchars($single_order['id']); ?>'><button type='submit' class='action-btn action-btn-delete' title='Hide this order from the active list'>Hide</button></form>
                                </div>
                                </td></tr>
                            <?php endforeach; ?>
                            </tbody></table>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($page === 'products'): ?>
                <div class="content-card">
                    <h2 class="card-title">Manage Products</h2>
                    <?php if ($products_load_error): ?>
                        <div class="alert-message alert-danger"><?php echo htmlspecialchars($products_load_error); ?></div>
                    <?php else: ?>
                        <div style="margin-bottom: 1.5rem; text-align: right;">
                            <a href="admin_dashboard.php?page=edit_product" class="action-btn" style="background-color: var(--primary-color); color: white !important; border-color: var(--primary-color);">
                                <i class="fas fa-plus"></i> Add New Product
                            </a>
                        </div>
                        <div class="orders-table-container">
                            <?php if (empty($products)): ?>
                                <p class='no-orders-message'>No products found.</p>
                            <?php else: ?>
                                <table class='orders-table'>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td data-label='ID' style="font-weight:500;"><?php echo htmlspecialchars($product['id']); ?></td>
                                                <td data-label='Name'><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td data-label='Category'><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                                <td data-label='Price'>
                                                    <?php
                                                        if (isset($product['durations']) && !empty($product['durations'])) {
                                                            echo "Multiple";
                                                        } else {
                                                            echo '৳' . htmlspecialchars(number_format(floatval($product['price'] ?? 0), 2));
                                                        }
                                                    ?>
                                                </td>
                                                <td data-label='Stock'><?php echo htmlspecialchars($product['stock'] ?? 'N/A'); ?></td>
                                                <td data-label='Actions'>
                                                    <div class="action-buttons-group">
                                                        <a href="admin_dashboard.php?page=edit_product&id=<?php echo htmlspecialchars($product['id']); ?>" class="action-btn">Edit</a>
                                                        <form method="POST" action="delete_product.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                            <input type="hidden" name="product_id_to_delete" value="<?php echo htmlspecialchars($product['id']); ?>">
                                                            <button type="submit" class="action-btn action-btn-cancel">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($page === 'edit_product'):
                    $is_edit_mode = isset($_GET['id']);
                    $product_to_edit = null;
                    $form_error = null;

                    if ($is_edit_mode) {
                        $product_id = $_GET['id'];
                        $all_products_raw = file_get_contents(__DIR__ . '/products.json');
                        $all_products = json_decode($all_products_raw, true);
                        if ($all_products) {
                            foreach ($all_products as $p) {
                                if ($p['id'] == $product_id) {
                                    $product_to_edit = $p;
                                    break;
                                }
                            }
                        }
                        if (!$product_to_edit) {
                            $form_error = "Product with ID " . htmlspecialchars($product_id) . " not found.";
                        }
                    } else {
                        // Default values for a new product
                        $product_to_edit = [
                            'id' => '', 'name' => '', 'description' => '', 'longDescription' => '', 'category' => '',
                            'price' => 0, 'image' => '', 'stock' => 0, 'isFeatured' => false, 'durations' => []
                        ];
                    }
                ?>
                <div class="content-card">
                    <h2 class="card-title"><?php echo $is_edit_mode ? 'Edit Product' : 'Add New Product'; ?></h2>
                    <?php if ($form_error): ?>
                        <div class="alert-message alert-danger"><?php echo $form_error; ?></div>
                    <?php else: ?>
                    <form action="save_product.php" method="POST" class="product-form">
                        <?php if ($is_edit_mode): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_to_edit['id']); ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_to_edit['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Short Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($product_to_edit['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="longDescription">Long Description</label>
                            <textarea id="longDescription" name="longDescription" rows="6"><?php echo htmlspecialchars($product_to_edit['longDescription']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($product_to_edit['category'] === $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price">Base Price</label>
                                <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($product_to_edit['price']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="image">Image URL</label>
                                <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($product_to_edit['image']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock Quantity</label>
                                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product_to_edit['stock']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="durations">Durations (JSON format)</label>
                            <textarea id="durations" name="durations" rows="4" placeholder='[{"label": "1 Month", "price": 100}, {"label": "1 Year", "price": 1000}]'><?php echo htmlspecialchars(json_encode($product_to_edit['durations'], JSON_PRETTY_PRINT)); ?></textarea>
                            <small>Enter as a valid JSON array of objects, or leave empty if not applicable.</small>
                        </div>

                        <div class="form-group">
                            <label for="isFeatured">
                                <input type="checkbox" id="isFeatured" name="isFeatured" value="true" <?php echo ($product_to_edit['isFeatured'] ?? false) ? 'checked' : ''; ?>>
                                Is this a featured product?
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="action-btn" style="background-color: var(--primary-color); color: white !important;">Save Product</button>
                            <a href="admin_dashboard.php?page=products" class="action-btn">Cancel</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                <style>
                    .product-form .form-group { margin-bottom: 1rem; }
                    .product-form label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
                    .product-form input[type="text"],
                    .product-form input[type="number"],
                    .product-form textarea {
                        width: 100%;
                        padding: 0.75rem;
                        border: 1px solid var(--border-color);
                        border-radius: var(--border-radius);
                        font-size: 0.9rem;
                    }
                    .product-form textarea { min-height: 80px; font-family: inherit; }
                    .product-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
                    .product-form .form-actions { margin-top: 1.5rem; text-align: right; }
                </style>

                <?php elseif ($page === 'categories'):
                    $category_to_edit = null;
                    if (isset($_GET['edit_id'])) {
                        foreach ($categories as $c) {
                            if ($c['id'] == $_GET['edit_id']) {
                                $category_to_edit = $c;
                                break;
                            }
                        }
                    }
                ?>
                <div class="content-card">
                    <h2 class="card-title"><?php echo $category_to_edit ? 'Edit Category' : 'Add New Category'; ?></h2>
                    <form action="save_category.php" method="POST" class="product-form">
                        <?php if ($category_to_edit): ?>
                            <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($category_to_edit['id']); ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id">Category ID</label>
                                <input type="text" id="id" name="id" value="<?php echo htmlspecialchars($category_to_edit['id'] ?? ''); ?>" required>
                                <small>Short, lowercase, no spaces (e.g., 'new_arrivals')</small>
                            </div>
                            <div class="form-group">
                                <label for="name">Category Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category_to_edit['name'] ?? ''); ?>" required>
                                <small>The full name for display (e.g., 'New Arrivals')</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="icon">FontAwesome Icon</label>
                            <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($category_to_edit['icon'] ?? 'fas fa-tag'); ?>">
                            <small>e.g., 'fas fa-book-open'</small>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="action-btn" style="background-color: var(--primary-color); color: white !important;">Save Category</button>
                        </div>
                    </form>
                </div>

                <div class="content-card">
                    <h2 class="card-title">Existing Categories</h2>
                    <?php if ($categories_load_error): ?>
                        <div class="alert-message alert-danger"><?php echo htmlspecialchars($categories_load_error); ?></div>
                    <?php elseif (empty($categories)): ?>
                        <p class="no-orders-message">No categories found.</p>
                    <?php else: ?>
                        <div class="orders-table-container">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Icon</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><i class="<?php echo htmlspecialchars($category['icon']); ?>"></i> (<?php echo htmlspecialchars($category['icon']); ?>)</td>
                                        <td>
                                            <div class="action-buttons-group">
                                                <a href="admin_dashboard.php?page=categories&edit_id=<?php echo htmlspecialchars($category['id']); ?>" class="action-btn">Edit</a>
                                                <form action="delete_category.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');" style="display:inline;">
                                                    <input type="hidden" name="category_id_to_delete" value="<?php echo htmlspecialchars($category['id']); ?>">
                                                    <button type="submit" class="action-btn action-btn-cancel">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <div class="content-card">
                    <h2 class="card-title">Page Not Found</h2>
                    <p>The page you requested could not be found.</p>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <script>
        function hexToRgb(hex) { /* For focus styles */
            let r = 0, g = 0, b = 0;
            if (hex.length == 4) {
                r = "0x" + hex[1] + hex[1]; g = "0x" + hex[2] + hex[2]; b = "0x" + hex[3] + hex[3];
            } else if (hex.length == 7) {
                r = "0x" + hex[1] + hex[2]; g = "0x" + hex[3] + hex[4]; b = "0x" + hex[5] + hex[6];
            }
            return "" + +r + "," + +g + "," + +b;
        }
        
        const primaryColorCSSVar = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
        if (primaryColorCSSVar) {
            document.documentElement.style.setProperty('--primary-color-rgb', hexToRgb(primaryColorCSSVar));
        }

        const allStatsDataFromPHP = {
            today: <?php echo json_encode($stats_today); ?>,
            week: <?php echo json_encode($stats_week); ?>,
            month: <?php echo json_encode($stats_month); ?>,
            ninetydays: <?php echo json_encode($stats_90_days); ?>,
            year: <?php echo json_encode($stats_year); ?>
        };

        function updateStatsDisplay(period) {
            const selectedStats = allStatsDataFromPHP[period];
            if (selectedStats) {
                document.getElementById('stat_total_orders').textContent = selectedStats.total_orders || 0;
                document.getElementById('stat_confirmed_orders').textContent = selectedStats.confirmed_orders || 0;
                document.getElementById('stat_cancelled_orders').textContent = selectedStats.cancelled_orders || 0;
                document.getElementById('stat_pending_orders_in_period').textContent = selectedStats.pending_orders_in_period || 0;
                document.getElementById('stat_total_revenue').textContent = '৳' + (parseFloat(selectedStats.total_revenue) || 0).toFixed(2);
            } else { 
                document.getElementById('stat_total_orders').textContent = '0';
                document.getElementById('stat_confirmed_orders').textContent = '0';
                document.getElementById('stat_cancelled_orders').textContent = '0';
                document.getElementById('stat_pending_orders_in_period').textContent = '0';
                document.getElementById('stat_total_revenue').textContent = '৳0.00';
            }
        }

        /* --- START: UPDATED JAVASCRIPT FOR SIDEBAR TOGGLE --- */
        document.addEventListener('DOMContentLoaded', function() {
            // Update stats on initial load
            updateStatsDisplay(document.getElementById('period_selector').value); 
            
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            
            if (sidebarToggle && adminSidebar) {
                // Event listener for the toggle button
                sidebarToggle.addEventListener('click', (e) => { 
                    e.stopPropagation(); // Prevents the click from bubbling up to the document
                    adminSidebar.classList.toggle('open'); 
                });
                
                // Event listener to close the sidebar when clicking outside of it
                 document.addEventListener('click', function(event) {
                     if (adminSidebar.classList.contains('open')) {
                         // Check if the click was outside the sidebar and not on the toggle button
                         if (!adminSidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                             adminSidebar.classList.remove('open');
                         }
                     }
                 });
            }
        });
        /* --- END: UPDATED JAVASCRIPT --- */
    </script>
</body>
</html>