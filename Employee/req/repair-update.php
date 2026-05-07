<?php 
session_start();

// Access check: Allow both Manager and Employee roles
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    ($_SESSION['role'] == 'Employee' || $_SESSION['role'] == 'Manager')) { 
    
    // Path to DB connection (Up two levels: req -> Manager/Employee -> Root)
    include "../../DB_connection.php";

    if (isset($_POST['repair_id']) && isset($_POST['action'])) {
        
        $repair_id = $_POST['repair_id'];
        $action = $_POST['action'];

        // --- ACTION: ACCEPT ---
        if ($action == "accept") {
            $status = "Inspecting";
            $sql = "UPDATE car_repairs SET status = ? WHERE repair_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $repair_id]);
            
            header("Location: ../repair-edit.php?id=$repair_id&success=Request Accepted");
            exit;

        // --- ACTION: REJECT (INITIAL REQUEST) ---
        } else if ($action == "reject") {
            $status = "Rejected";
            $sql = "UPDATE car_repairs SET status = ? WHERE repair_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $repair_id]);
            
            header("Location: ../repair-house.php?success=Request Rejected");
            exit;

        // --- ACTION: SEND INVOICE ---
        } else if ($action == "request_payment") {
            $repair_notes = $_POST['repair_notes'];
            $labor_fee = $_POST['labor_fee'];
            $parts_total = $_POST['parts_total'];
            $status = "Awaiting Payment";

            if (isset($_POST['parts'])) {
                $selected_parts = $_POST['parts'];
                
                // Remove old selections first
                $sql_clear = "DELETE FROM repair_parts_selected WHERE repair_id = ?";
                $stmt_clear = $conn->prepare($sql_clear);
                $stmt_clear->execute([$repair_id]);

                for ($i = 0; $i < count($selected_parts); $i = $i + 1) {
                    $product_id = $selected_parts[$i];
                    
                    if ($product_id != "") {
                        $sql_info = "SELECT p.*, c.category_name 
                                     FROM products p 
                                     JOIN categories c ON p.category_id = c.id 
                                     WHERE p.id = ?";
                        $stmt_info = $conn->prepare($sql_info);
                        $stmt_info->execute([$product_id]);
                        $product = $stmt_info->fetch();

                        if ($product) {
                            $p_name = $product['product_name'];
                            $p_img = $product['image'];
                            $p_cat = $product['category_name'];
                            $p_price = $product['price'];

                            // Deduct from stock
                            $sql_qty = "UPDATE products SET quantity = quantity - 1 WHERE id = ?";
                            $stmt_qty = $conn->prepare($sql_qty);
                            $stmt_qty->execute([$product_id]);

                            // Log the snapshot
                            $sql_log = "INSERT INTO repair_parts_selected 
                                         (repair_id, product_id, product_name, product_image, category_name, price_at_time) 
                                         VALUES (?, ?, ?, ?, ?, ?)";
                            $stmt_log = $conn->prepare($sql_log);
                            $stmt_log->execute([$repair_id, $product_id, $p_name, $p_img, $p_cat, $p_price]);
                        }
                    }
                }
            }

            $sql = "UPDATE car_repairs 
                    SET status = ?, 
                        repair_notes = ?, 
                        labor_fee = ?, 
                        parts_total = ? 
                    WHERE repair_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $repair_notes, $labor_fee, $parts_total, $repair_id]);
            
            header("Location: ../repair-edit.php?id=$repair_id&success=Invoice Sent");
            exit;

        // --- ACTION: SET READY ---
        } else if ($action == "set_ready") {
            $repair_notes = $_POST['repair_notes'];
            $status = "Ready";

            $sql = "UPDATE car_repairs 
                    SET status = ?, 
                        repair_notes = ? 
                    WHERE repair_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $repair_notes, $repair_id]);
            
            header("Location: ../repair-edit.php?id=$repair_id&success=Job Marked Ready");
            exit;

        // --- ACTION: FINALIZE ---
        } else if ($action == "finalize") {
            $status = "Completed";
            $sql = "UPDATE car_repairs SET status = ? WHERE repair_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $repair_id]);
            
            header("Location: ../repair-edit.php?id=$repair_id&success=Job Completed");
            exit;

        // --- ACTION: CANCEL/REJECT MID-PROCESS ---
        } else if ($action == "cancel_repair") {
            $sql_check = "SELECT status FROM car_repairs WHERE repair_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$repair_id]);
            $current_status = $stmt_check->fetchColumn();

            $allowed_to_cancel = ['Pending', 'Inspecting', 'Awaiting Payment'];
            $is_allowed = false;

            for ($k = 0; $k < count($allowed_to_cancel); $k = $k + 1) {
                if ($current_status == $allowed_to_cancel[$k]) {
                    $is_allowed = true;
                }
            }

            if ($is_allowed == true) {
                
                $sql_parts = "SELECT product_id FROM repair_parts_selected WHERE repair_id = ?";
                $stmt_parts = $conn->prepare($sql_parts);
                $stmt_parts->execute([$repair_id]);
                $parts_to_return = $stmt_parts->fetchAll();

                for ($i = 0; $i < count($parts_to_return); $i = $i + 1) {
                    $p_id = $parts_to_return[$i]['product_id'];

                    $sql_exists = "SELECT id FROM products WHERE id = ?";
                    $stmt_exists = $conn->prepare($sql_exists);
                    $stmt_exists->execute([$p_id]);

                    if ($stmt_exists->rowCount() > 0) {
                        $sql_stock = "UPDATE products SET quantity = quantity + 1 WHERE id = ?";
                        $stmt_stock = $conn->prepare($sql_stock);
                        $stmt_stock->execute([$p_id]);
                    }
                }

                $sql_del_parts = "DELETE FROM repair_parts_selected WHERE repair_id = ?";
                $stmt_del = $conn->prepare($sql_del_parts);
                $stmt_del->execute([$repair_id]);

                $sql_cancel = "UPDATE car_repairs SET status = 'Rejected' WHERE repair_id = ?";
                $stmt_cancel = $conn->prepare($sql_cancel);
                $stmt_cancel->execute([$repair_id]);

                header("Location: ../repair-house.php?success=Repair has been rejected");
                exit;
            } else {
                header("Location: ../repair-edit.php?id=$repair_id&error=Cannot reject job in current status");
                exit;
            }
        }

        header("Location: ../repair-house.php");
        exit;

    } else {
        header("Location: ../repair-house.php");
        exit;
    }

} else {
    header("Location: ../../login.php");
    exit;
}
?>