<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_GET['cart_id'])) {
        $cart_id = $_GET['cart_id'];
        $u_id = $_SESSION['user_id'];

        // Delete only if it belongs to the logged-in user for security
        $sql = "DELETE FROM cart WHERE id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute([$cart_id, $u_id]);

        if ($res) {
            header("Location: ../cart.php?success=Item removed from cart");
            exit;
        } else {
            header("Location: ../cart.php?error=Error occurred");
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