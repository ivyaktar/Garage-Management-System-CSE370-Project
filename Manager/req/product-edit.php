<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    if (isset($_POST['id'])           &&
        isset($_POST['product_name']) &&
        isset($_POST['category_id'])  &&
        isset($_POST['price'])) {

        $id = $_POST['id'];
        $p_name = $_POST['product_name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $hp = $_POST['hp']; // Added horsepower variable
        $remove_img = isset($_POST['remove_image']) ? 1 : 0;

        // Handle Warranty Checkboxes (1 or 0)
        $reg_w = 0;
        if (isset($_POST['regular_warranty'])) {
            $reg_w = 1;
        }

        $rep_w = 0;
        if (isset($_POST['replacement_warranty'])) {
            $rep_w = 1;
        }

        // --- TAGS LOGIC ---
        $tags_str = "";
        if (isset($_POST['tags'])) {
            $tags_array = $_POST['tags'];
            $tag_count = count($tags_array);
            
            for ($i = 0; $i < $tag_count; $i = $i + 1) {
                if ($i == 0) {
                    $tags_str = $tags_array[$i];
                } else {
                    $tags_str = $tags_str . "," . $tags_array[$i];
                }
            }
        }

        // Default: Keep current image
        $final_image = null; 

        // --- IMAGE UPDATE LOGIC ---
        
        // Scenario A: Manager wants to REMOVE the image (Revert to category icon)
        if ($remove_img == 1) {
            $sql_cat = "SELECT category_img FROM categories WHERE id = ?";
            $stmt_cat = $conn->prepare($sql_cat);
            $stmt_cat->execute([$category_id]);
            $category = $stmt_cat->fetch();
            
            if ($category) {
                $final_image = $category['category_img'];
            } else {
                $final_image = "default-cat.png";
            }
        } 
        // Scenario B: Manager uploaded a NEW image
        else if (isset($_FILES['product_image']['name']) && !empty($_FILES['product_image']['name'])) {
            $img_name = $_FILES['product_image']['name'];
            $tmp_name = $_FILES['product_image']['tmp_name'];
            $error = $_FILES['product_image']['error'];

            if ($error === 0) {
                $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_ex_lc = strtolower($img_ex);
                $allowed_exs = array("jpg", "jpeg", "png");

                if (in_array($img_ex_lc, $allowed_exs)) {
                    $new_img_name = uniqid("IMG-", true).'.'.$img_ex_lc;
                    $img_upload_path = '../../uploads/'.$new_img_name;
                    move_uploaded_file($tmp_name, $img_upload_path);
                    $final_image = $new_img_name;
                }
            }
        }

        // --- DATABASE UPDATE ---

        if ($final_image != null) {
            $sql = "UPDATE products 
                    SET product_name = ?, 
                        category_id = ?, 
                        price = ?, 
                        description = ?, 
                        regular_warranty = ?, 
                        replacement_warranty = ?,
                        tags = ?,
                        hp = ?,
                        image = ?
                    WHERE id = ?";
            $params = [$p_name, $category_id, $price, $description, $reg_w, $rep_w, $tags_str, $hp, $final_image, $id];
        } else {
            // Update everything EXCEPT the image (keep existing), including hp
            $sql = "UPDATE products 
                    SET product_name = ?, 
                        category_id = ?, 
                        price = ?, 
                        description = ?, 
                        regular_warranty = ?, 
                        replacement_warranty = ?,
                        tags = ?,
                        hp = ?
                    WHERE id = ?";
            $params = [$p_name, $category_id, $price, $description, $reg_w, $rep_w, $tags_str, $hp, $id];
        }

        $stmt = $conn->prepare($sql);
        $res = $stmt->execute($params);

        if ($res) {
            header("Location: ../inventory-view.php?success=Product updated successfully");
            exit;
        } else {
            header("Location: ../product-edit.php?id=$id&error=Unknown error occurred");
            exit;
        }

    } else {
        header("Location: ../inventory-view.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>