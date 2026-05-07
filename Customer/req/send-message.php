<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_POST['message_text']) && 
        isset($_POST['full_name'])    && 
        isset($_POST['email_address'])) {
        
        include "../../DB_connection.php";

        $message   = $_POST['message_text'];
        $full_name = $_POST['full_name'];
        $email     = $_POST['email_address'];

        if (empty($message)) {
            $em = "Message is required";
            header("Location: ../contact.php?error=$em");
            exit;
        } 

        // Matching your DB screenshot: sender_full_name, sender_email, message, status
        // We let the DB handle date_time or you can pass it manually
        $sql  = "INSERT INTO message (sender_full_name, sender_email, message, status) 
                 VALUES (?, ?, ?, 'Unread')";
        
        $stmt = $conn->prepare($sql);
        $res  = $stmt->execute([$full_name, $email, $message]);

        if ($res) {
            $sm = "Message sent successfully!";
            header("Location: ../contact.php?success=$sm");
            exit;
        } else {
            $em = "An error occurred during insertion";
            header("Location: ../contact.php?error=$em");
            exit;
        }

    } else {
        header("Location: ../contact.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}