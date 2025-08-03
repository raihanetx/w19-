<?php
session_start();

// আপনার ওয়েবসাইটের প্রাইমারি কালার (index.html থেকে)
$primary_color = "#8F87F1"; // এটি আপনার index.html এর CSS ভ্যারিয়েবল অনুযায়ী দিন

if (isset($_SESSION['admin_logged_in_thinkplusbd']) && $_SESSION['admin_logged_in_thinkplusbd'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - THINK PLUS BD</title>
    <style>
        /* CSS কোড লেআউট সমস্যার জন্য ঠিক করা হয়েছে */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .page-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
            box-sizing: border-box;
        }

        .login-container {
            background-color: white;
            padding: 2.5rem 3rem;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .login-container img.logo-image {
            max-height: 60px;
            margin-bottom: 1.5rem;
        }
        .login-container h2 {
            color: <?php echo $primary_color; ?>;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input[type="password"]:focus {
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 3px rgba(<?php echo implode(',', sscanf($primary_color, "#%02x%02x%02x")); ?>, 0.15);
            outline: none;
        }
        .login-container button[type="submit"] {
            background-color: <?php echo $primary_color; ?>;
            color: white;
            padding: 0.9rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 600;
            width: 100%;
            transition: filter 0.3s ease, transform 0.2s ease;
        }
        .login-container button[type="submit"]:hover {
            filter: brightness(0.9);
            transform: translateY(-2px);
        }
        .error-message {
            color: #dc3545;
            margin-top: 1.2rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- HTML কোড লেআউট সমস্যার জন্য ঠিক করা হয়েছে -->
    <div class="page-wrapper">
        <div class="login-container">
            <img src="https://i.postimg.cc/4NtztqPt/IMG-20250603-130207-removebg-preview-1.png" alt="THINK PLUS BD Logo" class="logo-image">
            <h2>Admin Panel Login</h2>
            <form method="POST" action="admin_dashboard.php">
                <div class="form-group">
                    <label for="adminPasswordInput">Password</label>
                    <input type="password" name="password" id="adminPasswordInput" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <?php
                if (isset($_GET['error']) && $_GET['error'] == 1) {
                    echo '<p class="error-message">Invalid password! Please try again.</p>';
                }
            ?>
        </div>
    </div>
</body>
</html>