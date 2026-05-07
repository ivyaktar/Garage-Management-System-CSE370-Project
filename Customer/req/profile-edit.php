<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_POST['fname'])         && 
        isset($_POST['lname'])         && 
        isset($_POST['email_address']) && 
        isset($_POST['old_password'])) {
        
        include "../../DB_connection.php";

        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email_address'];
        $phone = $_POST['phone_number'];
        $addr  = $_POST['address'];
        
        $old_pass   = $_POST['old_password'];
        $new_pass   = $_POST['new_password'];
        $c_new_pass = $_POST['confirm_password'];
        
        $user_id = $_SESSION['user_id'];

        if (empty($fname) || empty($lname) || empty($email) || empty($old_pass)) {
            
            $em = "Required fields are missing";
            header("Location: ../profile-edit.php?error=$em");
            exit;
            
        } else {
            // 1. Verify the current password from the users table
            $sql_verify = "SELECT password FROM users WHERE uid = ?";
            $stmt_verify = $conn->prepare($sql_verify);
            $stmt_verify->execute([$user_id]);
            $user = $stmt_verify->fetch();

            if (password_verify($old_pass, $user['password'])) {
                
                // 2. Update Personal Info in the customer table
                $sql = "UPDATE customer 
                        SET fname = ?, 
                            lname = ?, 
                            email_address = ?, 
                            phone_number = ?, 
                            address = ? 
                        WHERE user_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$fname, $lname, $email, $phone, $addr, $user_id]);

                // 3. Update Password if a new one was provided
                if (!empty($new_pass)) {
                    
                    if ($new_pass === $c_new_pass) {
                        
                        $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                        
                        $sql_pass = "UPDATE users SET password = ? WHERE uid = ?";
                        $stmt_pass = $conn->prepare($sql_pass);
                        $stmt_pass->execute([$new_pass_hashed, $user_id]);
                        
                    } else {
                        
                        $em = "Profile updated, but new passwords did not match";
                        header("Location: ../profile-edit.php?error=$em");
                        exit;
                        
                    }
                }

                // Redirect back to edit page with success message
                $sm = "Profile updated successfully!";
                header("Location: ../profile-edit.php?success=$sm");
                exit;

            } else {
                
                $em = "Incorrect current password";
                header("Location: ../profile-edit.php?error=$em");
                exit;
                
            }
        }
    } else {
        header("Location: ../profile-edit.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}