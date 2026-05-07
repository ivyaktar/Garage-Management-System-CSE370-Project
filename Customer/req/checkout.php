<?php 
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    include "../../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // 1. Get all items in user's cart
    $sql_cart = "SELECT cart.*, products.price FROM cart 
                 JOIN products ON cart.product_id = products.id 
                 WHERE customer_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->execute([$u_id]);
    $items = $stmt_cart->fetchAll();

    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        $total = $item['price'] * $item['quantity'];

        // 2. Insert into orders
        $sql_order = "INSERT INTO orders (customer_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([$u_id, $item['product_id'], $item['quantity'], $total]);

        // 3. Update stock
        $sql_stock = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->execute([$item['quantity'], $item['product_id']]);
    }

    // 4. Clear the cart
    $sql_clear = "DELETE FROM cart WHERE customer_id = ?";
    $stmt_clear = $conn->prepare($sql_clear);
    $stmt_clear->execute([$u_id]);

    header("Location: ../history.php?success=Thank you for your purchase!");
}