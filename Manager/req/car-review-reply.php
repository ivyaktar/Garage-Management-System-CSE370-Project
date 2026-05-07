<?php 
session_start();

// 1. Check if the user is a Manager
$is_manager = false;
if (isset($_SESSION['role']) && $_SESSION['role'] == 'Manager') {
    $is_manager = true;
}

// 2. Only proceed if Manager and data is POSTed
if ($is_manager && 
    isset($_POST['review_id']) && 
    isset($_POST['reply_text']) && 
    isset($_POST['car_id'])) {

    include "../../DB_connection.php";

    $review_id = $_POST['review_id'];
    $reply_text = $_POST['reply_text'];
    $car_id = $_POST['car_id'];

    // Validation: Ensure reply isn't empty
    if (empty($reply_text)) {
        header("Location: ../car-view.php?id=$car_id&error=Reply cannot be empty");
        exit;
    }

    // 3. Update the review with the company reply
    $sql = "UPDATE reviews SET company_reply = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $res = $stmt->execute([$reply_text, $review_id]);

    if ($res) {
        // SUCCESS: Redirect back to the manager's car view with a success message
        header("Location: ../car-view.php?id=$car_id&success=Reply posted successfully!");
        exit;
    } else {
        // FAILURE: Redirect back with error
        header("Location: ../car-view.php?id=$car_id&error=Database error occurred");
        exit;
    }

} else {
    // If not a manager or missing data, send to login or home
    header("Location: ../../login.php");
    exit;
}