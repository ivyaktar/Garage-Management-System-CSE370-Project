<?php 
session_start();

// Ensure the user is logged in as a Manager
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_POST['message_id'])) {
        
        include "../../DB_connection.php";
        $message_id = $_POST['message_id'];

        // Standard SQL Delete using the correct column name from your table
        $sql = "DELETE FROM message WHERE message_id = ?";
        $stmt = $conn->prepare($sql);
        $re = $stmt->execute([$message_id]);

        if ($re) {
            $sm = "Message deleted successfully!";
            header("Location: ../message.php?success=$sm");
            exit;
        } else {
            $em = "An error occurred while deleting.";
            header("Location: ../message.php?error=$em");
            exit;
        }

    } else {
        header("Location: ../message.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}