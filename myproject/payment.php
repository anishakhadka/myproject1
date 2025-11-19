<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if cart exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart']) || !isset($_SESSION['order_total'])) {
    header("Location: cart.php");
    exit;
}

$total = $_SESSION['order_total'];
$cart = $_SESSION['cart'];
$user_id = $_SESSION['id'] ?? null;

$success_message = '';
$error_message = '';
$selected_method = '';
$redirect = false;

// Step 1: user selects payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // When method is chosen
    if (isset($_POST['payment_method'])) {
        $selected_method = $_POST['payment_method'];
    }

 // Step 2: user confirms payment
    if (isset($_POST['confirm_payment'])) {
        $selected_method = $_POST['confirm_payment'];
        $purpose = trim($_POST['purpose'] ?? '');
        $entered_amount = (float)($_POST['total_amount'] ?? 0);

        // KHALTI: validate 16-digit account number
        if ($selected_method === "khalti") {
            $accNum = $_POST['Number'] ?? '';
            if (!preg_match('/^\d{16}$/', $accNum)) {
                $error_message = "Invalid Khalti account number. Must be 16 digits.";
            }
        }

        // eSEWA validate 10-digit number
        if ($selected_method === "esewa") {
            $num = $_POST['Number'] ?? '';
            if (!preg_match('/^\d{10}$/', $num)) {
                $error_message = "Invalid eSewa number. Must be 10 digits.";
            }
        }

        // Upload screenshot validation
        if (!isset($_FILES['payment_screenshot']) || $_FILES['payment_screenshot']['error'] !== 0) {
            $error_message = "Please upload a screenshot of your payment.";
        }
  // Only proceed if no error
        if (!$error_message && $user_id && !empty($cart)) {

            if ($entered_amount == $total && !empty($purpose)) {

                // Store screenshot file
                $uploadDir = "payment_slips/";
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = time() . "_" . basename($_FILES["payment_screenshot"]["name"]);
                $filePath = $uploadDir . $fileName;
                move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $filePath);

                // DB connect
                $conn = new mysqli("localhost", "root", "", "ak-store");
                if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

                // Insert each order item
                foreach ($cart as $item) {
                    $name = $conn->real_escape_string($item['name']);
                    $quantity = (int)$item['quantity'];
                    $price = (int)$item['price'];
                    $total_price = $price * $quantity;
     $insert_order = $conn->query("
                        INSERT INTO orders (user_id, product_name, quantity, total_price, order_date, status)
                        VALUES ('$user_id', '$name', '$quantity', '$total_price', CURDATE(), 'Pending')
                    ");

                    if (!$insert_order) {
                        die("Insert failed: " . $conn->error);
                    }
                }

                // Insert payment data
                $stmt = $conn->prepare("
                    INSERT INTO payment_notification (user_id, payment_method, purpose, amount, screenshot, payment_date)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("issds", $user_id, $selected_method, $purpose, $entered_amount, $filePath);
                $stmt->execute();
                $stmt->close();
                $conn->close();

                unset($_SESSION['cart']);
                unset($_SESSION['order_total']);

                $success_message = ucfirst($selected_method) . " payment successful!";
                   $redirect = true;

            } else {
                $error_message = "Amount mismatch! Enter exact Rs. " . number_format($total, 2);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Choose Payment Method</title>

    <style>
    body {
        font-family: Arial;
        background: #f4f4f4;
        padding: 20px;
        text-align: center;
    }

    .option-box,
    .form-box {
        background: #fff;
        border: 2px solid #ccc;
        border-radius: 10px;
        padding: 25px;
        margin: 20px auto;
        width: 350px;
    }

    input[type="radio"] {
        margin-right: 10px;
    }

    button,
    input[type="submit"] {
        background-color: #007bff;
        color: white;
        padding: 10px 22px;
        border: none;
        border-radius: 5px;
        font-size: 15px;
        margin-top: 15px;
        cursor: pointer;
    }

    input[type="text"],
    input[type="number"],
    input[type="file"] {
        width: 90%;
        padding: 10px;
        margin-top: 10px;
        border-radius: 6px;
        border: 1px solid #aaa;
        font-size: 1rem;
    }

    .qr-img {
        width: 180px;
        margin-top: 10px;
    }

    .success {
        color: green;
        font-weight: bold;
        font-size: 1.2rem;
        margin-top: 30px;
    }

    .error {
        color: red;
        font-weight: bold;
        font-size: 1.1rem;
        margin-top: 20px;
    }
    </style>

    <?php if ($redirect): ?>
    <script>
    setTimeout(() => {
        window.location.href = "index.php";
    }, 2000);
    </script>
    <?php endif; ?>
</head>

<body>

    <h2>Choose Payment Method</h2>
    <p>Total Amount: <strong>Rs. <?php echo number_format($total, 2); ?></strong></p>

    <!-- Step 1: Choose Method -->
    <?php if (!$selected_method && !$success_message): ?>
    <form method="POST">
        <div class="option-box">
            <label><input type="radio" name="payment_method" value="esewa" required> Pay with eSewa</label><br><br>
            <label><input type="radio" name="payment_method" value="khalti" required> Pay with Khalti</label><br><br>
            <button type="submit">Proceed</button>
        </div>
    </form>
    <?php endif; ?>
    <!-- Step 2 Form -->
    <?php if ($selected_method && !$success_message): ?>
    <div class="form-box">
        <h3><?php echo ucfirst($selected_method); ?> Payment</h3>

        <p><strong>Send to ID:</strong> 9765630974</p>

        <!-- QR image for both -->
        <img src="<?php echo $selected_method; ?>_qr.png" class="qr-img" alt="QR Code">

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="confirm_payment" value="<?php echo htmlspecialchars($selected_method); ?>">

            <!-- Number field changes -->
            <?php if ($selected_method === "khalti"): ?>
            <input type="text" name="Number" placeholder="Khalti Account Number (16 digits)" required maxlength="16">
            <?php else: ?>
            <input type="text" name="Number" placeholder="Your eSewa Number (10 digits)" required maxlength="10">
            <?php endif; ?>

            <input type="text" name="purpose" placeholder="Purpose of sending money" required>
            <input type="number" name="total_amount" placeholder="Enter Total Amount" required>

            <!-- Screenshot upload -->
            <input type="file" name="payment_screenshot" accept="image/*" required>
            <input type="submit" value="Send">
        </form>
    </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
    <div class="success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>

</body>

</html>