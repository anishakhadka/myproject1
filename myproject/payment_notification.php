<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "ak-store");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch notifications with user info
$sql = "
SELECT p.id, p.user_id, p.payment_method, p.purpose, p.amount, p.payment_date, p.status,
       p.screenshot,
       u.firstname, u.email
FROM payment_notification p
JOIN users u ON p.user_id = u.id
ORDER BY p.payment_date ASC
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Notifications</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        padding: 20px;
    }

    .sidebar {
        width: 220px;
        background: #2c3e50;
        color: white;
        height: 100vh;
        position: fixed;
    }

    .main-content {
        margin-left: 220px;
        padding: 20px;
        width: calc(100% - 220px);
        height: 100vh;
        overflow-y: auto;
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

    h2 {
        text-align: center;
    }

    table {
        width: 95%;
        margin: 20px auto;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: center;
    }

    th {
        background: #007bff;
        color: #fff;
    }

    tr:nth-child(even) {
        background: #f9f9f9;
    }

    .success {
        color: green;
        font-weight: bold;
    }

    .failed {
        color: red;
        font-weight: bold;
    }

    img.screenshot {
        width: 80px;
        height: auto;
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
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="manage_user.php">Manage Users</a></li>
            <li><a href="upload_notification.php">Notification Upload</a></li>
            <li><a href="manage_posts.php">Manage Posts</a></li>
            <li><a href="view_order.php">View Orders</a></li>
            <li><a href="upload_clothes.php">Upload Clothes</a></li>
            <li><a href="manage_clothes.php">Manage Clothes</a></li>
            <li><a href="payment_notification.php">Payment Notifications</a></li>
            <li><a href="uploadclothes_recommendation.php">Upload Clothes for Recommendation</a></li>
            <li><a href="manageclothes_recommendation.php"><i class="fa fa-users"></i> Manage clothes for
                    recommendation</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2>Payment Notifications</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Method</th>
                <th>Purpose</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Screenshot</th>
            </tr>

            <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo ucfirst($row['payment_method']); ?></td>
                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                <td>Rs. <?php echo number_format($row['amount'], 2); ?></td>

                <td class="<?php echo ($row['status'] === 'Success' ? 'success' : 'failed'); ?>">
                    <?php echo ($row['status'] === 'Pending') ? 'Completed' : $row['status']; ?>
                </td>

                <td><?php echo $row['payment_date']; ?></td>

                <td>
                    <?php if (!empty($row['screenshot'])): ?>
                    <img src="<?php echo htmlspecialchars($row['screenshot']); ?>" alt="Screenshot" class="screenshot">
                    <?php else: ?>
                    N/A
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="10">No payment notifications yet.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

</body>

</html>

<?php $conn->close(); ?>