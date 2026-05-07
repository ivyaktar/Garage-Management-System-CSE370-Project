<?php 
session_start();

if (isset($_SESSION['user_id']) && 
    isset($_SESSION['role'])    && 
    $_SESSION['role'] == 'Customer') {

    if (isset($_GET['id'])) {
        
        include "../../DB_connection.php";
        
        $repair_id = $_GET['id'];
        $u_id = $_SESSION['user_id'];
        
        // 1. Verify ownership and ensure it is in 'Awaiting Payment' status
        $sql = "SELECT * FROM car_repairs WHERE repair_id = ? AND customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$repair_id, $u_id]);
        $repair = $stmt->fetch();

        if ($repair) {
            
            if ($repair['status'] == 'Awaiting Payment') {
                
                // 2. Set fixed diagnostics fee (300), clear parts total, and set status to 'Ready'
                $new_labor = 300;
                $new_parts_total = 0;
                $new_status = 'Ready';
                
                // 3. Update the car_repairs table
                $update_sql = "UPDATE car_repairs 
                               SET labor_fee = ?, 
                                   parts_total = ?, 
                                   status = ? 
                               WHERE repair_id = ?";
                
                $update_stmt = $conn->prepare($update_sql);
                $res = $update_stmt->execute([$new_labor, $new_parts_total, $new_status, $repair_id]);
                
                if ($res) {
                    
                    // 4. Get the snapshot parts before deleting them to return them to stock
                    $get_parts_sql = "SELECT product_id FROM repair_parts_selected WHERE repair_id = ?";
                    $get_parts_stmt = $conn->prepare($get_parts_sql);
                    $get_parts_stmt->execute([$repair_id]);
                    $parts_to_remove = $get_parts_stmt->fetchAll();
                    
                    // Standard for loop to handle parts quantity restoration
                    $parts_count = count($parts_to_remove);
                    for ($i = 0; $i < $parts_count; $i = $i + 1) {
                        
                        $p_id = $parts_to_remove[$i]['product_id'];
                        
                        // CHECK: Does this product still exist in the main inventory?
                        $check_sql = "SELECT id FROM products WHERE id = ?";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->execute([$p_id]);
                        
                        if ($check_stmt->rowCount() > 0) {
                            // Only update quantity if the product wasn't deleted by manager
                            $stock_sql = "UPDATE products SET quantity = quantity + 1 WHERE id = ?";
                            $stock_stmt = $conn->prepare($stock_sql);
                            $stock_stmt->execute([$p_id]);
                        }
                        
                    }
                    
                    // 5. Delete the snapshots because customer declined the repair
                    $delete_parts_sql = "DELETE FROM repair_parts_selected WHERE repair_id = ?";
                    $delete_parts_stmt = $conn->prepare($delete_parts_sql);
                    $delete_parts_stmt->execute([$repair_id]);

                    $sm = "Parts declined. Please pay the $300 diagnostics fee upon pickup.";
                    header("Location: ../repair-view.php?id=$repair_id&success=$sm");
                    exit;
                    
                } else {
                    $em = "Error updating repair record.";
                    header("Location: ../repair-view.php?id=$repair_id&error=$em");
                    exit;
                }
                
            } else {
                $em = "This repair is not in a payable state.";
                header("Location: ../repair-view.php?id=$repair_id&error=$em");
                exit;
            }
            
        } else {
            header("Location: ../repair-dashboard.php");
            exit;
        }
        
    } else {
        header("Location: ../repair-dashboard.php");
        exit;
    }
    
} else {
    header("Location: ../../login.php");
    exit;
}
?>