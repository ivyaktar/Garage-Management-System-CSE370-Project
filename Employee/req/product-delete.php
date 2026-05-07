<?php 
session_start();

// Updated to allow both Manager and Employee roles
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        $id = $_GET['id'];

        // 1. First, get the image filename so we can delete the file from the folder
        $sql_select = "SELECT image FROM products WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->execute([$id]);
        $product = $stmt_select->fetch();

        if ($product) {
            $image_name = $product['image'];
            $file_path = "../../uploads/" . $image_name;

            // 2. Delete the physical image file from the 'uploads' folder
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // 3. Remove this product from all customer carts
            $sql_cart = "DELETE FROM cart WHERE product_id = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->execute([$id]);

            // 4. Delete the product record from the database table
            $sql_delete = "DELETE FROM products WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $res = $stmt_delete->execute([$id]);

            if ($res) {
                header("Location: ../inventory-view.php?success=Product deleted successfully");
                exit;
            } else {
                header("Location: ../inventory-view.php?error=Could not delete from database");
                exit;
            }
        } else {
            header("Location: ../inventory-view.php");
            exit;
        }

    } else {
        header("Location: ../inventory-view.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>