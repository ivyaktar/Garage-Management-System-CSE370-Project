<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_POST['old_pass']) && 
        isset($_POST['new_pass']) && 
        isset($_POST['c_new_pass'])) {
        
        include "../../DB_connection.php";

        $old_pass = $_POST['old_pass'];
        $new_pass = $_POST['new_pass'];
        $c_new_pass = $_POST['c_new_pass'];
        $user_id = $_SESSION['user_id'];

        if (empty($old_pass)) {
            $em = "Old password is required";
            header("Location: ../pass.php?error=$em");
            exit;
        } else if (empty($new_pass)) {
            $em = "New password is required";
            header("Location: ../pass.php?error=$em");
            exit;
        } else if (empty($c_new_pass)) {
            $em = "Please confirm your new password";
            header("Location: ../pass.php?error=$em");
            exit;
        } else if ($new_pass !== $c_new_pass) {
            $em = "New password and confirmation do not match";
            header("Location: ../pass.php?error=$em");
            exit;
        } else {
            
            // 1. Fetch current password using 'uid' column
            $sql = "SELECT password FROM users WHERE uid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            // 2. Verify the old password
            if (password_verify($old_pass, $user['password'])) {
                
                // 3. Hash the new password
                $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);

                // 4. Update using 'uid' column
                $sql_2 = "UPDATE users SET password = ? WHERE uid = ?";
                $stmt_2 = $conn->prepare($sql_2);
                $stmt_2->execute([$new_pass_hashed, $user_id]);

                $sm = "The password has been changed successfully!";
                header("Location: ../pass.php?success=$sm");
                exit;

            } else {
                $em = "Incorrect old password";
                header("Location: ../pass.php?error=$em");
                exit;
            }
        }

    } else {
        header("Location: ../pass.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}