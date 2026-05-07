<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_POST['id']) && isset($_POST['qty'])) {
        
        include "../../DB_connection.php";

        $product_id = $_POST['id'];
        $qty_requested = $_POST['qty'];
        $customer_id = $_SESSION['user_id'];

        // 1. Fetch current stock and product name for a better error message
        $sql_stock = "SELECT quantity, product_name FROM products WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->execute([$product_id]);
        $product = $stmt_stock->fetch();

        if ($product) {
            $current_inventory = $product['quantity'];
            $p_name = $product['product_name'];

            // 2. Check if the product is already in the cart for this user
            $sql_check = "SELECT quantity FROM cart WHERE customer_id = ? AND product_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$customer_id, $product_id]);
            $cart_item = $stmt_check->fetch();

            $existing_qty = 0;
            if ($cart_item) {
                $existing_qty = $cart_item['quantity'];
            }

            // Calculate what the new total would be
            $new_total_qty = $existing_qty + $qty_requested;

            // 3. STOCK VALIDATION: Prevent adding more than available stock
            // This triggers if (In Cart + New Request) > Stock
            if ($new_total_qty > $current_inventory) {
                
                $available_to_add = $current_inventory - $existing_qty;
                
                if ($existing_qty > 0) {
                    $msg = "Cannot add $qty_requested more. You already have $existing_qty in your cart, and only $current_inventory are available in total.";
                } else {
                    $msg = "Cannot add $qty_requested. Only $current_inventory items are in stock.";
                }

                // Redirect back to the product view page with the specific error
                header("Location: ../product-view.php?id=$product_id&error=$msg");
                exit;
            }

            // 4. Update or Insert into Cart
            if ($cart_item) {
                
                $sql_update = "UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$new_total_qty, $customer_id, $product_id]);
                
            } else {
                
                $sql_insert = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->execute([$customer_id, $product_id, $qty_requested]);
                
            }

            header("Location: ../store.php?success=$p_name added to cart!");
            exit;
            
        } else {
            header("Location: ../store.php?error=Product not found.");
            exit;
        }

    } else {
        header("Location: ../store.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>