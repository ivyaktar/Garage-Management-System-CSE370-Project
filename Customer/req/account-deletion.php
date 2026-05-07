<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer') {
    
    include "../../DB_connection.php";
    $user_id = $_SESSION['user_id'];

    // 1. Delete from customer
    $sql1 = "DELETE FROM customer WHERE user_id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$user_id]);

    // 2. Delete from users using 'uid' column
    $sql2 = "DELETE FROM users WHERE uid = ?";
    $stmt2 = $conn->prepare($sql2);
    $res2 = $stmt2->execute([$user_id]);

    if ($res2) {
        session_unset();
        session_destroy();
        header("Location: ../../login.php?success=Goodbye!");
        exit;
    } else {
        header("Location: ../profile.php?error=Error");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}