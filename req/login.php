<?php 
session_start();

if (isset($_POST['uname']) &&
    isset($_POST['pass']) &&
    isset($_POST['role'])) {

    include "../DB_connection.php";
    
    $uname = strtolower($_POST['uname']);
    $pass = $_POST['pass'];
    $role_num = $_POST['role'];

    if (empty($uname)) {
        $em  = "Username is required";
        header("Location: ../login.php?error=$em");
        exit;
    } else if (empty($pass)) {
        $em  = "Password is required";
        header("Location: ../login.php?error=$em");
        exit;
    } else if (empty($role_num)) {
        $em  = "An error Occurred";
        header("Location: ../login.php?error=$em");
        exit;
    } else {
        
        // Match numbers to the user_type in your DB
        if ($role_num == '1') {
            $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Manager'";
            $role = "Manager";
        } else if ($role_num == '2') {
            $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Employee'";
            $role = "Employee";
        } else if ($role_num == '3') {
            $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Customer'";
            $role = "Customer";
        } else {
            $em  = "Invalid Role selection";
            header("Location: ../login.php?error=$em");
            exit;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([$uname]);

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            $username = $user['username'];
            $password = $user['password'];
            $uid = $user['uid'];
            
            if ($username === $uname) {
                if (password_verify($pass, $password)) {
                    
                    // Storing the variables for use in other pages
                    $_SESSION['role'] = $role;
                    $_SESSION['user_id'] = $uid;

                    if ($role == 'Manager') {
                        header("Location: ../Manager/index.php");
                        exit;
                    } else if ($role == 'Employee') {
                        header("Location: ../Employee/index.php");
                        exit;
                    } else if ($role == 'Customer') {
                        header("Location: ../Customer/index.php");
                        exit;
                    }
                } else {
                    $em  = "Incorrect Username or Password";
                    header("Location: ../login.php?error=$em");
                    exit;
                }
            } else {
                $em  = "Incorrect Username or Password";
                header("Location: ../login.php?error=$em");
                exit;
            }
        } else {
            $em  = "Incorrect Username or Password";
            header("Location: ../login.php?error=$em");
            exit;
        }
    }
} else {
    header("Location: ../login.php");
    exit;
}