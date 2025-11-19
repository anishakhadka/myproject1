<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Connect to DB
$conn = new mysqli("localhost", "root", "", "ak-store");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Approve/Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id']);

    if (isset($_POST['approve'])) {
        // Approve order
        $conn->query("UPDATE orders SET status='Approved' WHERE id=$orderId");

        // Get order data
        $getOrderData = $conn->query("SELECT user_id, product_id, product_name, quantity FROM orders WHERE id=$orderId");
        if ($getOrderData && $getOrderData->num_rows > 0) {
            $orderData = $getOrderData->fetch_assoc();
            $userId = (int)$orderData['user_id'];
            $productId = (int)$orderData['product_id'];
            $productName = $orderData['product_name'] ?? '';
            $quantityOrdered = (int)$orderData['quantity'];

            // Send notification to user
            if ($productName) {
                $message = "✅ Your order #$orderId for $productName has been approved.";
            } else {
                $message = "✅ Your order #$orderId has been approved.";
            }

            $stmt = $conn->prepare("INSERT INTO notification (user_id, message) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("is", $userId, $message);
                $stmt->execute();
                $stmt->close();
            }

            // Update product rating (optional)
            if ($productId > 0) {
                $orderCountResult = $conn->query("SELECT SUM(quantity) AS total_quantity FROM orders WHERE product_id=$productId AND status='Approved'");
                $totalQuantity = $orderCountResult->fetch_assoc()['total_quantity'] ?? 0;
                $stars = min(5, floor($totalQuantity / 10));

                // Try updating in clothes table first
                $conn->query("UPDATE clothes SET rating=$stars WHERE id=$productId");
                // Also try in recommendation_clothes
                $conn->query("UPDATE recommendation_clothes SET rating=$stars WHERE id=$productId");
            }
        }

    } elseif (isset($_POST['delete'])) {
        // Delete order
        $conn->query("DELETE FROM orders WHERE id=$orderId");
    }
}

// Fetch orders with user info and product image
$sql = "
SELECT 
    o.id, o.user_id, o.product_id, o.product_name, o.quantity, o.total_price, o.status, o.order_date,
    u.firstname,
    c.product_name AS clothes_name, c.image AS clothes_image,
    r.product_name AS rec_name, r.image AS rec_image
FROM orders o
JOIN users u ON o.user_id = u.id
LEFT JOIN clothes c ON o.product_id = c.id
LEFT JOIN recommendation_clothes r ON o.product_id = r.id
ORDER BY o.id ASC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Orders</title>
    <style>
    body {
        background-color: #fff8e1;
        font-family: Arial;
        padding: 20px;
    }

    .sidebar {
        width: 220px;
        background: #2c3e50;
        color: white;
        height: 100vh;
        position: fixed;
    }

    .sidebar .profile {
        text-align: center;
        padding: 20px;
    }

    .profile-pic {
        width: 80px;
        border-radius: 50%;
    }

    .online {
        color: limegreen;
    }

    .sidebar .menu {
        list-style: none;
        padding: 0;
    }

    .sidebar .menu li a {
        display: block;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
    }

    .sidebar .menu li a:hover {
        background-color: #34495e;
    }

    .main-content {
        margin-left: 220px;
        padding: 20px;
        width: calc(100% - 220px);
        overflow-y: auto;
    }

    h1 {
        text-align: center;
        color: #333;
        font-size: 2rem;
    }

    table {
        margin: 0 auto;
        width: 95%;
        border-collapse: collapse;
        font-size: 1rem;
        background: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
        border: 1px solid #999;
        padding: 12px;
        text-align: center;
    }

    th {
        background-color: #f0e68c;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    form {
        display: inline;
    }

    button {
        padding: 6px 12px;
        margin: 2px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }

    .approve {
        background-color: #28a745;
        color: white;
    }

    .delete {
        background-color: #dc3545;
        color: white;
    }

    .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 5px;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="profile">
            <img src="assets/images/admin.jpg" alt="Admin" class="profile-pic">
            <h3>AK store</h3>
            <p class="online">● Online</p>
        </div>
        <ul class="menu">
            <li><a href="index.php"><i class="fa fa-home"></i> Dashboard</a></li>
            <li><a href="manage_user.php"><i class="fa fa-users"></i> Manage Users</a></li>
            <li><a href="upload_notification.php"><i class="fa fa-file-alt"></i> notification upload</a></li>
            <li><a href="manage_posts.php"><i class="fa fa-file-alt"></i> Manage Posts</a></li>
            <li><a href="view_order.php"><i class="fa fa-shopping-cart"></i> View Orders</a></li>
            <li><a href="upload_clothes.php"><i class="fa fa-upload"></i> Upload Clothes</a></li>
            <li><a href="manage_clothes.php"><i class="fa fa-upload"></i>Manage Clothes</a></li>
            <li><a href="payment_notification.php"><i class="fa fa-upload"></i>Paymnet Notification</a></li>
            <li><a href="uploadclothes_recommendation.php"><i class="fa fa-upload"></i>Upload Clothes for
                    recommendation</a></li>
            <li><a href="manageclothes_recommendation.php"><i class="fa fa-users"></i> Manage clothes for
                    recommendation</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            <li><a href="#"><i class="fa fa-cog"></i> Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>🧾 All Orders</h1>
        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Product</th>
                <th>Image</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): 
            // Choose product name and image correctly
            $productName = $row['product_name'] ?: $row['clothes_name'] ?: $row['rec_name'] ?: 'Unknown';
            $image = $row['clothes_image'] ?: $row['rec_image'] ?: '';
        ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['firstname']) ?></td>
                <td><?= htmlspecialchars($productName) ?></td>
                <td>
                    <?php if ($image): ?>
                    <img src="uploads/<?= htmlspecialchars($image) ?>" class="product-img" alt="product">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['quantity']) ?></td>
                <td>Rs. <?= htmlspecialchars($row['total_price']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['order_date']) ?></td>
                <td>
                    <?php if ($row['status'] !== 'Approved'): ?>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="approve" class="approve">Approve</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="delete" class="delete"
                            onclick="return confirm('Delete this order?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>

</html>

<?php $conn->close(); ?>