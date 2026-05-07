<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    if (isset($_POST['fname'])         && 
        isset($_POST['lname'])         && 
        isset($_POST['email_address']) &&
        isset($_POST['old_password'])) {
        
        include "../../DB_connection.php";

        $fname    = $_POST['fname'];
        $lname    = $_POST['lname'];
        $email    = $_POST['email_address'];
        $phone    = $_POST['phone_number'];
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $c_pass   = $_POST['confirm_password'];
        $user_id  = $_SESSION['user_id']; // This is the 'uid' in users table

        if (empty($fname) || empty($lname) || empty($email) || empty($old_pass)) {
            header("Location: ../profile-edit.php?error=Required fields missing");
            exit;
        }

        // 1. Fetch current password from 'users' table (NOT manager table)
        $sql  = "SELECT password FROM users WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();

        // Verify the old password
        if (!password_verify($old_pass, $user_data['password'])) {
            header("Location: ../profile-edit.php?error=Current password is incorrect");
            exit;
        }

        // 2. Update Profile Info in 'manager' table
        $sql_manager = "UPDATE manager 
                        SET fname = ?, lname = ?, email_address = ?, phone_number = ? 
                        WHERE user_id = ?";
        $stmt_manager = $conn->prepare($sql_manager);
        $res_manager  = $stmt_manager->execute([$fname, $lname, $email, $phone, $user_id]);

        // 3. Update Password in 'users' table if a new one was provided
        if (!empty($new_pass)) {
            if ($new_pass !== $c_pass) {
                header("Location: ../profile-edit.php?error=New passwords do not match");
                exit;
            }

            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql_user = "UPDATE users SET password = ? WHERE uid = ?";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->execute([$hashed_pass, $user_id]);
        }

        if ($res_manager) {
            header("Location: ../profile-edit.php?success=Profile updated successfully!");
            exit;
        } else {
            header("Location: ../profile-edit.php?error=An error occurred");
            exit;
        }

    } else {
        header("Location: ../profile-edit.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}