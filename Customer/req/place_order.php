<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    // Ensure we are hitting the right database path
    include "../../DB_connection.php";
    $u_id = $_SESSION['user_id'];

    // 1. Fetch all items for this specific customer
    // We join with products to get price and current stock levels
    $sql_cart = "SELECT cart.*, products.price, products.quantity AS stock, products.product_name 
                 FROM cart 
                 JOIN products ON cart.product_id = products.id 
                 WHERE cart.customer_id = ?";
    
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->execute([$u_id]);
    $cart_items = $stmt_cart->fetchAll();

    // Check if the cart actually has items
    if (count($cart_items) > 0) {
        
        // 2. Loop through for validation first (Safety Check)
        for ($i = 0; $i < count($cart_items); $i++) {
            $item = $cart_items[$i];
            
            if ($item['quantity'] > $item['stock']) {
                $name = $item['product_name'];
                header("Location: ../cart.php?error=Not enough stock for $name");
                exit;
            }
        }
        
        // 3. Loop through to process the purchase
        for ($i = 0; $i < count($cart_items); $i++) {
            
            $item = $cart_items[$i];
            $p_id = $item['product_id'];
            $qty  = $item['quantity'];
            $price = $item['price'];
            
            $total = $price * $qty;
            $date_ordered = date("Y-m-d");

            // A. Create the Order record
            $sql_order = "INSERT INTO orders (customer_id, product_id, quantity, total_price, date_ordered) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->execute([$u_id, $p_id, $qty, $total, $date_ordered]);

            // B. Reduce the Stock from products table
            $sql_stock = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->execute([$qty, $p_id]);
        }

        // 4. Clear the cart for this user after success
        $sql_clear = "DELETE FROM cart WHERE customer_id = ?";
        $stmt_clear = $conn->prepare($sql_clear);
        $stmt_clear->execute([$u_id]);

        header("Location: ../history.php?success=Order placed successfully!");
        exit;

    } else {
        // If the code reaches here, it means the SQL query returned 0 rows
        header("Location: ../store.php?error=Order failed: Your cart was empty.");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}