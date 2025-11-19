<?php
session_start();
include 'db_connect.php';

// Get category from URL
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

if ($category == 'all' || $category == '') {
    $sql = "SELECT * FROM clothes WHERE gender='kids'";
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
} else {
    $sql = "SELECT * FROM clothes WHERE gender='kids' AND category=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Kids' Collection</title>
    <style>
    body {
        font-family: Arial;
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

    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="recommendation.php">Recommendation</a></li>
            <!-- KIDS DROPDOWN -->
            <li class="dropdown">
                <a href="kids.php">Kids ▾</a>
                <div class="dropdown-content">
                    <a href="kids.php?category=all">All</a>
                    <a href="kids.php?category=formal">Formal</a>
                    <a href="kids.php?category=casual">Casual</a>
                    <a href="kids.php?category=party">Party</a>
                    <a href="kids.php?category=accessories">Accessories</a>
                    <a href="kids.php?category=bags">Bags</a>
                    <a href="kids.php?category=shoes">Shoes</a>
                    
                </div>
            </li>
            <li><a href="men.php">Men</a></li>
            <li><a href="women.php">Women</a></li>
            <li><a href="sale.php">sales</a></li>
            <li><a href="cart.php">Cart 🛒</a></li>
            <li><a href="notification.php">🔔</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <h2>🧒 Kids' Collection</h2>

    <div class="product-grid">
        <?php while ($row = $result->fetch_assoc()):

        if ($row['sales'] >= 30 && $row['rating'] < 3) {
            $update = $conn->prepare("UPDATE clothes SET rating=3 WHERE id=?");
            $update->bind_param("i", $row['id']);
            $update->execute();
            $row['rating'] = 3;
        }
    ?>
        <div class="product">
            <?php if(!empty($row['image'])): ?>
            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <p>Rs. <?php echo $row['price']; ?></p>
            <p>
                <?php for($i=1;$i<=5;$i++){ echo ($i<=$row['rating']) ? "⭐" : "☆"; } ?>
            </p>
            <form method="POST" action="add_to_cart.php">
                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                <button type="submit">Add to Cart</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>

</body>

</html>