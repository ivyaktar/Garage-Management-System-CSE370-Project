<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role']) && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_POST['reply']) && 
        isset($_POST['review_id']) && 
        isset($_POST['product_id'])) {
        
        include "../../DB_connection.php";

        $reply = $_POST['reply'];
        $rev_id = $_POST['review_id'];
        $p_id = $_POST['product_id'];
        
        // Determine the message based on if it's an update
        $msg = isset($_POST['is_edit']) ? "Reply updated successfully!" : "Reply posted successfully!";

        if (empty($reply)) {
            header("Location: ../view-reviews.php?id=$p_id&error=Reply cannot be empty");
            exit;
        }

        $sql = "UPDATE reviews SET company_reply = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute([$reply, $rev_id]);

        if ($res) {
            header("Location: ../view-reviews.php?id=$p_id&success=$msg");
            exit;
        } else {
            header("Location: ../view-reviews.php?id=$p_id&error=An error occurred while saving");
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