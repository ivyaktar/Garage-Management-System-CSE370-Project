<?php
session_start();

if (isset($_SESSION['user_id']) &&
    isset($_SESSION['role'])    &&
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {

    include "../../DB_connection.php";

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // --- ACTION 1: STOCK ADJUSTMENT ---
        if ($action == "restock") {
            $p_id = $_POST['product_id'];
            $qty  = $_POST['quantity'];
            $type = $_POST['type'];

            if (empty($p_id) || empty($qty)) {
                header("Location: ../inventory.php?error=Select product and quantity");
                exit;
            } else {
                if ($type == "subtract") {
                    $sql_check = "SELECT quantity FROM products WHERE id = ?";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->execute([$p_id]);
                    $current_stock = $stmt_check->fetchColumn();

                    if ($qty > $current_stock) {
                        header("Location: ../inventory.php?error=Cannot remove $qty items. Only $current_stock available.");
                        exit;
                    }
                    
                    $sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$qty, $p_id]);

                    $sql_after = "SELECT quantity FROM products WHERE id = ?";
                    $stmt_after = $conn->prepare($sql_after);
                    $stmt_after->execute([$p_id]);
                    $new_qty = $stmt_after->fetchColumn();

                    if ($new_qty <= 0) {
                        $sql_status = "UPDATE products SET status = 'Out of Stock' WHERE id = ?";
                        $stmt_status = $conn->prepare($sql_status);
                        $stmt_status->execute([$p_id]);
                    }

                    $msg = "Stock reduced successfully!";

                } else {
                    $sql = "UPDATE products SET quantity = quantity + ?, status = 'In Stock' WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$qty, $p_id]);

                    $msg = "Stock added and status updated to In Stock!";
                }
                
                header("Location: ../inventory.php?success=$msg");
                exit;
            }
        }

        // --- ACTION 2: ADD NEW PRODUCT ---
        else if ($action == "add_new") {
            $p_name      = $_POST['p_name'];
            $price       = $_POST['price'];
            $quantity    = $_POST['quantity'];
            $hp          = $_POST['hp']; // Added Horsepower
            $category_id = $_POST['category_id'];
            $description = $_POST['description']; 
            $status      = "In Stock"; 

            // --- PRICE AND QUANTITY CONSTRAINT ---
            if ($price < 0) {
                header("Location: ../inventory.php?error=Price cannot be negative");
                exit;
            }

            if ($quantity < 0) {
                header("Location: ../inventory.php?error=Quantity cannot be negative");
                exit;
            }

            // Handle Tags using a full for loop
            $tag_string = ""; 
            
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                $tag_list = $_POST['tags'];
                
                for ($i = 0; $i < count($tag_list); $i = $i + 1) {
                    $tag_string = $tag_string . $tag_list[$i];
                    
                    if ($i < count($tag_list) - 1) {
                        $tag_string = $tag_string . ", ";
                    }
                }
            }

            $reg_warranty = isset($_POST['regular_warranty']) ? 1 : 0;
            $rep_warranty = isset($_POST['replacement_warranty']) ? 1 : 0;

            if (empty($p_name) || empty($price) || empty($category_id)) {
                header("Location: ../inventory.php?error=Name, Price, and Category are required");
                exit;
            } else {
                $final_img_name = "";

                if (isset($_FILES['product_image']['name']) && !empty($_FILES['product_image']['name'])) {
                    $img_name = $_FILES['product_image']['name'];
                    $tmp_name = $_FILES['product_image']['tmp_name'];
                    $error    = $_FILES['product_image']['error'];

                    if ($error === 0) {
                        $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                        $img_ex_lc = strtolower($img_ex);
                        $new_img_name = uniqid("PRODUCT-", true).'.'.$img_ex_lc;
                        $img_upload_path = '../../uploads/'.$new_img_name;

                        if (move_uploaded_file($tmp_name, $img_upload_path)) {
                            $final_img_name = $new_img_name;
                        } else {
                            header("Location: ../inventory.php?error=Failed to move uploaded file");
                            exit;
                        }
                    }
                } else {
                    $sql_cat = "SELECT category_img FROM categories WHERE id = ?";
                    $stmt_cat = $conn->prepare($sql_cat);
                    $stmt_cat->execute([$category_id]);
                    $category_data = $stmt_cat->fetch();

                    if ($category_data && !empty($category_data['category_img'])) {
                        $final_img_name = $category_data['category_img'];
                    } else {
                        $final_img_name = "default-cat.png";
                    }
                }

                $sql = "INSERT INTO products(product_name, description, price, quantity, hp, image, category_id, status, regular_warranty, replacement_warranty, tags)
                        VALUES(?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $p_name, 
                    $description, 
                    $price, 
                    $quantity, 
                    $hp, // Added Horsepower to execution
                    $final_img_name, 
                    $category_id, 
                    $status, 
                    $reg_warranty, 
                    $rep_warranty,
                    $tag_string
                ]);

                header("Location: ../inventory.php?success=Product added successfully!");
                exit;
            }
        }
    }
} else {
    header("Location: ../../login.php");
    exit;
}
?>