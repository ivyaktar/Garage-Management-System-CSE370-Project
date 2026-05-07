<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_GET['id'])) {
        $review_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];

        // We check user_id to ensure customers can only delete THEIR OWN reviews
        $sql = "DELETE FROM reviews WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute([$review_id, $user_id]);

        if ($res) {
            header("Location: ../my-reviews.php?success=Review deleted successfully");
            exit;
        } else {
            header("Location: ../my-reviews.php?error=Delete failed");
            exit;
        }

    } else {
        header("Location: ../my-reviews.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}