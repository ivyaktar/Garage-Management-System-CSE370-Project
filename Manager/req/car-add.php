<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    // 1. Get Data
    $brand        = $_POST['brand'];
    $model        = $_POST['model'];
    $year         = $_POST['year'];
    $category_id  = $_POST['category_id'];
    $transmission = $_POST['transmission'];
    $mileage      = $_POST['mileage'];
    $price        = $_POST['price'];
    $quantity     = $_POST['quantity'];
    $status       = $_POST['status'];
    $description  = $_POST['description'];

    // Checkbox logic for warranty
    $reg_w = isset($_POST['regular_warranty']) ? 1 : 0;
    $rep_w = isset($_POST['replacement_warranty']) ? 1 : 0;

    // 2. Validation
    if (empty($brand) || empty($model) || empty($price)) {
        $em = "Brand, Model, and Price are required";
        header("Location: ../car-add.php?error=$em");
        exit;
    }

    // Price and Mileage Validation (Above 0 and Below 1 Billion)
    $limit = 1000000000;
    if ($price <= 0 || $price >= $limit) {
        $em = "Price must be between 1 and 999,999,999";
        header("Location: ../car-add.php?error=$em");
        exit;
    }

    if ($mileage <= 0 || $mileage >= $limit) {
        $em = "Mileage must be between 1 and 999,999,999";
        header("Location: ../car-add.php?error=$em");
        exit;
    }

    // 3. Image Processing
    if (isset($_FILES['car_image'])) {
        $img_name = $_FILES['car_image']['name'];
        $tmp_name = $_FILES['car_image']['tmp_name'];
        $error    = $_FILES['car_image']['error'];

        if ($error === 0) {
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);
            $allowed_exs = array("jpg", "jpeg", "png");

            if (in_array($img_ex_lc, $allowed_exs)) {
                
                // Path points to root/uploads/
                $new_img_name = "CAR-" . uniqid() . "." . $img_ex_lc;
                $img_upload_path = "../../uploads/" . $new_img_name;

                // Move file first, then insert to DB
                if (move_uploaded_file($tmp_name, $img_upload_path)) {
                    
                    // 4. Database Insert
                    $sql = "INSERT INTO cars (brand, model, year, category_id, price, quantity, status, transmission, mileage, description, regular_warranty, replacement_warranty, image) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    
                    $stmt = $conn->prepare($sql);
                    $res  = $stmt->execute([$brand, $model, $year, $category_id, $price, $quantity, $status, $transmission, $mileage, $description, $reg_w, $rep_w, $new_img_name]);

                    if ($res) {
                        $sm = "New vehicle added successfully!";
                        header("Location: ../car-add.php?success=$sm");
                        exit;
                    } else {
                        $em = "An error occurred during database saving.";
                        header("Location: ../car-add.php?error=$em");
                        exit;
                    }
                } else {
                    $em = "Failed to move file to root/uploads folder. Check permissions.";
                    header("Location: ../car-add.php?error=$em");
                    exit;
                }
            } else {
                $em = "Incorrect file type. Use JPG, JPEG, or PNG.";
                header("Location: ../car-add.php?error=$em");
                exit;
            }
        } else {
            $em = "Image upload error code: $error";
            header("Location: ../car-add.php?error=$em");
            exit;
        }
    } else {
        $em = "Image is required.";
        header("Location: ../car-add.php?error=$em");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>