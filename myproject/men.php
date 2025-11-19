<?php
session_start();
include 'db_connect.php';

// Get category from URL
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Prepare SQL for clothes
if ($category === 'all' || $category === '') {
    $sql1 = "SELECT *, 'clothes' AS table_name FROM clothes WHERE gender='men'";
} else {
    $sql1 = "SELECT *, 'clothes' AS table_name FROM clothes WHERE gender='men' AND category=?";
}
$stmt1 = $conn->prepare($sql1);
if ($category !== 'all' && $category !== '') {
    $stmt1->bind_param("s", $category);
}
$stmt1->execute();
$result1 = $stmt1->get_result();

// Prepare SQL for recommendation_clothes
if ($category === 'all' || $category === '') {
    $sql2 = "SELECT *, 'recommendation' AS table_name FROM recommendation_clothes WHERE gender='men'";
} else {
    $sql2 = "SELECT *, 'recommendation' AS table_name FROM recommendation_clothes WHERE gender='men' AND category=?";
}
$stmt2 = $conn->prepare($sql2);
if ($category !== 'all' && $category !== '') {
    $stmt2->bind_param("s", $category);
}
$stmt2->execute();
$result2 = $stmt2->get_result();

// Merge both results into one array
$products = [];
while ($row = $result1->fetch_assoc()) {
    $products[] = $row;
}
while ($row = $result2->fetch_assoc()) {
    $products[] = $row;
}

$stmt1->close();
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Men's Collection</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f2f2f2;
        margin: 0;
    }

    nav {
        background: #333;
        padding: 10px 0;
    }

    nav ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    nav ul li {
        position: relative;
        margin-right: 20px;
    }

    nav ul li a {
        color: white;
        text-decoration: none;
        padding: 10px;
        display: block;
    }

    nav ul li a:hover {
        background: #555;
        border-radius: 4px;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background: #444;
        min-width: 150px;
        z-index: 1000;
    }

    .dropdown-content a {
        padding: 10px;
        display: block;
        color: #fff;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .dropdown-content a:hover {
        background: #555;
    }

    h2 {
        background: #222;
        color: white;
        padding: 15px;
        margin: 0;
    }

    .product-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        padding: 20px;
        justify-content: flex-start;
    }

    .product {
        background: white;
        padding: 10px;
        width: 220px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 1px 1px 6px rgba(0, 0, 0, 0.1);
    }

    .product img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 5px;
    }

    .product button {
        width: 100%;
        padding: 10px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 5px;
    }

    .product button:hover {
        background: #0056b3;
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="recommendation.php">Recommendation</a></li>
            <li class="dropdown">
                <a href="men.php">Men ▾</a>
                <div class="dropdown-content">
                    <a href="men.php?category=all">All</a>
                    <a href="men.php?category=formal">Formal</a>
                    <a href="men.php?category=casual">Casual</a>
                    <a href="men.php?category=party">Party</a>
                    <a href="men.php?category=accessories">Accessories</a>
                    <a href="men.php?category=bags">Bags</a>
                    <a href="men.php?category=shoes">Shoes</a>
                </div>
            </li>
            <li><a href="women.php">Women</a></li>
            <li><a href="kids.php">Kids</a></li>
            <li><a href="sale.php">Sale</a></li>
            <li><a href="cart.php">Cart 🛒</a></li>
            <li><a href="notification.php">🔔</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <h2>👕 Men's Collection</h2>

    <div class="product-grid">
        <?php foreach ($products as $row):
            $product_name = $row['product_name'] ?? $row['name'] ?? 'No Name';
            $image = !empty($row['image']) ? 'uploads/' . $row['image'] : 'uploads/all_sample.jpg';
            $rating = $row['rating'] ?? 0;
            $price = $row['price'] ?? 0;
            $table_name = $row['table_name'];
        ?>
        <div class="product">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>">
            <h3><?php echo htmlspecialchars($product_name); ?></h3>
            <p>Rs. <?php echo number_format($price, 2); ?></p>
            <p>
                <?php for ($i=1; $i<=5; $i++): ?>
                <?php echo ($i <= $rating) ? "⭐" : "☆"; ?>
                <?php endfor; ?>
            </p>
            <form method="POST" action="add_to_cart.php">
                <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table_name); ?>">
                <button type="submit">Add to Cart</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

</body>

</html>