<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Manager') {

    include "../../DB_connection.php";

    /* --- 1. RECEIVE BASIC AUCTION DATA --- */
    $start_price   = $_POST['start_price'];
    $reserve_price = $_POST['reserve_price'];
    $min_increment = $_POST['min_increment'];
    $end_time      = $_POST['end_time'];


    /* --- 2. VALIDATION --- */
    if ($reserve_price < $start_price) {
        header("Location: ../add-auction.php?error=Reserve price cannot be lower than the starting bid");
        exit;
    }

    if (empty($min_increment) || $min_increment <= 0) {
        header("Location: ../add-auction.php?error=Minimum increment must be greater than 0");
        exit;
    }


    /* --- 3. TIME VALIDATION --- */
    $current_time  = date('Y-m-d H:i:s');
    $selected_time = date('Y-m-d H:i:s', strtotime($end_time));

    if ($selected_time <= $current_time) {
        header("Location: ../add-auction.php?error=Auction end time must be in the future");
        exit;
    }


    $car_id = 0;

    /* --- 4. HANDLE VEHICLE LOGIC (Unified Flow) --- */
    
    // Check if it's a manual entry (New Registration)
    if (isset($_POST['brand']) && !empty($_POST['brand'])) {
        $brand        = $_POST['brand'];
        $model        = $_POST['model'];
        $year         = $_POST['year'];
        $category_id  = $_POST['category_id'];
        $transmission = $_POST['transmission'];
        $mileage      = $_POST['mileage'];
        $description  = $_POST['description'];
        
        $reg_w = isset($_POST['regular_warranty']) ? 1 : 0;
        $rep_w = isset($_POST['replacement_warranty']) ? 1 : 0;

        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
            $img_name = $_FILES['car_image']['name'];
            $tmp_name = $_FILES['car_image']['tmp_name'];
            
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);
            $allowed_exs = array("jpg", "jpeg", "png");

            if (in_array($img_ex_lc, $allowed_exs)) {
                $new_img_name = "AUC-" . uniqid() . "." . $img_ex_lc;
                $img_upload_path = "../../uploads/" . $new_img_name;
                move_uploaded_file($tmp_name, $img_upload_path);

                // Step 1: Add to inventory first with 0 quantity (since it's going straight to auction)
                $sql_car = "INSERT INTO cars (brand, model, year, category_id, price, quantity, 
                                            status, transmission, mileage, description, 
                                            regular_warranty, replacement_warranty, image, in_auction) 
                            VALUES (?, ?, ?, ?, ?, 0, 'In Auction', ?, ?, ?, ?, ?, ?, 1)";
                
                $stmt_car = $conn->prepare($sql_car);
                $stmt_car->execute([
                    $brand, $model, $year, $category_id, $reserve_price, 
                    $transmission, $mileage, $description, 
                    $reg_w, $rep_w, $new_img_name
                ]);
                
                $car_id = $conn->lastInsertId();
            } else {
                header("Location: ../add-auction.php?error=Invalid image format");
                exit;
            }
        } else {
            header("Location: ../add-auction.php?error=Vehicle image is required for new registration");
            exit;
        }

    } else if (isset($_POST['car_id']) && !empty($_POST['car_id'])) {
        // Handle selection from existing Inventory
        $car_id = $_POST['car_id'];

        $sql_check = "SELECT quantity FROM cars WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$car_id]);
        $car_data = $stmt_check->fetch();

        if ($car_data && $car_data['quantity'] > 0) {
            $new_qty = $car_data['quantity'] - 1;
            $new_status = ($new_qty <= 0) ? 'Out of Stock' : 'In Stock';

            $sql_upd = "UPDATE cars 
                        SET quantity = ?, 
                            status = ?, 
                            in_auction = 1 
                        WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            $stmt_upd->execute([$new_qty, $new_status, $car_id]);
        } else {
            header("Location: ../add-auction.php?error=Car is out of stock");
            exit;
        }
    } else {
        header("Location: ../add-auction.php?error=Please provide car details or select from inventory");
        exit;
    }


    /* --- 5. CREATE THE AUCTION --- */
    
    if ($car_id > 0) {
        // Removed source_type from the SQL to prevent "Column not found" errors
        $sql_auc = "INSERT INTO auctions (car_id, start_price, current_bid, reserve_price, min_increment, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Active')";
        $stmt_auc = $conn->prepare($sql_auc);
        
        $res = $stmt_auc->execute([
            $car_id, 
            $start_price, 
            $start_price, 
            $reserve_price, 
            $min_increment, 
            $selected_time
        ]);

        if ($res) {
            header("Location: ../auction-dashboard.php?success=Auction successfully launched!");
            exit;
        } else {
            header("Location: ../add-auction.php?error=Failed to initialize auction record");
            exit;
        }
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>