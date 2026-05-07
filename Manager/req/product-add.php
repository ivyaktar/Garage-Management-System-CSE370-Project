<?php
session_start();

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create a debug log file
$debug_log = "../../debug_product_add.txt";

if (isset($_SESSION['user_id']) &&
    isset($_SESSION['role'])    &&
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    // Log that script was reached
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Action: $action\n", FILE_APPEND);

        // --- ACTION 2: ADD NEW PRODUCT ---
        if ($action == "add_new") {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Entering add_new action\n", FILE_APPEND);
            
            $p_name      = $_POST['p_name'];
            $price       = $_POST['price'];
            $quantity    = $_POST['quantity'];
            $category_id = $_POST['category_id'];
            $description = $_POST['description']; 
            $hp          = $_POST['hp']; 

            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Data: name=$p_name, price=$price, qty=$quantity, cat=$category_id\n", FILE_APPEND);

            $reg_warranty = isset($_POST['regular_warranty']) ? 1 : 0;
            $rep_warranty = isset($_POST['replacement_warranty']) ? 1 : 0;

            if (empty($p_name) || empty($price) || empty($category_id)) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Validation FAILED: missing required fields\n", FILE_APPEND);
                header("Location: ../inventory.php?error=Name, Price, and Category are required");
                exit;
            }
            
            // Handle image
            $final_img_name = "default-cat.png";
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0 && !empty($_FILES['product_image']['name'])) {
                $img_name = $_FILES['product_image']['name'];
                $tmp_name = $_FILES['product_image']['tmp_name'];
                $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_ex_lc = strtolower($img_ex);
                $new_img_name = uniqid("PRODUCT-", true).'.'.$img_ex_lc;
                $img_upload_path = '../../uploads/'.$new_img_name;
                
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Trying to upload: $img_name to $img_upload_path\n", FILE_APPEND);
                
                if (move_uploaded_file($tmp_name, $img_upload_path)) {
                    $final_img_name = $new_img_name;
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Upload SUCCESS\n", FILE_APPEND);
                } else {
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Upload FAILED. Error code: " . $_FILES['product_image']['error'] . "\n", FILE_APPEND);
                }
            } else {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - No image uploaded, using default\n", FILE_APPEND);
            }

            // Insert into database
            $sql = "INSERT INTO products(product_name, description, price, quantity, image, category_id, status, regular_warranty, replacement_warranty, tags, hp)
                    VALUES(?,?,?,?,?,?,?,?,?,?,?)";
            
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $p_name, 
                $description, 
                $price, 
                $quantity, 
                $final_img_name, 
                $category_id, 
                'In Stock', 
                $reg_warranty, 
                $rep_warranty,
                '',  // tags
                $hp 
            ]);

            if ($result) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - INSERT SUCCESS! Last ID: " . $conn->lastInsertId() . "\n", FILE_APPEND);
                header("Location: ../inventory.php?success=Product added successfully!");
                exit;
            } else {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - INSERT FAILED!\n", FILE_APPEND);
                $error_info = $stmt->errorInfo();
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Error: " . print_r($error_info, true) . "\n", FILE_APPEND);
                header("Location: ../inventory.php?error=Database insert failed");
                exit;
            }
        }
    }
} else {
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Access denied: user_id=" . ($_SESSION['user_id'] ?? 'not set') . ", role=" . ($_SESSION['role'] ?? 'not set') . "\n", FILE_APPEND);
    header("Location: ../../login.php");
    exit;
}
?>