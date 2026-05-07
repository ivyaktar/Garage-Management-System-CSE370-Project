<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_GET['cart_id'])) {
        
        $cart_id = $_GET['cart_id'];
        $u_id = $_SESSION['user_id'];

        // First, we get the current quantity to make sure it's greater than 1
        $sql = "SELECT quantity FROM cart WHERE id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cart_id, $u_id]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            
            $current_qty = $cart_item['quantity'];

            if ($current_qty > 1) {
                
                // We reduce the quantity by 1
                $new_qty = $current_qty - 1;

                $sql_update = "UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $res = $stmt_update->execute([$new_qty, $cart_id, $u_id]);

                if ($res) {
                    header("Location: ../cart.php?success=Quantity updated successfully");
                    exit;
                } else {
                    header("Location: ../cart.php?error=Unknown error occurred");
                    exit;
                }

            } else {
                // If quantity is already 1, we don't reduce further (user should use Delete)
                header("Location: ../cart.php");
                exit;
            }

        } else {
            header("Location: ../cart.php");
            exit;
        }

    } else {
        header("Location: ../cart.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}