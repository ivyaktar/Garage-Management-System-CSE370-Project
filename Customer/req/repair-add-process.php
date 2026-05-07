<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    include "../../DB_connection.php";

    if (isset($_POST['brand'])         &&
        isset($_POST['model'])         &&
        isset($_POST['year'])          &&
        isset($_POST['mileage'])       &&
        isset($_POST['service_type'])  &&
        isset($_FILES['car_image'])) {

        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $year = $_POST['year'];
        $mileage = $_POST['mileage'];
        $service_type = $_POST['service_type'];
        $description = $_POST['description'];
        $customer_id = $_SESSION['user_id'];

        // Logic for Labor Fee and Repair Type
        $labor_fee = 0;
        $repair_type = "Service"; // Default

        if ($service_type == "Regular Service") {
            $labor_fee = 500;
            $repair_type = "Service";
        } else if ($service_type == "Premium Service") {
            $labor_fee = 1000;
            $repair_type = "Service";
        } else if ($service_type == "Repair Car") {
            $labor_fee = 300;
            $repair_type = "Repair";
        }

        // Image Upload Handling
        $img_name = $_FILES['car_image']['name'];
        $tmp_name = $_FILES['car_image']['tmp_name'];
        $error = $_FILES['car_image']['error'];

        if ($error === 0) {
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);

            $allowed_exs = array("jpg", "jpeg", "png");

            $is_allowed = false;
            for ($i = 0; $i < count($allowed_exs); $i = $i + 1) {
                if ($img_ex_lc == $allowed_exs[$i]) {
                    $is_allowed = true;
                }
            }

            if ($is_allowed) {
                $new_img_name = uniqid("CAR-", true) . '.' . $img_ex_lc;
                $img_upload_path = "../../uploads/" . $new_img_name;
                move_uploaded_file($tmp_name, $img_upload_path);

                // SQL Insertion (Added repair_type column)
                $sql = "INSERT INTO car_repairs (customer_id, repair_type, brand, model, year, mileage, 
                                                issue_description, car_image, status, labor_fee) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $status = "Pending";
                $res = $stmt->execute([$customer_id, $repair_type, $brand, $model, $year, $mileage, 
                                       $description, $new_img_name, $status, $labor_fee]);

                if ($res) {
                    header("Location: ../repair-dashboard.php?success=Request submitted successfully");
                    exit;
                } else {
                    $em = "An error occurred during submission";
                    header("Location: ../repair-add.php?error=$em");
                    exit;
                }

            } else {
                $em = "You cannot upload files of this type";
                header("Location: ../repair-add.php?error=$em");
                exit;
            }
        } else {
            $em = "Unknown error occurred while uploading image";
            header("Location: ../repair-add.php?error=$em");
            exit;
        }

    } else {
        $em = "All fields are required";
        header("Location: ../repair-add.php?error=$em");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}