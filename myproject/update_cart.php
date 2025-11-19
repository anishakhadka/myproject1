<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_key = $_POST['cart_key'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($cart_key && isset($_SESSION['cart'][$cart_key])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$cart_key]['quantity'] += 1;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$cart_key]['quantity'] -= 1;
            if ($_SESSION['cart'][$cart_key]['quantity'] <= 0) {
                unset($_SESSION['cart'][$cart_key]);
            }
        }
    }

    header('Location: cart.php');
    exit;
} else {
    header('Location: cart.php');
    exit;
}
?>