<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        padding: 20px;
    }

    nav#nav {
        background: #333;
        padding: 10px 0;
    }

    nav#nav .navbar ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    nav#nav .navbar ul li a {
        color: white;
        text-decoration: none;
        padding: 8px 12px;
        display: inline-block;
    }

    nav#nav .navbar ul li a.active,
    nav#nav .navbar ul li a:hover {
        background-color: #555;
        border-radius: 4px;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    th,
    td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: center;
        vertical-align: middle;
    }

    .cart-product {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cart-product img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
    }

    .qty-btn {
        background-color: #007bff;
        border: none;
        color: white;
        font-weight: bold;
        padding: 4px 8px;
        cursor: pointer;
        border-radius: 4px;
        margin: 0 5px;
    }

    .qty-btn:hover {
        background-color: #0056b3;
    }

    .remove-btn {
        padding: 6px 10px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .remove-btn:hover {
        background-color: #c82333;
    }

    .order-btn-container {
        text-align: center;
        margin-top: 20px;
    }

    .order-btn {
        padding: 12px 30px;
        font-size: 16px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .order-btn:hover {
        background-color: #218838;
    }

    .notification {
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        text-align: center;
    }

    .notification.success {
        background-color: #d4edda;
        color: #155724;
    }

    .notification.error {
        background-color: #f8d7da;
        color: #721c24;
    }
    </style>
</head>

<body>

    <nav id="nav">
        <div class="navbar">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="recommendation.php">Recommend</a></li>
                <li><a href="index.php?category=men">Men</a></li>
                <li><a href="index.php?category=women">Women</a></li>
                <li><a href="index.php?category=kids">Kids</a></li>
                <li><a href="index.php?category=sale">Sale</a></li>
                <li><a class="nav-link active" href="cart.php">Cart 🛒</a></li>
                <li><a href="notification.php">🔔</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <h2>Your Cart 🛒</h2>

    <?php if (isset($_GET['added'])): ?>
    <div class="notification success">Item added to cart successfully!</div>
    <?php elseif (isset($_GET['removed'])): ?>
    <div class="notification error">Item removed from cart.</div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="notification error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($cart)): ?>
    <table>
        <tr>
            <th>Product</th>
            <th>Price (Rs)</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
        <?php
            $grand_total = 0;
            foreach ($cart as $cart_key => $item):
                $total = $item['price'] * $item['quantity'];
                $grand_total += $total;
            ?>
        <tr>
            <td>
                <div class="cart-product">
                    <?php if (!empty($item['image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                        alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                </div>
            </td>
            <td><?php echo number_format($item['price'], 2); ?></td>
            <td>
                <form action="update_cart.php" method="POST" style="display:inline;">
                    <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                    <input type="hidden" name="action" value="decrease">
                    <button type="submit" class="qty-btn">➖</button>
                </form>

                <?php echo $item['quantity']; ?>

                <form action="update_cart.php" method="POST" style="display:inline;">
                    <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                    <input type="hidden" name="action" value="increase">
                    <button type="submit" class="qty-btn">➕</button>
                </form>
            </td>
            <td><?php echo number_format($total, 2); ?></td>
            <td>
                <form method="POST" action="update_cart.php" onsubmit="return confirm('Remove this item?');">
                    <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                    <input type="hidden" name="action" value="decrease">
                    <button type="submit" class="remove-btn">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="3">Grand Total</th>
            <th colspan="2"><?php echo number_format($grand_total, 2); ?></th>
        </tr>
    </table>

    <div class="order-btn-container">
        <form method="POST" action="checkout.php">
            <button type="submit" class="order-btn">Checkout</button>
        </form>
    </div>

    <?php else: ?>
    <p style="text-align:center;">Your cart is empty.</p>
    <?php endif; ?>

</body>

</html>