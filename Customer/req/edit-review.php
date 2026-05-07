<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role']) && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_POST['rating']) && 
        isset($_POST['comment']) && 
        isset($_POST['review_id']) &&
        isset($_POST['product_id'])) {
        
        include "../../DB_connection.php";

        $rating = $_POST['rating'];
        $comment = $_POST['comment'];
        $r_id = $_POST['review_id'];
        $p_id = $_POST['product_id'];
        $u_id = $_SESSION['user_id'];

        // Security check: Ensure this review actually belongs to this user
        $sql_check = "SELECT id FROM reviews WHERE id = ? AND user_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$r_id, $u_id]);

        if ($stmt_check->rowCount() > 0) {
            $sql = "UPDATE reviews 
                    SET rating = ?, comment = ? 
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $res = $stmt->execute([$rating, $comment, $r_id, $u_id]);

            if ($res) {
                header("Location: ../product-view.php?id=$p_id&success=Review updated successfully!");
                exit;
            } else {
                header("Location: ../product-view.php?id=$p_id&error=Failed to update review");
                exit;
            }
        } else {
            header("Location: ../product-view.php?id=$p_id&error=Unauthorized action");
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