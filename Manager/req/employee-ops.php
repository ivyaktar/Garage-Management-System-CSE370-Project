<?php
session_start();
include "../../DB_connection.php";

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager') {

    // REGISTER EMPLOYEE
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        
        // Convert username to lowercase immediately to prevent login mismatches
        $uname = strtolower($_POST['username']); 
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $sal   = $_POST['salary'];

        // Constraint: Salary cannot be below 0
        if ($sal < 0) {
            header("Location: ../employees.php?error=Salary cannot be a negative value");
            exit;
        }

        // Check if username already exists
        $stmt_check = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $stmt_check->execute([$uname]);
        
        if ($stmt_check->rowCount() > 0) {
            header("Location: ../employees.php?error=Username already exists");
            exit;
        }

        // Set the starting password as the literal string 'default'
        $pass = password_hash("default", PASSWORD_DEFAULT);
        
        // Insert into users table
        $sql1 = "INSERT INTO users(username, password, user_type) VALUES(?, ?, 'employee')";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$uname, $pass]);
        $user_id = $conn->lastInsertId();

        // Insert into employees table
        $sql2 = "INSERT INTO employees(user_id, fname, lname, email_address, phone_number, salary) VALUES(?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$user_id, $fname, $lname, $email, $phone, $sal]);
        
        header("Location: ../employees.php?success=Registered successfully");
        exit;
    }

    // UPDATE SALARY
    if (isset($_POST['action']) && $_POST['action'] == 'update_salary') {
        
        $new_sal = $_POST['new_salary'];
        $emp_id  = $_POST['emp_id'];

        if ($new_sal < 0) {
            header("Location: ../employees.php?error=Update failed: Salary cannot be below 0");
            exit;
        }

        $sql = "UPDATE employees SET salary = ? WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_sal, $emp_id]);
        
        header("Location: ../employees.php?success=Salary updated");
        exit;
    }

    // UPDATE EMPLOYEE INFO
    if (isset($_POST['action']) && $_POST['action'] == 'edit_info') {
        
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $emp_id = $_POST['emp_id'];

        $sql = "UPDATE employees SET fname = ?, lname = ?, email_address = ? WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fname, $lname, $email, $emp_id]);
        
        header("Location: ../employees.php?success=Info updated");
        exit;
    }

    // DELETE EMPLOYEE
    if (isset($_GET['delete_id'])) {
        
        $delete_uid = $_GET['delete_id'];
        
        // Note: Check if your table uses 'uid' or 'id' - consistent with your last snippet
        $sql = "DELETE FROM users WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$delete_uid]);
        
        header("Location: ../employees.php?success=Employee deleted");
        exit;
    }
    
} else { 
    header("Location: ../../login.php"); 
    exit; 
}
?>