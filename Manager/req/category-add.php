<?php 
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Manager') {
    if (isset($_POST['category_name'])) {
        
        include "../../DB_connection.php";
        
        $name = $_POST['category_name'];
        $type = $_POST['type']; 
        
        $back_to = "../inventory.php"; 
        if (isset($_POST['back_to'])) {
            $back_to = "../../" . $_POST['back_to'];
        }

        if (empty($name)) {
            header("Location: $back_to?error=Category name is required");
            exit;
        } else {
            // Check if the category name already exists
            $check_sql = "SELECT * FROM categories WHERE category_name = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$name]);
            
            // If the row count is greater than 0, it means it already exists
            if ($check_stmt->rowCount() > 0) {
                header("Location: $back_to?error=The category '$name' already exists!");
                exit;
            } else {
                // If it doesn't exist, proceed with the insertion
                $sql = "INSERT INTO categories (category_name, type) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $res = $stmt->execute([$name, $type]);

                if ($res) {
                    header("Location: $back_to?success=New category created!");
                    exit;
                } else {
                    header("Location: $back_to?error=Unknown error occurred");
                    exit;
                }
            }
        }
    }
} else {
    header("Location: ../../login.php");
    exit;
}