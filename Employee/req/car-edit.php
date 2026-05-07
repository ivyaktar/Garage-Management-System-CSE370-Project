<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Employee')) {

    if (isset($_POST['id'])           &&
        isset($_POST['model'])        &&
        isset($_POST['brand'])        &&
        isset($_POST['category_id'])  &&
        isset($_POST['transmission']) &&
        isset($_POST['price'])        &&
        isset($_POST['year'])         &&
        isset($_POST['quantity'])     &&
        isset($_POST['mileage'])      &&
        isset($_POST['status'])       &&
        isset($_POST['description'])  &&
        isset($_POST['old_image'])) {
        
        include "../../DB_connection.php";

        $id           = $_POST['id'];
        $model        = $_POST['model'];
        $brand        = $_POST['brand'];
        $category_id  = $_POST['category_id'];
        $transmission = $_POST['transmission'];
        $price        = $_POST['price'];
        $year         = $_POST['year'];
        $quantity     = $_POST['quantity'];
        $mileage      = $_POST['mileage'];
        $status       = $_POST['status'];
        $description  = $_POST['description'];
        $old_image    = $_POST['old_image'];

        /* --- 1. AUCTION CHECK --- */
        // We check if the car is currently in an active auction
        $auc_sql = "SELECT id FROM auctions WHERE car_id = ? AND status = 'Active'";
        $auc_stmt = $conn->prepare($auc_sql);
        $auc_stmt->execute([$id]);
        $in_auction = $auc_stmt->fetch();

        if ($in_auction) {
            header("Location: ../car-edit.php?id=$id&error=This car is currently in an active auction and cannot be edited.");
            exit;
        }

        /* --- 2. FETCH CURRENT YEAR --- */
        $set_sql = "SELECT current_year FROM setting LIMIT 1";
        $set_stmt = $conn->prepare($set_sql);
        $set_stmt->execute();
        $setting = $set_stmt->fetch();
        $curr_year = $setting['current_year'];

        /* --- 3. VALIDATION --- */
        $max_val = 1000000000;

        if ($year < 1900 || $year > $curr_year) {
            header("Location: ../car-edit.php?id=$id&error=Year must be between 1900 and $curr_year");
            exit;
        }

        if ($price <= 0 || $price >= $max_val) {
            header("Location: ../car-edit.php?id=$id&error=Price must be between 1 and 999,999,999");
            exit;
        }

        if ($mileage <= 0 || $mileage >= $max_val) {
            header("Location: ../car-edit.php?id=$id&error=Mileage must be between 1 and 999,999,999");
            exit;
        }

        /* --- 4. WARRANTY CHECKBOXES --- */
        $reg_w = isset($_POST['regular_warranty']) ? 1 : 0;
        $rep_w = isset($_POST['replacement_warranty']) ? 1 : 0;

        /* --- 5. IMAGE HANDLING --- */
        if (isset($_FILES['car_image']['name']) && $_FILES['car_image']['name'] != "") {
            
            $img_name = $_FILES['car_image']['name'];
            $tmp_name = $_FILES['car_image']['tmp_name'];
            $error    = $_FILES['car_image']['error'];

            if ($error === 0) {
                $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_ex_lc = strtolower($img_ex);
                $allowed_exs = array("jpg", "jpeg", "png");

                if (in_array($img_ex_lc, $allowed_exs)) {
                    $new_img_name = "CAR-" . uniqid() . "." . $img_ex_lc;
                    $img_upload_path = "../../uploads/" . $new_img_name;
                    
                    // Delete old image file
                    $old_image_path = "../../uploads/" . $old_image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }

                    move_uploaded_file($tmp_name, $img_upload_path);

                    $sql = "UPDATE cars SET 
                            brand=?, model=?, year=?, category_id=?, price=?, 
                            quantity=?, status=?, transmission=?, mileage=?, 
                            description=?, regular_warranty=?, replacement_warranty=?, image=?
                            WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $res = $stmt->execute([$brand, $model, $year, $category_id, $price, 
                                           $quantity, $status, $transmission, $mileage, 
                                           $description, $reg_w, $rep_w, $new_img_name, $id]);
                } else {
                    header("Location: ../car-edit.php?id=$id&error=Invalid image format");
                    exit;
                }
            }
        } else {
            // Update without image change
            $sql = "UPDATE cars SET 
                    brand=?, model=?, year=?, category_id=?, price=?, 
                    quantity=?, status=?, transmission=?, mileage=?, 
                    description=?, regular_warranty=?, replacement_warranty=?
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $res = $stmt->execute([$brand, $model, $year, $category_id, $price, 
                                   $quantity, $status, $transmission, $mileage, 
                                   $description, $reg_w, $rep_w, $id]);
        }

        /* --- 6. FINAL REDIRECT --- */
        if ($res) {
            header("Location: ../showroom-manage.php?success=Vehicle updated successfully");
            exit;
        } else {
            header("Location: ../car-edit.php?id=$id&error=Unknown error occurred");
            exit;
        }

    } else {
        header("Location: ../showroom-manage.php");
        exit;
    }
} else {
    header("Location: ../../login.php");
    exit;
}
?>